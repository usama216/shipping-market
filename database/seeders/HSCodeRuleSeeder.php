<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HSCodeRule;

class HSCodeRuleSeeder extends Seeder
{
    /**
     * Seed comprehensive HS code rules
     */
    public function run(): void
    {
        $rules = [
            // ============================================
            // APPAREL - DRESSES
            // ============================================
            [
                'category' => 'apparel',
                'keywords' => ['dress', 'gown', 'frock', 'clothing', 'clothes', 'apparel'],
                'materials' => ['cotton', 'silk', 'polyester', 'fabric'],
                'usage_terms' => ['clothing', 'apparel', 'fashion'],
                'gender' => 'female',
                'suggested_hs_code' => '6204.44.00',
                'confidence_score' => 0.85,
                'priority' => 100,
                'is_active' => true,
                'notes' => 'Women\'s or girls\' dresses',
            ],
            // ============================================
            // APPAREL - SWEATERS
            // ============================================
            [
                'category' => 'apparel',
                'keywords' => ['sweater', 'pullover', 'jumper', 'cardigan', 'knitwear'],
                'materials' => ['wool', 'cotton', 'synthetic', 'cashmere'],
                'usage_terms' => ['clothing', 'apparel', 'warmth'],
                'gender' => 'unisex',
                'suggested_hs_code' => '6110.20.00',
                'confidence_score' => 0.85,
                'priority' => 100,
                'is_active' => true,
                'notes' => 'Sweaters and pullovers',
            ],
            // ============================================
            // APPAREL - T-SHIRTS
            // ============================================
            [
                'category' => 'apparel',
                'keywords' => ['t-shirt', 'tshirt', 'tee', 'shirt', 'top'],
                'materials' => ['cotton', 'polyester', 'fabric'],
                'usage_terms' => ['clothing', 'apparel', 'casual'],
                'gender' => 'unisex',
                'suggested_hs_code' => '6109.10.00',
                'confidence_score' => 0.80,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'T-shirts, singlets and other vests',
            ],
            // ============================================
            // APPAREL - PANTS/TROUSERS
            // ============================================
            [
                'category' => 'apparel',
                'keywords' => ['pants', 'trousers', 'shorts'],
                'materials' => ['cotton', 'denim', 'synthetic', 'fabric'],
                'usage_terms' => ['clothing', 'apparel'],
                'gender' => 'unisex',
                'suggested_hs_code' => '6203.42.00',
                'confidence_score' => 0.75,
                'priority' => 80,
                'is_active' => true,
                'notes' => 'Men\'s or boys\' trousers',
            ],
            // ============================================
            // APPAREL - SHIRTS/BLOUSES
            // ============================================
            [
                'category' => 'apparel',
                'keywords' => ['blouse', 'shirt', 'top', 'women shirt', 'ladies shirt'],
                'materials' => ['cotton', 'silk', 'polyester', 'fabric'],
                'usage_terms' => ['clothing', 'apparel', 'fashion'],
                'gender' => 'female',
                'suggested_hs_code' => '6206.30.00',
                'confidence_score' => 0.80,
                'priority' => 85,
                'is_active' => true,
                'notes' => 'Women\'s or girls\' blouses, shirts and shirt-blouses',
            ],
            [
                'category' => 'apparel',
                'keywords' => ['shirt', 'dress shirt', 'men shirt', 'formal shirt'],
                'materials' => ['cotton', 'fabric'],
                'usage_terms' => ['clothing', 'apparel', 'formal'],
                'gender' => 'male',
                'suggested_hs_code' => '6205.20.00',
                'confidence_score' => 0.80,
                'priority' => 85,
                'is_active' => true,
                'notes' => 'Men\'s or boys\' shirts',
            ],
            // ============================================
            // APPAREL - UNDERWEAR
            // ============================================
            [
                'category' => 'apparel',
                'keywords' => ['underwear', 'lingerie', 'bra', 'panties', 'underpants'],
                'materials' => ['cotton', 'synthetic', 'fabric'],
                'usage_terms' => ['clothing', 'apparel', 'underwear'],
                'gender' => 'unisex',
                'suggested_hs_code' => '6208.91.00',
                'confidence_score' => 0.75,
                'priority' => 70,
                'is_active' => true,
                'notes' => 'Women\'s or girls\' underwear',
            ],
            // ============================================
            // APPAREL - SWIMWEAR
            // ============================================
            [
                'category' => 'apparel',
                'keywords' => ['swimsuit', 'bikini', 'swimwear', 'bathing suit', 'swim'],
                'materials' => ['synthetic', 'nylon', 'spandex', 'fabric'],
                'usage_terms' => ['clothing', 'apparel', 'swimming', 'beach'],
                'gender' => 'unisex',
                'suggested_hs_code' => '6211.12.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Swimwear',
            ],
            // ============================================
            // FOOTWEAR
            // ============================================
            [
                'category' => 'footwear',
                'keywords' => ['shoe', 'sneaker', 'boot', 'sandal', 'footwear'],
                'materials' => ['leather', 'rubber', 'fabric', 'synthetic'],
                'usage_terms' => ['personal', 'sports', 'casual'],
                'gender' => null,
                'suggested_hs_code' => '6403.99.90',
                'confidence_score' => 0.80,
                'priority' => 100,
                'is_active' => true,
                'notes' => 'Footwear',
            ],
            [
                'category' => 'footwear',
                'keywords' => ['running shoe', 'athletic shoe', 'sports shoe', 'trainer'],
                'materials' => ['rubber', 'synthetic', 'fabric'],
                'usage_terms' => ['sports', 'athletic', 'running', 'exercise'],
                'gender' => null,
                'suggested_hs_code' => '6404.19.00',
                'confidence_score' => 0.85,
                'priority' => 95,
                'is_active' => true,
                'notes' => 'Athletic footwear',
            ],
            [
                'category' => 'footwear',
                'keywords' => ['dress shoe', 'formal shoe', 'leather shoe', 'oxford'],
                'materials' => ['leather', 'rubber'],
                'usage_terms' => ['formal', 'dress', 'business'],
                'gender' => null,
                'suggested_hs_code' => '6403.51.00',
                'confidence_score' => 0.80,
                'priority' => 85,
                'is_active' => true,
                'notes' => 'Leather footwear',
            ],
            // ============================================
            // ELECTRONICS - COMPUTERS
            // ============================================
            [
                'category' => 'electronics',
                'keywords' => ['laptop', 'notebook', 'computer'],
                'materials' => ['plastic', 'metal', 'aluminum'],
                'usage_terms' => ['personal', 'business', 'consumer'],
                'gender' => null,
                'suggested_hs_code' => '8471.30.01',
                'confidence_score' => 0.90,
                'priority' => 100,
                'is_active' => true,
                'notes' => 'Portable automatic data processing machines',
            ],
            [
                'category' => 'electronics',
                'keywords' => ['tablet', 'ipad', 'surface', 'android tablet'],
                'materials' => ['plastic', 'metal', 'glass'],
                'usage_terms' => ['personal', 'business', 'consumer', 'computing'],
                'gender' => null,
                'suggested_hs_code' => '8471.30.02',
                'confidence_score' => 0.90,
                'priority' => 100,
                'is_active' => true,
                'notes' => 'Tablets',
            ],
            // ============================================
            // ELECTRONICS - PHONES
            // ============================================
            [
                'category' => 'electronics',
                'keywords' => ['phone', 'smartphone', 'mobile', 'cell phone', 'iphone', 'android'],
                'materials' => ['glass', 'metal', 'plastic'],
                'usage_terms' => ['communication', 'personal', 'consumer'],
                'gender' => null,
                'suggested_hs_code' => '8517.12.00',
                'confidence_score' => 0.90,
                'priority' => 100,
                'is_active' => true,
                'notes' => 'Smartphones',
            ],
            // ============================================
            // ELECTRONICS - AUDIO
            // ============================================
            [
                'category' => 'electronics',
                'keywords' => ['headphone', 'earphone', 'earbud', 'audio', 'headset'],
                'materials' => ['plastic', 'metal', 'fabric'],
                'usage_terms' => ['audio', 'music', 'communication'],
                'gender' => null,
                'suggested_hs_code' => '8518.30.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Headphones and earphones',
            ],
            [
                'category' => 'electronics',
                'keywords' => ['speaker', 'bluetooth speaker', 'wireless speaker', 'audio speaker'],
                'materials' => ['plastic', 'metal', 'fabric'],
                'usage_terms' => ['audio', 'music', 'entertainment'],
                'gender' => null,
                'suggested_hs_code' => '8518.22.00',
                'confidence_score' => 0.80,
                'priority' => 85,
                'is_active' => true,
                'notes' => 'Loudspeakers',
            ],
            // ============================================
            // ELECTRONICS - DISPLAYS
            // ============================================
            [
                'category' => 'electronics',
                'keywords' => ['monitor', 'display', 'screen', 'computer monitor', 'lcd', 'led'],
                'materials' => ['plastic', 'metal', 'glass'],
                'usage_terms' => ['electronics', 'display', 'computing'],
                'gender' => null,
                'suggested_hs_code' => '8528.72.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Monitors and displays',
            ],
            [
                'category' => 'electronics',
                'keywords' => ['tv', 'television', 'smart tv', 'led tv', 'lcd tv'],
                'materials' => ['plastic', 'metal', 'glass'],
                'usage_terms' => ['electronics', 'display', 'entertainment'],
                'gender' => null,
                'suggested_hs_code' => '8528.73.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Televisions',
            ],
            // ============================================
            // ELECTRONICS - CAMERAS
            // ============================================
            [
                'category' => 'electronics',
                'keywords' => ['camera', 'digital camera', 'dslr', 'mirrorless'],
                'materials' => ['plastic', 'metal', 'glass'],
                'usage_terms' => ['photography', 'imaging', 'consumer'],
                'gender' => null,
                'suggested_hs_code' => '8525.80.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Digital cameras',
            ],
            // ============================================
            // ELECTRONICS - APPLIANCES
            // ============================================
            [
                'category' => 'electronics',
                'keywords' => ['microwave', 'oven', 'microwave oven', 'cooking', 'appliance'],
                'materials' => ['metal', 'plastic', 'glass'],
                'usage_terms' => ['kitchen', 'cooking', 'home'],
                'gender' => null,
                'suggested_hs_code' => '8516.50.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Microwave ovens',
            ],
            [
                'category' => 'electronics',
                'keywords' => ['refrigerator', 'fridge', 'freezer', 'appliance'],
                'materials' => ['metal', 'plastic'],
                'usage_terms' => ['kitchen', 'home', 'appliance'],
                'gender' => null,
                'suggested_hs_code' => '8418.10.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Refrigerators',
            ],
            // ============================================
            // ACCESSORIES - BAGS
            // ============================================
            [
                'category' => 'accessories',
                'keywords' => ['handbag', 'purse', 'bag', 'clutch', 'tote'],
                'materials' => ['leather', 'fabric', 'synthetic', 'canvas'],
                'usage_terms' => ['personal', 'fashion', 'accessory'],
                'gender' => 'female',
                'suggested_hs_code' => '4202.12.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Handbags',
            ],
            [
                'category' => 'accessories',
                'keywords' => ['backpack', 'rucksack', 'school bag', 'travel bag'],
                'materials' => ['fabric', 'leather', 'canvas', 'nylon'],
                'usage_terms' => ['travel', 'school', 'personal', 'luggage'],
                'gender' => null,
                'suggested_hs_code' => '4202.92.00',
                'confidence_score' => 0.80,
                'priority' => 85,
                'is_active' => true,
                'notes' => 'Backpacks and travel bags',
            ],
            [
                'category' => 'accessories',
                'keywords' => ['wallet', 'purse', 'money clip', 'card holder'],
                'materials' => ['leather', 'fabric', 'synthetic'],
                'usage_terms' => ['personal', 'accessory', 'fashion'],
                'gender' => null,
                'suggested_hs_code' => '4202.31.00',
                'confidence_score' => 0.75,
                'priority' => 75,
                'is_active' => true,
                'notes' => 'Wallets and purses',
            ],
            [
                'category' => 'accessories',
                'keywords' => ['belt', 'leather belt', 'fashion belt'],
                'materials' => ['leather', 'fabric', 'metal'],
                'usage_terms' => ['clothing', 'accessory', 'fashion'],
                'gender' => null,
                'suggested_hs_code' => '4203.30.00',
                'confidence_score' => 0.75,
                'priority' => 75,
                'is_active' => true,
                'notes' => 'Belts',
            ],
            // ============================================
            // ACCESSORIES - WATCHES
            // ============================================
            [
                'category' => 'accessories',
                'keywords' => ['watch', 'wristwatch', 'timepiece', 'luxury watch'],
                'materials' => ['metal', 'gold', 'silver', 'precious metal'],
                'usage_terms' => ['time', 'fashion', 'luxury'],
                'gender' => null,
                'suggested_hs_code' => '9102.11.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Precious metal watches',
            ],
            [
                'category' => 'accessories',
                'keywords' => ['watch', 'wristwatch', 'timepiece'],
                'materials' => ['metal', 'steel', 'titanium'],
                'usage_terms' => ['time', 'fashion'],
                'gender' => null,
                'suggested_hs_code' => '9102.12.00',
                'confidence_score' => 0.80,
                'priority' => 85,
                'is_active' => true,
                'notes' => 'Base metal watches',
            ],
            // ============================================
            // ACCESSORIES - EYEWEAR
            // ============================================
            [
                'category' => 'accessories',
                'keywords' => ['sunglasses', 'eyeglasses', 'glasses', 'spectacles'],
                'materials' => ['plastic', 'metal', 'glass'],
                'usage_terms' => ['vision', 'eye protection', 'fashion'],
                'gender' => null,
                'suggested_hs_code' => '9004.10.00',
                'confidence_score' => 0.80,
                'priority' => 80,
                'is_active' => true,
                'notes' => 'Sunglasses and eyeglasses',
            ],
            // ============================================
            // TOYS AND GAMES
            // ============================================
            [
                'category' => 'toys',
                'keywords' => ['toy', 'game', 'doll', 'action figure', 'puzzle', 'board game'],
                'materials' => ['plastic', 'fabric', 'wood', 'metal'],
                'usage_terms' => ['recreational', 'children', 'entertainment'],
                'gender' => null,
                'suggested_hs_code' => '9503.00.00',
                'confidence_score' => 0.80,
                'priority' => 80,
                'is_active' => true,
                'notes' => 'Toys and games',
            ],
            [
                'category' => 'toys',
                'keywords' => ['video game', 'console', 'playstation', 'xbox', 'nintendo', 'gaming'],
                'materials' => ['plastic', 'metal'],
                'usage_terms' => ['gaming', 'entertainment', 'recreational'],
                'gender' => null,
                'suggested_hs_code' => '9504.30.00',
                'confidence_score' => 0.90,
                'priority' => 95,
                'is_active' => true,
                'notes' => 'Video game consoles',
            ],
            [
                'category' => 'toys',
                'keywords' => ['bicycle', 'bike', 'cycle', 'pedal cycle'],
                'materials' => ['metal', 'steel', 'aluminum'],
                'usage_terms' => ['transportation', 'sports', 'recreational'],
                'gender' => null,
                'suggested_hs_code' => '8712.00.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Bicycles',
            ],
            // ============================================
            // FURNITURE
            // ============================================
            [
                'category' => 'furniture',
                'keywords' => ['mattress', 'bed', 'sleeping', 'bedding', 'pillow'],
                'materials' => ['foam', 'spring', 'memory foam', 'latex', 'fabric'],
                'usage_terms' => ['furniture', 'home', 'sleeping'],
                'gender' => null,
                'suggested_hs_code' => '9401.40.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Mattresses and bedding',
            ],
            [
                'category' => 'furniture',
                'keywords' => ['chair', 'office chair', 'desk chair', 'furniture'],
                'materials' => ['metal', 'plastic', 'wood', 'fabric'],
                'usage_terms' => ['furniture', 'office', 'home'],
                'gender' => null,
                'suggested_hs_code' => '9401.30.00',
                'confidence_score' => 0.80,
                'priority' => 85,
                'is_active' => true,
                'notes' => 'Chairs',
            ],
            [
                'category' => 'furniture',
                'keywords' => ['table', 'desk', 'dining table', 'coffee table'],
                'materials' => ['wood', 'metal', 'glass', 'plastic'],
                'usage_terms' => ['furniture', 'home', 'office'],
                'gender' => null,
                'suggested_hs_code' => '9403.30.00',
                'confidence_score' => 0.80,
                'priority' => 85,
                'is_active' => true,
                'notes' => 'Tables',
            ],
            // ============================================
            // COSMETICS/BEAUTY
            // ============================================
            [
                'category' => 'cosmetics',
                'keywords' => ['cosmetic', 'makeup', 'beauty', 'skincare', 'cream', 'lotion'],
                'materials' => ['chemical', 'organic'],
                'usage_terms' => ['beauty', 'personal care', 'skincare'],
                'gender' => null,
                'suggested_hs_code' => '3304.99.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Beauty and skincare products',
            ],
            [
                'category' => 'cosmetics',
                'keywords' => ['perfume', 'cologne', 'fragrance', 'toilet water'],
                'materials' => ['chemical', 'alcohol'],
                'usage_terms' => ['beauty', 'personal care', 'fragrance'],
                'gender' => null,
                'suggested_hs_code' => '3303.00.00',
                'confidence_score' => 0.90,
                'priority' => 95,
                'is_active' => true,
                'notes' => 'Perfumes and fragrances',
            ],
            [
                'category' => 'cosmetics',
                'keywords' => ['lipstick', 'mascara', 'eyeliner', 'foundation', 'makeup'],
                'materials' => ['chemical', 'wax', 'pigment'],
                'usage_terms' => ['beauty', 'makeup', 'cosmetic'],
                'gender' => null,
                'suggested_hs_code' => '3304.20.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Eye makeup and lip makeup',
            ],
            // ============================================
            // JEWELRY
            // ============================================
            [
                'category' => 'jewelry',
                'keywords' => ['jewelry', 'jewellery', 'necklace', 'ring', 'bracelet', 'earring', 'gold', 'silver'],
                'materials' => ['gold', 'silver', 'precious metal', 'diamond'],
                'usage_terms' => ['fashion', 'accessory', 'luxury'],
                'gender' => null,
                'suggested_hs_code' => '7113.19.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Precious metal jewelry',
            ],
            [
                'category' => 'jewelry',
                'keywords' => ['costume jewelry', 'fashion jewelry', 'imitation jewelry', 'costume'],
                'materials' => ['metal', 'plastic', 'glass', 'crystal'],
                'usage_terms' => ['fashion', 'accessory'],
                'gender' => null,
                'suggested_hs_code' => '7117.19.00',
                'confidence_score' => 0.80,
                'priority' => 85,
                'is_active' => true,
                'notes' => 'Imitation jewelry',
            ],
            // ============================================
            // SPORTS EQUIPMENT
            // ============================================
            [
                'category' => 'sports',
                'keywords' => ['sports', 'exercise', 'gym', 'fitness', 'athletic', 'equipment'],
                'materials' => ['metal', 'plastic', 'rubber', 'fabric'],
                'usage_terms' => ['sports', 'fitness', 'exercise'],
                'gender' => null,
                'suggested_hs_code' => '9506.51.00',
                'confidence_score' => 0.80,
                'priority' => 85,
                'is_active' => true,
                'notes' => 'Sports and fitness equipment',
            ],
            [
                'category' => 'sports',
                'keywords' => ['ball', 'soccer ball', 'basketball', 'football', 'tennis ball'],
                'materials' => ['rubber', 'leather', 'plastic'],
                'usage_terms' => ['sports', 'recreational'],
                'gender' => null,
                'suggested_hs_code' => '9506.61.00',
                'confidence_score' => 0.85,
                'priority' => 90,
                'is_active' => true,
                'notes' => 'Sports balls',
            ],
            // ============================================
            // BOOKS/MEDIA
            // ============================================
            [
                'category' => 'media',
                'keywords' => ['book', 'novel', 'textbook', 'magazine', 'publication'],
                'materials' => ['paper', 'cardboard'],
                'usage_terms' => ['education', 'entertainment', 'reading'],
                'gender' => null,
                'suggested_hs_code' => '4901.99.00',
                'confidence_score' => 0.85,
                'priority' => 85,
                'is_active' => true,
                'notes' => 'Books and publications',
            ],
            // ============================================
            // FOOD/SUPPLEMENTS
            // ============================================
            [
                'category' => 'food',
                'keywords' => ['supplement', 'vitamin', 'protein', 'nutrition', 'health'],
                'materials' => ['organic', 'chemical'],
                'usage_terms' => ['health', 'nutrition', 'dietary'],
                'gender' => null,
                'suggested_hs_code' => '2106.90.00',
                'confidence_score' => 0.75,
                'priority' => 70,
                'is_active' => true,
                'notes' => 'Food supplements and vitamins',
            ],
        ];

        foreach ($rules as $rule) {
            HSCodeRule::updateOrCreate(
                [
                    'category' => $rule['category'],
                    'suggested_hs_code' => $rule['suggested_hs_code'],
                ],
                $rule
            );
        }

        $this->command->info('Seeded ' . count($rules) . ' HS code rules');
    }
}
