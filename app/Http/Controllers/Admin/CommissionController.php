<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CarrierCommissionSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class CommissionController extends Controller
{
    /**
     * Display the commission settings page
     */
    public function index()
    {
        $setting = CarrierCommissionSetting::getCurrent();

        return Inertia::render('Admin/Commission/Index', [
            'commission' => [
                'id' => $setting->id,
                'dhl_commission_percentage' => (float) $setting->dhl_commission_percentage,
                'fedex_commission_percentage' => (float) $setting->fedex_commission_percentage,
                'ups_commission_percentage' => (float) $setting->ups_commission_percentage,
                'dangerous_goods_charge' => (float) $setting->dangerous_goods_charge,
                'fragile_item_charge' => (float) $setting->fragile_item_charge,
                'oversized_item_charge' => (float) $setting->oversized_item_charge,
                'updated_at' => $setting->updated_at,
            ],
        ]);
    }

    /**
     * Update the commission percentages for all carriers
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'dhl_commission_percentage' => 'required|numeric|min:0|max:100',
            'fedex_commission_percentage' => 'required|numeric|min:0|max:100',
            'ups_commission_percentage' => 'required|numeric|min:0|max:100',
            'dangerous_goods_charge' => 'required|numeric|min:0',
            'fragile_item_charge' => 'required|numeric|min:0',
            'oversized_item_charge' => 'required|numeric|min:0',
        ]);

        $setting = CarrierCommissionSetting::updateCommissions($validated);

        return Redirect::route('admin.commission.index')
            ->with('success', 'Commission settings updated successfully');
    }
}
