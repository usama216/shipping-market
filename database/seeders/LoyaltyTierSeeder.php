<?php

namespace Database\Seeders;

use App\Models\LoyaltyTier;
use Illuminate\Database\Seeder;

class LoyaltyTierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiers = [
            [
                'name' => 'Bronze',
                'slug' => 'bronze',
                'min_lifetime_spend' => 0,
                'earn_multiplier' => 1.00,
                'color' => '#CD7F32',
                'icon' => 'medal-bronze',
                'sort_order' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Silver',
                'slug' => 'silver',
                'min_lifetime_spend' => 500,
                'earn_multiplier' => 1.25,
                'color' => '#C0C0C0',
                'icon' => 'medal-silver',
                'sort_order' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Gold',
                'slug' => 'gold',
                'min_lifetime_spend' => 2000,
                'earn_multiplier' => 1.50,
                'color' => '#FFD700',
                'icon' => 'medal-gold',
                'sort_order' => 3,
                'is_active' => true,
            ],
        ];

        foreach ($tiers as $tier) {
            LoyaltyTier::updateOrCreate(
                ['slug' => $tier['slug']],
                $tier
            );
        }
    }
}
