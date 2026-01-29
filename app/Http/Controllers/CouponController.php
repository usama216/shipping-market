<?php

namespace App\Http\Controllers;

use App\Http\Requests\CouponRequest;
use App\Models\Coupon;
use App\Repositories\CouponRepository;
use App\Services\CouponService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CouponController extends Controller
{
    public function __construct(
        private CouponRepository $couponRepository,
        private CouponService $couponService
    ) {
    }

    /**
     * Display a listing of coupons
     */
    public function index(Request $request): Response
    {
        $query = $request->get('search');

        if ($query) {
            $coupons = $this->couponRepository->search($query);
        } else {
            $coupons = $this->couponRepository->getAllPaginated();
        }

        $stats = $this->couponRepository->getUsageStats();

        $customers = \App\Models\Customer::query()
            ->select('id', 'first_name', 'last_name', 'email', 'suite')
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => trim($customer->first_name . ' ' . $customer->last_name),
                    'email' => $customer->email,
                    'suite' => $customer->suite,
                ];
            });

        return Inertia::render('Admin/Coupons/Index', [
            'coupons' => $coupons,
            'stats' => $stats,
            'search' => $query,
            'customers' => $customers
        ]);
    }

    /**
     * Store a newly created coupon
     */
    public function store(CouponRequest $request)
    {
        try {
            $data = $request->validated();

            // Generate unique code if not provided
            if (empty($data['code'])) {
                $data['code'] = $this->couponService->generateUniqueCode();
            }

            // Set default values for nullable fields
            $data['description'] = $data['description'] ?? '';
            
            // Handle selected_customer_ids - convert to array if provided
            if (isset($data['selected_customer_ids']) && is_array($data['selected_customer_ids'])) {
                $data['selected_customer_ids'] = array_filter($data['selected_customer_ids']); // Remove empty values
                $data['selected_customer_ids'] = !empty($data['selected_customer_ids']) ? $data['selected_customer_ids'] : null;
            } else {
                $data['selected_customer_ids'] = null;
            }

            $this->couponRepository->create($data);

            return redirect()->route('admin.coupons.index')
                ->with('success', 'Coupon created successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create coupon: ' . $e->getMessage()]);
        }
    }

    /**
     * Update the specified coupon
     */
    public function update(CouponRequest $request, Coupon $coupon)
    {
        try {
            $data = $request->validated();
            
            // Handle selected_customer_ids - convert to array if provided
            if (isset($data['selected_customer_ids']) && is_array($data['selected_customer_ids'])) {
                $data['selected_customer_ids'] = array_filter($data['selected_customer_ids']); // Remove empty values
                $data['selected_customer_ids'] = !empty($data['selected_customer_ids']) ? $data['selected_customer_ids'] : null;
            } else {
                $data['selected_customer_ids'] = null;
            }
            
            $this->couponRepository->update($coupon, $data);

            return redirect()->route('admin.coupons.index')
                ->with('success', 'Coupon updated successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to update coupon.']);
        }
    }

    /**
     * Remove the specified coupon
     */
    public function destroy(Coupon $coupon)
    {
        try {
            $this->couponRepository->delete($coupon);

            return redirect()->route('admin.coupons.index')
                ->with('success', 'Coupon deleted successfully!');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete coupon.']);
        }
    }

    /**
     * Toggle coupon status
     */
    public function toggleStatus(Coupon $coupon)
    {
        try {
            $coupon->update(['is_active' => !$coupon->is_active]);

            return response()->json([
                'success' => true,
                'message' => 'Coupon status updated successfully!',
                'is_active' => $coupon->is_active
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update coupon status.'
            ], 500);
        }
    }

    /**
     * Show coupon usage statistics
     */
    public function usageStats(): Response
    {
        $stats = $this->couponRepository->getUsageStats();
        $mostUsed = $this->couponRepository->getMostUsedCoupons();
        $expiringSoon = $this->couponRepository->getExpiringSoon();

        return Inertia::render('Admin/Coupons/Stats', [
            'stats' => $stats,
            'mostUsed' => $mostUsed,
            'expiringSoon' => $expiringSoon
        ]);
    }

    /**
     * Generate unique coupon code
     */
    public function generateCode()
    {
        $code = $this->couponService->generateUniqueCode();

        return response()->json([
            'success' => true,
            'code' => $code
        ]);
    }

    /**
     * Search customers for coupon assignment
     */
    public function searchCustomers(Request $request)
    {
        $search = $request->input('search', '');
        
        $customers = \App\Models\Customer::query()
            ->select('id', 'first_name', 'last_name', 'email', 'suite')
            ->where('is_active', 1)
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('suite', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"]);
            })
            ->orderBy('first_name')
            ->limit(100)
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => trim($customer->first_name . ' ' . $customer->last_name),
                    'email' => $customer->email,
                    'suite' => $customer->suite,
                ];
            });

        return response()->json([
            'customers' => $customers
        ]);
    }

    /**
     * Show the form for creating a new coupon
     */
    public function create(): Response
    {
        $customers = \App\Models\Customer::query()
            ->select('id', 'first_name', 'last_name', 'email', 'suite')
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => trim($customer->first_name . ' ' . $customer->last_name),
                    'email' => $customer->email,
                    'suite' => $customer->suite,
                ];
            });

        return Inertia::render('Admin/Coupons/Create', [
            'customers' => $customers
        ]);
    }

    /**
     * Show the form for editing the specified coupon
     */
    public function edit(Coupon $coupon): Response
    {
        $customers = \App\Models\Customer::query()
            ->select('id', 'first_name', 'last_name', 'email', 'suite')
            ->where('is_active', 1)
            ->orderBy('first_name')
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => trim($customer->first_name . ' ' . $customer->last_name),
                    'email' => $customer->email,
                    'suite' => $customer->suite,
                ];
            });

        return Inertia::render('Admin/Coupons/Edit', [
            'coupon' => $coupon,
            'customers' => $customers
        ]);
    }
}