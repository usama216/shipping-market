<?php

namespace Database\Seeders;

use App\Models\ProformaInvoiceOptions;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProformaInvoiceOptionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ProformaInvoiceOptions::truncate();
        $options = [
            [
                'title' => 'Enter Tax I.D.',
                'description' => 'Enter tax id for input',
                'is_text_input' => true,
            ],
            [
                'title' => 'Mark for "For Personal Use Only"',
                'description' => '',
                'is_text_input' => false,
            ],
            [
                'title' => 'Include proforma invoice with shipment',
                'description' => '',
                'is_text_input' => false,
            ],
        ];
        foreach ($options as $option) {
            ProformaInvoiceOptions::updateOrCreate(
                ['title' => $option['title']],
                $option
            );
        }
    }
}
