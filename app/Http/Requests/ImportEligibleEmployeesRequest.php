<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportEligibleEmployeesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('import eligible employees') ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                'mimes:csv,txt',
                'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Please select a CSV file.',
            'file.mimes' => 'The file must be a CSV file.',
            'file.max' => 'The file must not be larger than 2 MB.',
        ];
    }
}
