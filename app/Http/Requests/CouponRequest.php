<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CouponRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $couponId = $this->route('coupon')?->id;

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('coupons')->ignore($couponId),
            ],
            'discount_type' => ['required', 'in:percentage,fixed'],
            'discount_value' => [
                'required',
                'numeric',
                'min:0',
                function ($attribute, $value, $fail) {
                    if ($this->discount_type === 'percentage' && $value > 100) {
                        $fail('Percentage discount cannot exceed 100%.');
                    }
                },
            ],
            'per_customer_limit' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after:start_date'],
            'is_active' => ['boolean'],
            'auto_apply' => ['boolean'],
            'is_private' => ['boolean'],
            'target_audience' => ['required', 'in:all,new_customer,registration,certain_customers'],
            'description' => ['nullable', 'string', 'max:500'],
            'selected_customer_ids' => ['nullable', 'array'],
            'selected_customer_ids.*' => ['integer', 'exists:customers,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'This coupon code already exists.',
            'discount_type.in' => 'Discount type must be either percentage or fixed.',
            'discount_value.min' => 'Discount value must be greater than 0.',
            'usage_limit.min' => 'Usage limit must be at least 1.',
            'expiry_date.after' => 'Expiry date must be in the future.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'auto_apply' => $this->boolean('auto_apply'),
            'is_private' => $this->boolean('is_private'),
            'code' => strtoupper($this->code),
        ]);
    }
}
