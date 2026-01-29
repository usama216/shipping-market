<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoyaltyRuleRequest;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyTier;
use App\Models\Customer;
use App\Repositories\LoyaltyRepository;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoyaltyController extends Controller
{
    public function __construct(
        private LoyaltyRepository $loyaltyRepository,
        private LoyaltyService $loyaltyService
    ) {
    }

    /**
     * Display loyalty program dashboard
     */
    public function index(): Response
    {
        $stats = $this->loyaltyRepository->getLoyaltyStats();
        $topUsers = $this->loyaltyRepository->getTopLoyaltyUsers();
        $recentTransactions = $this->loyaltyRepository->getRecentTransactions();

        // Get tier distribution
        $tiers = LoyaltyTier::active()->ordered()->get();
        $tierDistribution = [];

        foreach ($tiers as $tier) {
            $nextTier = LoyaltyTier::where('sort_order', '>', $tier->sort_order)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->first();

            $query = Customer::where('lifetime_spend', '>=', $tier->min_lifetime_spend);

            if ($nextTier) {
                $query->where('lifetime_spend', '<', $nextTier->min_lifetime_spend);
            }

            $tierDistribution[] = [
                'id' => $tier->id,
                'name' => $tier->name,
                'slug' => $tier->slug,
                'color' => $tier->color,
                'min_spend' => $tier->min_lifetime_spend,
                'earn_multiplier' => $tier->earn_multiplier,
                'customer_count' => $query->count(),
            ];
        }

        // Get active loyalty rule
        $activeRule = $this->loyaltyRepository->getActiveRule();

        return Inertia::render('Admin/Loyalty/Index', [
            'stats' => $stats,
            'topUsers' => $topUsers,
            'recentTransactions' => $recentTransactions,
            'tierDistribution' => $tierDistribution,
            'tiers' => $tiers,
            'activeRule' => $activeRule,
        ]);
    }

    /**
     * Display loyalty rules management
     */
    public function rules(): Response
    {
        $rules = $this->loyaltyRepository->getAllRules();
        $activeRule = $this->loyaltyRepository->getActiveRule();

        return Inertia::render('Admin/Loyalty/Rules', [
            'rules' => $rules,
            'activeRule' => $activeRule
        ]);
    }

    /**
     * Store a new loyalty rule
     */
    public function storeRule(LoyaltyRuleRequest $request)
    {
        try {
            $data = $request->validated();

            // If this rule is being set as active, deactivate others
            if ($data['is_active']) {
                LoyaltyRule::where('is_active', true)->update(['is_active' => false]);
            }

            $this->loyaltyRepository->createRule($data);

            return redirect()->route('admin.loyalty.rules')
                ->with('success', 'Loyalty rule created successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create loyalty rule.']);
        }
    }

    /**
     * Update loyalty rule
     */
    public function updateRule(LoyaltyRuleRequest $request, LoyaltyRule $rule)
    {
        try {
            $data = $request->validated();

            // If this rule is being set as active, deactivate others
            if ($data['is_active']) {
                LoyaltyRule::where('id', '!=', $rule->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $this->loyaltyRepository->updateRule($rule, $data);

            // Redirect back to the page that made the request (index or rules page)
            $redirectTo = $request->input('redirect_to', 'admin.loyalty.rules');
            
            return redirect()->route($redirectTo)
                ->with('success', 'Loyalty rule updated successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update loyalty rule.']);
        }
    }

    /**
     * Delete loyalty rule
     */
    public function destroyRule(LoyaltyRule $rule)
    {
        try {
            $this->loyaltyRepository->deleteRule($rule);

            return redirect()->route('admin.loyalty.rules')
                ->with('success', 'Loyalty rule deleted successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete loyalty rule.']);
        }
    }

    /**
     * Display loyalty transactions
     */
    public function transactions(Request $request): Response
    {
        $query = $request->get('search');

        if ($query) {
            $transactions = $this->loyaltyRepository->searchTransactions($query);
        } else {
            $transactions = $this->loyaltyRepository->getAllTransactions();
        }

        return Inertia::render('Admin/Loyalty/Transactions', [
            'transactions' => $transactions,
            'search' => $query
        ]);
    }

    /**
     * Display customers with loyalty information
     */
    public function users(): Response
    {
        // Get ALL customers with their loyalty info
        $customers = Customer::orderBy('loyalty_points', 'desc')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->first_name . ' ' . $customer->last_name,
                    'email' => $customer->email,
                    'loyalty_points' => $customer->loyalty_points ?? 0,
                    'lifetime_spend' => $customer->lifetime_spend ?? 0,
                ];
            });

        return Inertia::render('Admin/Loyalty/Users', [
            'users' => $customers
        ]);
    }

    /**
     * Toggle loyalty rule status
     */
    public function toggleRuleStatus(LoyaltyRule $rule)
    {
        try {
            // If activating this rule, deactivate others
            if (!$rule->is_active) {
                LoyaltyRule::where('id', '!=', $rule->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $rule->update(['is_active' => !$rule->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Loyalty rule status updated successfully!',
                'is_active' => $rule->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update loyalty rule status.'
            ], 500);
        }
    }

    // ==========================================
    // Tier Management
    // ==========================================

    /**
     * Display loyalty tiers management
     */
    public function tiers(): Response
    {
        $tiers = LoyaltyTier::ordered()->get();

        return Inertia::render('Admin/Loyalty/Tiers', [
            'tiers' => $tiers
        ]);
    }

    /**
     * Store a new loyalty tier
     */
    public function storeTier(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:loyalty_tiers,slug',
            'min_lifetime_spend' => 'required|numeric|min:0',
            'earn_multiplier' => 'required|numeric|min:1|max:10',
            'color' => 'required|string|max:7',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            LoyaltyTier::create($request->all());

            return redirect()->route('admin.loyalty.tiers')
                ->with('success', 'Loyalty tier created successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create loyalty tier.']);
        }
    }

    /**
     * Update loyalty tier
     */
    public function updateTier(Request $request, LoyaltyTier $tier)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:loyalty_tiers,slug,' . $tier->id,
            'min_lifetime_spend' => 'required|numeric|min:0',
            'earn_multiplier' => 'required|numeric|min:1|max:10',
            'color' => 'required|string|max:7',
            'icon' => 'nullable|string|max:255',
            'sort_order' => 'required|integer|min:0',
            'is_active' => 'boolean',
        ]);

        try {
            $tier->update($request->all());

            return redirect()->route('admin.loyalty.tiers')
                ->with('success', 'Loyalty tier updated successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update loyalty tier.']);
        }
    }

    /**
     * Delete loyalty tier
     */
    public function destroyTier(LoyaltyTier $tier)
    {
        try {
            $tier->delete();

            return redirect()->route('admin.loyalty.tiers')
                ->with('success', 'Loyalty tier deleted successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete loyalty tier.']);
        }
    }

    // ==========================================
    // Point Management
    // ==========================================

    /**
     * Adjust (add/deduct) points for a customer
     */
    public function adjustPoints(Request $request, Customer $customer)
    {
        $request->validate([
            'points' => 'required|integer',
            'type' => 'required|in:add,deduct',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $points = abs($request->points);
            $type = $request->type;
            $reason = $request->reason;

            if ($type === 'add') {
                $customer->addLoyaltyPoints($points);
                $description = "Admin: Added {$points} points - {$reason}";
            } else {
                if (!$customer->hasEnoughLoyaltyPoints($points)) {
                    return back()->withErrors(['error' => 'Customer does not have enough points.']);
                }
                $customer->deductLoyaltyPoints($points);
                $description = "Admin: Deducted {$points} points - {$reason}";
            }

            // Record transaction
            \App\Models\LoyaltyTransaction::create([
                'customer_id' => $customer->id,
                'type' => $type === 'add' ? 'earn' : 'redeem',
                'points' => $points,
                'amount' => 0,
                'description' => $description,
            ]);

            return back()->with('success', "Successfully {$type}ed {$points} points.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to adjust points: ' . $e->getMessage()]);
        }
    }

    // ==========================================
    // Referral Management
    // ==========================================

    /**
     * Display referral dashboard
     */
    public function referrals(): Response
    {
        // Get customers who have referred others
        $referrers = Customer::whereHas('referrals')
            ->withCount('referrals')
            ->with([
                'referrals' => function ($query) {
                    $query->select('id', 'first_name', 'last_name', 'email', 'referred_by_id', 'created_at')
                        ->latest()
                        ->limit(5);
                }
            ])
            ->orderByDesc('referrals_count')
            ->limit(20)
            ->get();

        // Get recent referrals
        $recentReferrals = Customer::whereNotNull('referred_by_id')
            ->with('referrer:id,first_name,last_name,email')
            ->latest()
            ->limit(20)
            ->get();

        // Stats
        $stats = [
            'total_referrers' => Customer::whereHas('referrals')->count(),
            'total_referred' => Customer::whereNotNull('referred_by_id')->count(),
            'this_month_referrals' => Customer::whereNotNull('referred_by_id')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count(),
        ];

        return Inertia::render('Admin/Loyalty/Referrals', [
            'referrers' => $referrers,
            'recentReferrals' => $recentReferrals,
            'stats' => $stats,
        ]);
    }
}
