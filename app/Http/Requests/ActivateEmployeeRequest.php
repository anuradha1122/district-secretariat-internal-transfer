<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ActivateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('nic'))) {
            $this->merge([
                'nic' => strtoupper(trim($this->input('nic'))),
            ]);
        }

        if (is_string($this->input('activation_code'))) {
            $this->merge([
                'activation_code' => strtoupper(
                    trim($this->input('activation_code'))
                ),
            ]);
        }

        if (is_string($this->input('email'))) {
            $this->merge([
                'email' => strtolower(trim($this->input('email'))),
            ]);
        }

        if (is_string($this->input('name'))) {
            $this->merge([
                'name' => trim($this->input('name')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'nic' => [
                'required',
                'string',
                'max:12',
            ],

            'activation_code' => [
                'required',
                'string',
                'size:24',
                'regex:/^[A-F0-9]{24}$/',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'confirmed',
                Password::min(12),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'activation_code.size' => 'The activation code must contain 24 characters.',
            'activation_code.regex' => 'Enter a valid activation code.',
        ];
    }
}
