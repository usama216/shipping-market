<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'unique:customers,email',
            ],
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8',
            'date_of_birth' => 'nullable|date',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'required|string|max:255',
            'is_active' => 'required|in:1,0',
            'tax_id' => 'nullable|string|max:50',
            'address' => 'required|string|max:500',
            'suite' => 'nullable|string|max:50',
            'zip_code' => 'required|string|max:20',
        ];
    }
}
