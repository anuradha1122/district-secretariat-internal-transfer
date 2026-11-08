<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeProfileRequest extends FormRequest
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
            'name_with_initials' => [
                'required',
                'string',
                'max:255',
            ],

            'designation' => [
                'required',
                'string',
                'max:255',
            ],

            'employee_no' => [
                'nullable',
                'string',
                'max:50',
            ],

            'mobile_no' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+()\\-\\s]+$/',
            ],

            'office_id' => [
                'required',
                'integer',
                Rule::exists('offices', 'id')
                    ->where('is_active', true),
            ],

            'service_id' => [
                'required',
                'integer',
                Rule::exists('services', 'id')
                    ->where('is_active', true),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'office_id.exists' => 'Please select an active office.',

            'service_id.exists' => 'Please select an active service.',

            'mobile_no.regex' => 'Please enter a valid mobile number.',
        ];
    }
}
