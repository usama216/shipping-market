<?php

namespace Database\Seeders;

use App\Models\ProformaInvoiceOptions;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
                // Role & Permission must be seeded first
            RolePermissionSeeder::class,

                // User and system data
            UserSeeder::class,
            WarehouseSeeder::class,
            SpecialRequestSeeder::class,
            PreferredShipMethodSeeder::class,
            InternationalShippingOptionsSeeder::class,
            ShippingPreferenceOptionSeeder::class,
            PackingOptionsSeeder::class,
            ProformaInvoiceOptionsSeeder::class,
            LoginOptionSeeder::class,
            LoyaltyRuleSeeder::class,
            LoyaltyTierSeeder::class,
            CouponSeeder::class,
            CarrierServicesSeeder::class,
            CaribbeanLocationSeeder::class,
            BVIPostalCodeUpdateSeeder::class,
            FedExCaribbeanRulesSeeder::class,
        ]);
    }
}

