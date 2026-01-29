<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PackageRequest extends FormRequest
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
        $isDraft = $this->boolean('is_draft');

        return [
            // Draft flag
            'is_draft' => 'nullable|boolean',

            // Package basics - relaxed for drafts
            'id' => 'sometimes',
            'from' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'date_received' => $isDraft ? 'nullable|date' : 'required|date',
            'customer_id' => $isDraft ? 'nullable|exists:customers,id' : 'required|exists:customers,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'store_tracking_id' => 'nullable|string|max:100',
            'total_value' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'note' => 'nullable|string|max:1000',
            'status' => 'sometimes|nullable|integer|between:0,4',
            
            // Export compliance fields
            'incoterm' => 'nullable|string|max:10',
            'invoice_signature_name' => 'nullable|string|max:255',
            'exporter_id_license' => 'nullable|string|max:50',
            'us_filing_type' => 'nullable|string|max:50',
            'exporter_code' => 'nullable|string|max:50',
            'itn_number' => 'nullable|string|max:50',

            // Package files
            'files.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',

            // Items validation - relaxed for drafts
            'items' => 'sometimes|array',
            'items.*.title' => $isDraft ? 'nullable|string|max:255' : 'required|string|max:255',
            'items.*.description' => $isDraft ? 'nullable|string|max:1000' : 'required|string|max:1000',
            'items.*.hs_code' => $isDraft ? 'nullable|string|max:20' : 'required|string|max:20',
            'items.*.eei_code' => 'nullable|string|max:50',
            'items.*.material' => $isDraft ? 'nullable|string|max:100' : 'required|string|max:100',
            'items.*.item_note' => 'nullable|string|max:500',
            'items.*.quantity' => $isDraft ? 'nullable|integer|min:1' : 'required|integer|min:1',
            'items.*.value_per_unit' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'items.*.weight_per_unit' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'items.*.weight_unit' => $isDraft ? 'nullable|in:lb,kg' : 'required|in:lb,kg',
            'items.*.total_line_value' => 'nullable|numeric|min:0',
            'items.*.total_line_weight' => 'nullable|numeric|min:0',

            // Item dimensions
            'items.*.length' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'items.*.width' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'items.*.height' => $isDraft ? 'nullable|numeric|min:0' : 'required|numeric|min:0',
            'items.*.dimension_unit' => $isDraft ? 'nullable|in:in,cm' : 'required|in:in,cm',

            // Item classification
            'items.*.is_dangerous' => 'nullable|boolean',
            'items.*.un_code' => 'nullable|string|max:10',
            'items.*.is_fragile' => 'nullable|boolean',
            'items.*.is_oversized' => 'nullable|boolean',
            'items.*.classification_notes' => 'nullable|string|max:500',

            // Item images
            'items.*.files.*' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'items.*.new_files.*' => 'nullable|image|mimes:jpeg,png,webp|max:2048',
            'items.*.delete_file_ids' => 'nullable|array',
            'items.*.delete_file_ids.*' => 'exists:package_files,id',

            // Invoice validation
            'invoices' => 'nullable|array',
            'invoices.*.type' => 'nullable|in:received,customer_submitted',
            'invoices.*.invoice_number' => 'nullable|string|max:100',
            'invoices.*.vendor_name' => 'nullable|string|max:255',
            'invoices.*.invoice_date' => 'nullable|date',
            'invoices.*.invoice_amount' => 'nullable|numeric|min:0',
            'invoices.*.notes' => 'nullable|string|max:500',
            'invoices.*.files.*' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
            'invoices.*.file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            // Package messages
            'from.required' => 'Please enter where the package is from.',
            'date_received.required' => 'Please select the date received.',
            'customer_id.required' => 'Please select a customer.',
            'customer_id.exists' => 'The selected customer is invalid.',
            'total_value.required' => 'Total value is required.',

            // Item messages
            'items.*.title.required' => 'Each item must have a title.',
            'items.*.title.max' => 'Item title cannot exceed 255 characters.',
            'items.*.quantity.required' => 'Each item must have a quantity.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
            'items.*.value_per_unit.required' => 'Each item must have a value per unit.',
            'items.*.value_per_unit.min' => 'Value per unit cannot be negative.',

            // New required field messages
            'items.*.description.required' => 'Each item must have a description.',
            'items.*.hs_code.required' => 'Each item must have an HS code.',
            'items.*.material.required' => 'Each item must have a material specified.',
            'items.*.weight_per_unit.required' => 'Each item must have a weight per unit.',
            'items.*.weight_unit.required' => 'Each item must have a weight unit (lb or kg).',
            'items.*.length.required' => 'Each item must have a length dimension.',
            'items.*.width.required' => 'Each item must have a width dimension.',
            'items.*.height.required' => 'Each item must have a height dimension.',
            'items.*.dimension_unit.required' => 'Each item must have a dimension unit (in or cm).',

            // Item file messages
            'items.*.files.*.image' => 'Each item file must be an image.',
            'items.*.files.*.mimes' => 'Item images must be in JPEG, PNG, or WebP format.',
            'items.*.files.*.max' => 'Each item image must not exceed 2MB.',
            'items.*.new_files.*.image' => 'Each new item file must be an image.',
            'items.*.new_files.*.mimes' => 'New item images must be in JPEG, PNG, or WebP format.',
            'items.*.new_files.*.max' => 'Each new item image must not exceed 2MB.',

            // Invoice messages
            'invoices.*.invoice_amount.min' => 'Invoice amount cannot be negative.',
            'invoices.*.files.*.mimes' => 'Invoice files must be JPEG, PNG, or PDF format.',
            'invoices.*.files.*.max' => 'Each invoice file must not exceed 5MB.',
            'invoices.*.file.mimes' => 'Invoice file must be JPEG, PNG, or PDF format.',
            'invoices.*.file.max' => 'Invoice file must not exceed 5MB.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Convert string booleans to actual booleans for item classification
        if ($this->has('items')) {
            $items = $this->items;
            foreach ($items as $key => $item) {
                $items[$key]['is_dangerous'] = filter_var($item['is_dangerous'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $items[$key]['is_fragile'] = filter_var($item['is_fragile'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $items[$key]['is_oversized'] = filter_var($item['is_oversized'] ?? false, FILTER_VALIDATE_BOOLEAN);
            }
            $this->merge(['items' => $items]);
        }
    }
}
