<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Models\LoyaltyRule;
use App\Models\LoyaltyTier;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Exception;

class LoyaltyService
{
    /**
     * Earn loyalty points for a purchase with tier multiplier
     */
    public function earnPoints(Customer $customer, Transaction $transaction, float $amount): bool
    {
        try {
            $rule = LoyaltyRule::getDefaultRule();

            if (!$rule) {
                return false;
            }

            // Calculate base points
            $basePoints = $rule->calculateEarnPoints($amount);

            if ($basePoints <= 0) {
                return false;
            }

            // Apply tier multiplier
            $tier = $customer->tier;
            $points = $tier ? $tier->applyMultiplier($basePoints) : $basePoints;

            DB::transaction(function () use ($customer, $transaction, $points, $basePoints, $amount, $tier) {
                // Add points to customer
                $customer->addLoyaltyPoints($points);

                // Update lifetime spend for tier progression
                $customer->addLifetimeSpend($amount);

                // Build description with tier info
                $tierInfo = $tier ? " ({$tier->name} {$tier->earn_multiplier}x)" : '';
                $description = "Earned {$points} points{$tierInfo} for purchase of $" . number_format($amount, 2);

                // Record transaction
                LoyaltyTransaction::create([
                    'customer_id' => $customer->id,
                    'transaction_id' => $transaction->id,
                    'type' => 'earn',
                    'points' => $points,
                    'amount' => $amount,
                    'description' => $description
                ]);
            });

            return true;
        } catch (Exception $e) {
            \Log::error('LoyaltyService::earnPoints error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Calculate loyalty discount for given points
     */
    public function calculateLoyaltyDiscount(int $points, float $orderAmount): array
    {
        $rule = LoyaltyRule::getDefaultRule();

        if (!$rule) {
            return [
                'success' => false,
                'message' => 'Loyalty program is not configured.',
                'discount' => 0
            ];
        }

        if ($points <= 0) {
            return [
                'success' => false,
                'message' => 'Please enter a valid number of points.',
                'discount' => 0
            ];
        }

        // Calculate discount value
        $discountValue = $rule->calculateRedeemValue($points);

        // Don't discount more than order amount
        $discountValue = min($discountValue, $orderAmount);

        if ($discountValue <= 0) {
            return [
                'success' => false,
                'message' => 'Insufficient points for discount.',
                'discount' => 0
            ];
        }

        return [
            'success' => true,
            'message' => 'Loyalty discount calculated successfully!',
            'discount' => $discountValue,
            'points_required' => $points
        ];
    }

    /**
     * Redeem loyalty points
     */
    public function redeemPoints(Customer $customer, Transaction $transaction, int $points, float $discountAmount): bool
    {
        try {
            // Check if customer has enough points
            if (!$customer->hasEnoughLoyaltyPoints($points)) {
                return false;
            }

            DB::transaction(function () use ($customer, $transaction, $points, $discountAmount) {
                // Deduct points from customer
                $customer->deductLoyaltyPoints($points);

                // Record transaction
                LoyaltyTransaction::create([
                    'customer_id' => $customer->id,
                    'transaction_id' => $transaction->id,
                    'type' => 'redeem',
                    'points' => $points,
                    'amount' => $discountAmount,
                    'description' => "Redeemed {$points} points for $" . number_format($discountAmount, 2) . " discount"
                ]);
            });

            return true;
        } catch (Exception $e) {
            \Log::error('LoyaltyService::redeemPoints error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get customer loyalty summary including tier info
     */
    public function getUserLoyaltySummary(Customer $customer): array
    {
        $totalEarned = LoyaltyTransaction::where('customer_id', $customer->id)
            ->where('type', 'earn')
            ->sum('points');

        $totalRedeemed = LoyaltyTransaction::where('customer_id', $customer->id)
            ->where('type', 'redeem')
            ->sum('points');

        $totalDiscountEarned = LoyaltyTransaction::where('customer_id', $customer->id)
            ->where('type', 'redeem')
            ->sum('amount');

        $loyaltyRule = LoyaltyRule::getDefaultRule();
        $tier = $customer->tier;
        $tierProgress = $customer->getTierProgress();

        return [
            'current_points' => $customer->loyalty_points,
            'total_earned' => $totalEarned,
            'total_redeemed' => $totalRedeemed,
            'total_discount_earned' => $totalDiscountEarned,
            'net_points' => $totalEarned - $totalRedeemed,
            'lifetime_spend' => $customer->lifetime_spend ?? 0,
            'tier' => $tier ? [
                'name' => $tier->name,
                'slug' => $tier->slug,
                'color' => $tier->color,
                'earn_multiplier' => $tier->earn_multiplier,
            ] : null,
            'tier_progress' => [
                'next_tier' => $tierProgress['next_tier'] ? [
                    'name' => $tierProgress['next_tier']->name,
                    'min_spend' => $tierProgress['next_tier']->min_lifetime_spend,
                ] : null,
                'spend_remaining' => $tierProgress['spend_remaining'] ?? 0,
                'percentage' => $tierProgress['percentage'] ?? 0,
            ],
            'loyalty_rule' => $loyaltyRule ? [
                'spend_amount' => $loyaltyRule->spend_amount,
                'earn_points' => $loyaltyRule->earn_points,
                'redeem_points' => $loyaltyRule->redeem_points,
                'redeem_value' => $loyaltyRule->redeem_value
            ] : null
        ];
    }

    /**
     * Get customer loyalty transactions
     */
    public function getUserLoyaltyTransactions(Customer $customer, int $limit = 10): array
    {
        return LoyaltyTransaction::where('customer_id', $customer->id)
            ->with('transaction')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get loyalty program statistics
     */
    public function getLoyaltyStats(): array
    {
        $totalCustomers = Customer::where('loyalty_points', '>', 0)->count();
        $totalPointsIssued = LoyaltyTransaction::where('type', 'earn')->sum('points');
        $totalPointsRedeemed = LoyaltyTransaction::where('type', 'redeem')->sum('points');
        $totalDiscountGiven = LoyaltyTransaction::where('type', 'redeem')->sum('amount');

        return [
            'total_users_with_points' => $totalCustomers,
            'total_points_issued' => $totalPointsIssued,
            'total_points_redeemed' => $totalPointsRedeemed,
            'total_discount_given' => $totalDiscountGiven,
            'active_points' => $totalPointsIssued - $totalPointsRedeemed
        ];
    }

    /**
     * Get maximum redeemable points for customer and order amount
     */
    public function getMaxRedeemablePoints(Customer $customer, float $orderAmount): int
    {
        $rule = LoyaltyRule::getDefaultRule();

        if (!$rule) {
            return 0;
        }

        $maxRedeemable = $rule->getMaxRedeemablePoints($orderAmount);
        $customerPoints = $customer->loyalty_points;

        return min($maxRedeemable, $customerPoints);
    }

    /**
     * Process referral reward when a referred customer completes first purchase
     */
    public function processReferralReward(Customer $referrer, Customer $referee, int $referrerPoints = 100, int $refereePoints = 50): bool
    {
        try {
            DB::transaction(function () use ($referrer, $referee, $referrerPoints, $refereePoints) {
                // Award points to referrer
                $referrer->addLoyaltyPoints($referrerPoints);
                LoyaltyTransaction::create([
                    'customer_id' => $referrer->id,
                    'type' => 'earn',
                    'points' => $referrerPoints,
                    'description' => "Referral bonus: {$referee->name} completed their first purchase"
                ]);

                // Award points to referee
                $referee->addLoyaltyPoints($refereePoints);
                LoyaltyTransaction::create([
                    'customer_id' => $referee->id,
                    'type' => 'earn',
                    'points' => $refereePoints,
                    'description' => "Welcome bonus: Referred by {$referrer->name}"
                ]);
            });

            return true;
        } catch (Exception $e) {
            \Log::error('LoyaltyService::processReferralReward error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Award loyalty points for completing a ship request
     */
    public function awardPointsForShipRequest(Customer $customer, float $shipAmount, int $basePoints = 10): bool
    {
        try {
            $rule = LoyaltyRule::getDefaultRule();
            
            // Use rule-based points if available, otherwise use base points
            $points = $rule ? $rule->calculateEarnPoints($shipAmount) : $basePoints;
            
            // Apply tier multiplier
            $tier = $customer->tier;
            $finalPoints = $tier ? $tier->applyMultiplier($points) : $points;

            if ($finalPoints <= 0) {
                return false;
            }

            DB::transaction(function () use ($customer, $finalPoints, $tier) {
                // Add points to customer
                $customer->addLoyaltyPoints($finalPoints);

                // Build description with tier info
                $tierInfo = $tier ? " ({$tier->name} {$tier->earn_multiplier}x)" : '';
                $description = "Earned {$finalPoints} points{$tierInfo} for completing a ship request";

                // Record transaction
                LoyaltyTransaction::create([
                    'customer_id' => $customer->id,
                    'type' => 'earn',
                    'points' => $finalPoints,
                    'amount' => 0,
                    'description' => $description
                ]);
            });

            return true;
        } catch (Exception $e) {
            \Log::error('LoyaltyService::awardPointsForShipRequest error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check and award milestone rewards for shipment count
     */
    public function checkAndAwardMilestones(Customer $customer, string $milestoneType = 'shipment_count'): array
    {
        $awardedMilestones = [];

        try {
            // Get customer's current count based on milestone type
            $currentValue = $this->getCurrentMilestoneValue($customer, $milestoneType);

            if ($currentValue <= 0) {
                return $awardedMilestones;
            }

            // Get active milestone rules for this type
            $rules = \App\Models\LoyaltyMilestoneRule::active()
                ->forType($milestoneType)
                ->ordered()
                ->get();

            foreach ($rules as $rule) {
                // Check if customer has already achieved this milestone
                $alreadyAchieved = \App\Models\LoyaltyMilestone::where('customer_id', $customer->id)
                    ->where('milestone_type', $milestoneType)
                    ->where('milestone_value', $rule->milestone_value)
                    ->exists();

                if ($alreadyAchieved) {
                    continue;
                }

                // Check if customer has reached this milestone
                if ($currentValue >= $rule->milestone_value) {
                    DB::transaction(function () use ($customer, $rule, $milestoneType, &$awardedMilestones) {
                        // Award points
                        $customer->addLoyaltyPoints($rule->points_reward);

                        // Record milestone achievement
                        \App\Models\LoyaltyMilestone::create([
                            'customer_id' => $customer->id,
                            'milestone_type' => $milestoneType,
                            'milestone_value' => $rule->milestone_value,
                            'points_awarded' => $rule->points_reward,
                            'achieved_at' => now(),
                        ]);

                        // Record loyalty transaction
                        LoyaltyTransaction::create([
                            'customer_id' => $customer->id,
                            'type' => 'earn',
                            'points' => $rule->points_reward,
                            'amount' => 0,
                            'description' => "Milestone reward: {$rule->name} - {$rule->points_reward} points"
                        ]);

                        $awardedMilestones[] = [
                            'milestone' => $rule->name,
                            'points' => $rule->points_reward,
                            'value' => $rule->milestone_value,
                        ];
                    });
                }
            }

            return $awardedMilestones;
        } catch (Exception $e) {
            \Log::error('LoyaltyService::checkAndAwardMilestones error: ' . $e->getMessage());
            return $awardedMilestones;
        }
    }

    /**
     * Get current milestone value for a customer based on milestone type
     */
    private function getCurrentMilestoneValue(Customer $customer, string $milestoneType): int
    {
        switch ($milestoneType) {
            case 'shipment_count':
                return $customer->shipments()
                    ->where('status', '!=', 'cancelled')
                    ->where('invoice_status', 'paid')
                    ->count();

            case 'referral_count':
                // Count how many customers this customer has referred
                return Customer::where('referred_by_id', $customer->id)
                    ->whereHas('transactions')
                    ->count();

            case 'spend_amount':
                return (int) $customer->lifetime_spend;

            default:
                return 0;
        }
    }
}
