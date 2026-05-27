<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullName' => 'required|string|max:255',
            'email' => 'nullable|string|email|max:255',
            'phone' => 'nullable|string|max:255',
            'streetLine1' => 'required|string|max:255',
            'streetLine2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'country' => 'required|string|max:100',
        ];
    }
}
