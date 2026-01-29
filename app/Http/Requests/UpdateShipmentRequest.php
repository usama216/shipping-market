<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
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
            'tracking_number' => 'nullable|string|max:100',
            'carrier_tracking_number' => 'nullable|string|max:100',
            'total_weight' => 'nullable|numeric|min:0',
            'total_price' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:pending,paid,processing,submitted,label_ready,picked_up,shipped,in_transit,out_for_delivery,delivered,cancelled,failed,returned,on_hold',
            'invoice_status' => 'nullable|in:pending,paid,unpaid',
            'declared_value' => 'nullable|numeric|min:0',
            // Export Compliance (DHL)
            'incoterm' => 'nullable|string|in:DAP,DDU,DDP,CIF,FOB',
            'us_filing_type' => 'nullable|string|max:50',
            'invoice_signature_name' => 'nullable|string|max:100',
            'invoice_signature_title' => 'nullable|string|max:20',
            'exporter_id' => 'nullable|string|max:50',
            'exporter_code' => 'nullable|string|max:20',
            // Custom Invoice (DHL Paperless Trade)
            'use_custom_invoice' => 'nullable|boolean',
            'custom_invoice_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120', // 5MB max
        ];
    }
}
