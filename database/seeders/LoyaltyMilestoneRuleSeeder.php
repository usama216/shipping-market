<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\LoyaltyMilestoneRule;

class LoyaltyMilestoneRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $milestones = [
            [
                'name' => '5 Shipments Reward',
                'milestone_type' => 'shipment_count',
                'milestone_value' => 5,
                'points_reward' => 50,
                'description' => 'Complete 5 ship requests to unlock this reward',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => '10 Shipments Reward',
                'milestone_type' => 'shipment_count',
                'milestone_value' => 10,
                'points_reward' => 150,
                'description' => 'Complete 10 ship requests to unlock this reward',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => '20 Shipments Reward',
                'milestone_type' => 'shipment_count',
                'milestone_value' => 20,
                'points_reward' => 400,
                'description' => 'Complete 20 ship requests to unlock this reward',
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'name' => '50 Shipments Reward',
                'milestone_type' => 'shipment_count',
                'milestone_value' => 50,
                'points_reward' => 1250,
                'description' => 'Complete 50 ship requests to unlock this reward',
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'name' => '100 Shipments Reward',
                'milestone_type' => 'shipment_count',
                'milestone_value' => 100,
                'points_reward' => 3000,
                'description' => 'Complete 100 ship requests to unlock this reward',
                'is_active' => true,
                'sort_order' => 5,
            ],
        ];

        foreach ($milestones as $milestone) {
            LoyaltyMilestoneRule::updateOrCreate(
                [
                    'milestone_type' => $milestone['milestone_type'],
                    'milestone_value' => $milestone['milestone_value'],
                ],
                $milestone
            );
        }
    }
}
