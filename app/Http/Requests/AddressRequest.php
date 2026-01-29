<?php

namespace App\Http\Requests;

use App\Models\Country;
use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
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
        // Check if the selected country requires postal codes
        $postalCodeRule = 'nullable|string|max:20';

        if ($this->country_code) {
            $country = Country::where('code', $this->country_code)->first();
            if ($country && $country->has_postal_code) {
                $postalCodeRule = 'required|string|max:20';
            }
        }

        return [
            'address_name' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'address_line_1' => 'required|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'country' => 'required|string|max:100',
            'state' => 'nullable|string|max:100',
            'city' => 'required|string|max:100',
            'postal_code' => $postalCodeRule,
            'country_code' => 'required|string',
            'phone_number' => 'required|string|max:20',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'address_name.required' => 'Address name is required.',
            'full_name.required' => 'Full name is required.',
            'address_line_1.required' => 'Address line 1 is required.',
            'country.required' => 'Country is required.',
            'city.required' => 'City is required.',
            'postal_code.required' => 'Postal code is required.',
            'country_code.required' => 'Country code is required.',
            'phone_number.required' => 'Phone number is required.',
            'phone_number.regex' => 'Phone number contains invalid characters.',
        ];
    }
}
