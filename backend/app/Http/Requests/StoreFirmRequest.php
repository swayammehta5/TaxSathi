<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFirmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'gstin' => ['required', 'string', 'max:32'],
            'address' => ['required', 'string', 'max:2000'],
            'state' => ['nullable', 'string', 'max:100'],
            'state_code' => ['nullable', 'string', 'max:4'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
        ];
    }
}
