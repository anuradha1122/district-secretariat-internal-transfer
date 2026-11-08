<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTransferCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage transfer cycles') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'opens_on' => [
                'required',
                'date_format:Y-m-d',
            ],

            'closes_on' => [
                'required',
                'date_format:Y-m-d',
                'after_or_equal:opens_on',
            ],

            'status' => [
                'required',
                Rule::in(['draft', 'open', 'closed']),
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'closes_on.after_or_equal' =>
                'The closing date must be on or after the opening date.',
        ];
    }
}
