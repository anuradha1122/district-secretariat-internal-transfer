<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTransferApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null
            && $user->eligible_employee_id !== null
            && $user->hasRole('Employee');
    }

    public function rules(): array
    {
        return [
            'full_name' => ['nullable', 'string', 'max:255'],

            'name_with_initials_sinhala' => [
                'nullable',
                'string',
                'max:255',
            ],

            'name_with_initials_english' => [
                'nullable',
                'string',
                'max:255',
            ],

            'date_of_birth' => [
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],

            'gender' => [
                'nullable',
                Rule::in(['female', 'male']),
            ],

            'permanent_address' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'temporary_address' => [
                'nullable',
                'string',
                'max:5000',
            ],

            'office_phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'personal_phone' => [
                'nullable',
                'string',
                'max:20',
            ],

            'marital_status' => [
                'nullable',
                Rule::in(['married', 'unmarried']),
            ],

            'spouse_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'spouse_occupation' => [
                'nullable',
                'string',
                'max:255',
            ],

            'spouse_workplace' => [
                'nullable',
                'string',
                'max:255',
            ],

            'post' => [
                'nullable',
                'string',
                'max:255',
            ],

            'grade' => [
                'nullable',
                'string',
                'max:255',
            ],

            'appointment_letter_no' => [
                'nullable',
                'string',
                'max:255',
            ],

            'is_confirmed' => [
                'nullable',
                'boolean',
            ],

            'current_office_id' => [
                'nullable',
                'integer',
                Rule::exists('offices', 'id')
                    ->where('is_active', true),
            ],

            'service_id' => [
                'nullable',
                'integer',
                Rule::exists('services', 'id')
                    ->where('is_active', true),
            ],

            'reported_to_current_office_on' => [
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
            ],

            'accept_nearby_office' => [
                'nullable',
                'boolean',
            ],

            'transfer_reason' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'children' => ['present', 'array', 'max:20'],

            'children.*.name' => [
                'required',
                'string',
                'max:255',
            ],

            'children.*.age' => [
                'nullable',
                'integer',
                'min:0',
                'max:120',
            ],

            'children.*.school' => [
                'nullable',
                'string',
                'max:255',
            ],

            'work_history' => ['present', 'array', 'max:50'],

            'work_history.*.workplace_name' => [
                'required',
                'string',
                'max:255',
            ],

            'work_history.*.from_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'work_history.*.to_date' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'preferences' => ['present', 'array', 'max:5'],

            'preferences.*.office_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('offices', 'id')
                    ->where('is_active', true),
            ],

            'preferences.*.reason' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $currentOffice = $this->input('current_office_id');

                foreach ($this->input('preferences', []) as $index => $preference) {
                    if (
                        $currentOffice
                        && (string) ($preference['office_id'] ?? '')
                            === (string) $currentOffice
                    ) {
                        $validator->errors()->add(
                            "preferences.{$index}.office_id",
                            'A preferred office cannot be your current office.'
                        );
                    }
                }

                foreach ($this->input('work_history', []) as $index => $history) {
                    $from = $history['from_date'] ?? null;
                    $to = $history['to_date'] ?? null;

                    if ($from && $to && $to < $from) {
                        $validator->errors()->add(
                            "work_history.{$index}.to_date",
                            'The end date must be on or after the start date.'
                        );
                    }
                }
            },
        ];
    }
}
