<?php

namespace Database\Seeders;

use App\Models\LoyaltyRule;
use Illuminate\Database\Seeder;

class LoyaltyRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $rules = [
            [
                'name' => 'Default Loyalty Program',
                'spend_amount' => 10.00,
                'earn_points' => 1,
                'redeem_points' => 100,
                'redeem_value' => 5.00,
                'is_active' => true,
            ],
            [
                'name' => 'Premium Loyalty Program',
                'spend_amount' => 5.00,
                'earn_points' => 1,
                'redeem_points' => 50,
                'redeem_value' => 3.00,
                'is_active' => false,
            ],
        ];

        foreach ($rules as $rule) {
            LoyaltyRule::firstOrCreate(
                ['name' => $rule['name']],
                $rule
            );
        }
    }
}
