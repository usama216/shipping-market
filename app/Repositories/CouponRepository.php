<?php

namespace App\Repositories;

use App\Models\Coupon;
use App\Models\CouponUsage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CouponRepository
{
    /**
     * Get all coupons with pagination
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return Coupon::withCount('usages')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get active coupons
     */
    public function getActiveCoupons(): Collection
    {
        return Coupon::active()
            ->valid()
            ->available()
            ->get();
    }

    /**
     * Find coupon by code
     */
    public function findByCode(string $code): ?Coupon
    {
        return Coupon::where('code', $code)->first();
    }

    /**
     * Create new coupon
     */
    public function create(array $data): Coupon
    {
        return Coupon::create($data);
    }

    /**
     * Update coupon
     */
    public function update(Coupon $coupon, array $data): bool
    {
        return $coupon->update($data);
    }

    /**
     * Delete coupon
     */
    public function delete(Coupon $coupon): bool
    {
        return $coupon->delete();
    }

    /**
     * Get coupon usage statistics
     */
    public function getUsageStats(): array
    {
        $totalCoupons = Coupon::count();
        $activeCoupons = Coupon::active()->count();
        $expiredCoupons = Coupon::where('expiry_date', '<', now())->count();
        $totalUsage = CouponUsage::count();

        return [
            'total_coupons' => $totalCoupons,
            'active_coupons' => $activeCoupons,
            'expired_coupons' => $expiredCoupons,
            'total_usage' => $totalUsage
        ];
    }

    /**
     * Get most used coupons
     */
    public function getMostUsedCoupons(int $limit = 10): Collection
    {
        return Coupon::withCount('usages')
            ->orderBy('usages_count', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get coupons expiring soon
     */
    public function getExpiringSoon(int $days = 7): Collection
    {
        return Coupon::where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('is_active', true)
            ->get();
    }

    /**
     * Search coupons
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return Coupon::where('code', 'like', "%{$query}%")
            ->orWhere('description', 'like', "%{$query}%")
            ->withCount('usages')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
