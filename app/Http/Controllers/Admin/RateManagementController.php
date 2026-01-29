<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RateMarkupRule;
use App\Services\ShippingRateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class RateManagementController extends Controller
{
    public function __construct(
        protected ShippingRateService $shippingRateService
    ) {
    }

    /**
     * Display the rate management dashboard
     */
    public function index()
    {
        $rules = RateMarkupRule::ordered()
            ->get()
            ->map(fn($rule) => $rule->toFrontendFormat());

        $stats = [
            'total_rules' => RateMarkupRule::count(),
            'active_rules' => RateMarkupRule::active()->count(),
            'carriers_covered' => RateMarkupRule::whereNotNull('carrier')
                ->distinct('carrier')
                ->count('carrier'),
        ];

        return Inertia::render('Admin/RateManagement/Index', [
            'rules' => $rules,
            'stats' => $stats,
            'carriers' => ['fedex', 'dhl', 'ups'],
        ]);
    }

    /**
     * Store a new markup rule
     */
    public function storeRule(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0|max:9999',
            'carrier' => 'nullable|string|in:fedex,dhl,ups',
            'service_code' => 'nullable|string|max:100',
            'min_weight' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0|gte:min_weight',
            'destination_country' => 'nullable|string|size:2',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0|max:100',
        ]);

        RateMarkupRule::create($validated);

        return Redirect::back()->with('success', 'Markup rule created successfully.');
    }

    /**
     * Update an existing markup rule
     */
    public function updateRule(Request $request, RateMarkupRule $rule)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0|max:9999',
            'carrier' => 'nullable|string|in:fedex,dhl,ups',
            'service_code' => 'nullable|string|max:100',
            'min_weight' => 'nullable|numeric|min:0',
            'max_weight' => 'nullable|numeric|min:0|gte:min_weight',
            'destination_country' => 'nullable|string|size:2',
            'is_active' => 'boolean',
            'priority' => 'integer|min:0|max:100',
        ]);

        $rule->update($validated);

        return Redirect::back()->with('success', 'Markup rule updated successfully.');
    }

    /**
     * Delete a markup rule
     */
    public function destroyRule(RateMarkupRule $rule)
    {
        $rule->delete();

        return Redirect::back()->with('success', 'Markup rule deleted.');
    }

    /**
     * Toggle rule active status
     */
    public function toggleRule(RateMarkupRule $rule)
    {
        $rule->update(['is_active' => !$rule->is_active]);

        return Redirect::back()->with(
            'success',
            $rule->is_active ? 'Rule activated.' : 'Rule deactivated.'
        );
    }

    /**
     * Simulate rate with markups applied
     */
    public function simulateRate(Request $request)
    {
        $validated = $request->validate([
            'carrier' => 'required|string|in:fedex,dhl,ups',
            'weight' => 'required|numeric|min:0.1|max:1000',
            'base_price' => 'required|numeric|min:0',
            'destination_country' => 'nullable|string|size:2',
        ]);

        $appliedRules = [];
        $totalMarkup = 0;

        $rules = RateMarkupRule::active()
            ->forCarrier($validated['carrier'])
            ->forWeight($validated['weight'])
            ->forDestination($validated['destination_country'] ?? null)
            ->ordered()
            ->get();

        foreach ($rules as $rule) {
            $markup = $rule->applyTo($validated['base_price']);
            $totalMarkup += $markup;
            $appliedRules[] = [
                'id' => $rule->id,
                'name' => $rule->name,
                'type' => $rule->type,
                'value' => $rule->value,
                'markup_amount' => round($markup, 2),
            ];
        }

        return response()->json([
            'base_price' => round($validated['base_price'], 2),
            'total_markup' => round($totalMarkup, 2),
            'final_price' => round($validated['base_price'] + $totalMarkup, 2),
            'applied_rules' => $appliedRules,
            'rules_count' => count($appliedRules),
        ]);
    }
}
