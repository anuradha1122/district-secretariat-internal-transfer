<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage services') ?? false;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge([
                'code' => strtoupper(trim($this->input('code'))),
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
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('services', 'code'),
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],
        ];
    }
}
