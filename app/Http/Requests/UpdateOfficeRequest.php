<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOfficeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage offices') ?? false;
    }

    public function rules(): array
    {
        $office = $this->route('office');

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('offices', 'code')->ignore($office->id),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'type' => [
                'required',
                Rule::in([
                    'district_secretariat',
                    'divisional_secretariat',
                ]),
            ],

            'parent_id' => [
                'nullable',
                'required_if:type,divisional_secretariat',
                'prohibited_if:type,district_secretariat',
                'integer',
                Rule::exists('offices', 'id')
                    ->where('type', 'district_secretariat')
                    ->where('is_active', true),
                Rule::notIn([$office->id]),
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}
