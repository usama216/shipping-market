<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoyaltyRuleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'spend_amount' => ['required', 'numeric', 'min:0.01'],
            'earn_points' => ['required', 'integer', 'min:1'],
            'redeem_points' => ['required', 'integer', 'min:1'],
            'redeem_value' => ['required', 'numeric', 'min:0.01'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'spend_amount.min' => 'Spend amount must be greater than 0.',
            'earn_points.min' => 'Earn points must be at least 1.',
            'redeem_points.min' => 'Redeem points must be at least 1.',
            'redeem_value.min' => 'Redeem value must be greater than 0.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
