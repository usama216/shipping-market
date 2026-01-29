<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\HSCode;

class HSCodeSeeder extends Seeder
{
    /**
     * Seed comprehensive HS code database
     */
    public function run(): void
    {
        $hsCodes = [
            // ============================================
            // APPAREL - WOMEN'S CLOTHING
            // ============================================
            [
                'code' => '6204.44.00',
                'description' => 'Women\'s or girls\' dresses, of cotton',
                'chapter' => '62',
                'heading' => '6204',
                'subheading' => '44.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['dress', 'gown', 'frock', 'women dress', 'ladies dress', 'clothing', 'clothes', 'apparel', 'garment'],
                'materials' => ['cotton', 'silk', 'polyester', 'fabric', 'textile'],
            ],
            [
                'code' => '6204.62.00',
                'description' => 'Women\'s or girls\' trousers, bib and brace overalls, breeches and shorts, of cotton',
                'chapter' => '62',
                'heading' => '6204',
                'subheading' => '62.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['pants', 'trousers', 'shorts', 'women pants', 'ladies pants', 'clothing', 'apparel'],
                'materials' => ['cotton', 'denim', 'fabric'],
            ],
            [
                'code' => '6204.63.00',
                'description' => 'Women\'s or girls\' trousers, bib and brace overalls, breeches and shorts, of synthetic fibres',
                'chapter' => '62',
                'heading' => '6204',
                'subheading' => '63.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['pants', 'trousers', 'shorts', 'women', 'ladies', 'clothing', 'apparel'],
                'materials' => ['synthetic', 'polyester', 'nylon', 'fabric'],
            ],
            [
                'code' => '6202.13.00',
                'description' => 'Women\'s or girls\' overcoats, car coats, capes, cloaks, anoraks, wind-cheaters, wind-jackets, of wool or fine animal hair',
                'chapter' => '62',
                'heading' => '6202',
                'subheading' => '13.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['coat', 'overcoat', 'jacket', 'women', 'ladies', 'outerwear', 'clothing'],
                'materials' => ['wool', 'fabric'],
            ],
            [
                'code' => '6202.93.00',
                'description' => 'Women\'s or girls\' suits, ensembles, jackets, blazers, dresses, skirts, of synthetic fibres',
                'chapter' => '62',
                'heading' => '6202',
                'subheading' => '93.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['suit', 'women suit', 'ladies suit', 'formal', 'jacket', 'blazer', 'clothing'],
                'materials' => ['synthetic', 'fabric'],
            ],
            [
                'code' => '6204.32.00',
                'description' => 'Women\'s or girls\' suits, ensembles, jackets, blazers, dresses, skirts, of cotton',
                'chapter' => '62',
                'heading' => '6204',
                'subheading' => '32.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['jacket', 'blazer', 'women', 'ladies', 'clothing', 'apparel'],
                'materials' => ['cotton', 'fabric'],
            ],
            [
                'code' => '6204.52.00',
                'description' => 'Women\'s or girls\' skirts and divided skirts, of cotton',
                'chapter' => '62',
                'heading' => '6204',
                'subheading' => '52.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['skirt', 'women', 'ladies', 'clothing', 'apparel'],
                'materials' => ['cotton', 'fabric'],
            ],
            [
                'code' => '6204.53.00',
                'description' => 'Women\'s or girls\' skirts and divided skirts, of synthetic fibres',
                'chapter' => '62',
                'heading' => '6204',
                'subheading' => '53.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['skirt', 'women', 'ladies', 'clothing', 'apparel'],
                'materials' => ['synthetic', 'polyester', 'fabric'],
            ],

            // ============================================
            // APPAREL - MEN'S CLOTHING
            // ============================================
            [
                'code' => '6203.23.00',
                'description' => 'Men\'s or boys\' suits, ensembles, jackets, blazers, trousers, of cotton',
                'chapter' => '62',
                'heading' => '6203',
                'subheading' => '23.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['suit', 'men suit', 'jacket', 'blazer', 'formal', 'clothing'],
                'materials' => ['cotton', 'fabric'],
            ],
            [
                'code' => '6203.42.00',
                'description' => 'Men\'s or boys\' trousers, bib and brace overalls, breeches and shorts, of cotton',
                'chapter' => '62',
                'heading' => '6203',
                'subheading' => '42.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['pants', 'trousers', 'shorts', 'men', 'boys', 'jeans', 'clothing'],
                'materials' => ['cotton', 'denim', 'fabric'],
            ],
            [
                'code' => '6203.43.00',
                'description' => 'Men\'s or boys\' trousers, bib and brace overalls, breeches and shorts, of synthetic fibres',
                'chapter' => '62',
                'heading' => '6203',
                'subheading' => '43.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['pants', 'trousers', 'shorts', 'men', 'boys', 'clothing'],
                'materials' => ['synthetic', 'polyester', 'nylon'],
            ],
            [
                'code' => '6201.93.00',
                'description' => 'Men\'s or boys\' suits, ensembles, jackets, blazers, trousers, of synthetic fibres',
                'chapter' => '62',
                'heading' => '6201',
                'subheading' => '93.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['suit', 'men suit', 'formal', 'jacket', 'blazer', 'clothing'],
                'materials' => ['synthetic', 'fabric'],
            ],
            [
                'code' => '6201.12.00',
                'description' => 'Men\'s or boys\' overcoats, car coats, capes, cloaks, anoraks, wind-cheaters, wind-jackets, of wool or fine animal hair',
                'chapter' => '62',
                'heading' => '6201',
                'subheading' => '12.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['coat', 'overcoat', 'jacket', 'men', 'boys', 'outerwear', 'clothing'],
                'materials' => ['wool', 'fabric'],
            ],

            // ============================================
            // APPAREL - KNITTED/CROCHETED
            // ============================================
            [
                'code' => '6109.10.00',
                'description' => 'T-shirts, singlets and other vests, knitted or crocheted, of cotton',
                'chapter' => '61',
                'heading' => '6109',
                'subheading' => '10.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['t-shirt', 'tshirt', 'tee', 'shirt', 'top', 'singlet', 'vest', 'clothing'],
                'materials' => ['cotton', 'polyester', 'fabric'],
            ],
            [
                'code' => '6109.90.00',
                'description' => 'T-shirts, singlets and other vests, knitted or crocheted, of other textile materials',
                'chapter' => '61',
                'heading' => '6109',
                'subheading' => '90.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['t-shirt', 'tshirt', 'shirt', 'top', 'clothing', 'apparel'],
                'materials' => ['other', 'fabric', 'textile'],
            ],
            [
                'code' => '6110.20.00',
                'description' => 'Jerseys, pullovers, cardigans, waistcoats and similar articles, knitted or crocheted, of wool or fine animal hair',
                'chapter' => '61',
                'heading' => '6110',
                'subheading' => '20.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['sweater', 'pullover', 'jumper', 'cardigan', 'knitwear', 'clothing'],
                'materials' => ['wool', 'cotton', 'synthetic', 'cashmere'],
            ],
            [
                'code' => '6110.30.00',
                'description' => 'Jerseys, pullovers, cardigans, waistcoats and similar articles, knitted or crocheted, of man-made fibres',
                'chapter' => '61',
                'heading' => '6110',
                'subheading' => '30.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['sweater', 'pullover', 'jumper', 'cardigan', 'clothing'],
                'materials' => ['synthetic', 'polyester', 'acrylic'],
            ],
            [
                'code' => '6104.62.00',
                'description' => 'Women\'s or girls\' trousers, bib and brace overalls, breeches and shorts, knitted or crocheted, of cotton',
                'chapter' => '61',
                'heading' => '6104',
                'subheading' => '62.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['pants', 'trousers', 'shorts', 'women', 'ladies', 'clothing'],
                'materials' => ['cotton', 'fabric'],
            ],
            [
                'code' => '6103.42.00',
                'description' => 'Men\'s or boys\' trousers, bib and brace overalls, breeches and shorts, knitted or crocheted, of cotton',
                'chapter' => '61',
                'heading' => '6103',
                'subheading' => '42.00',
                'category' => 'apparel',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['pants', 'trousers', 'shorts', 'men', 'boys', 'clothing'],
                'materials' => ['cotton', 'fabric'],
            ],

            // ============================================
            // FOOTWEAR
            // ============================================
            [
                'code' => '6403.99.90',
                'description' => 'Other footwear, with outer soles of rubber, plastics, leather or composition leather and uppers of textile materials',
                'chapter' => '64',
                'heading' => '6403',
                'subheading' => '99.90',
                'category' => 'footwear',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['shoe', 'sneaker', 'boot', 'sandal', 'footwear', 'athletic'],
                'materials' => ['leather', 'rubber', 'fabric', 'synthetic', 'canvas'],
            ],
            [
                'code' => '6404.19.00',
                'description' => 'Footwear with outer soles of rubber or plastics and uppers of textile materials',
                'chapter' => '64',
                'heading' => '6404',
                'subheading' => '19.00',
                'category' => 'footwear',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['shoe', 'sneaker', 'athletic', 'sports', 'footwear'],
                'materials' => ['rubber', 'plastic', 'textile', 'fabric'],
            ],
            [
                'code' => '6403.51.00',
                'description' => 'Footwear with outer soles of rubber or plastics and uppers of leather',
                'chapter' => '64',
                'heading' => '6403',
                'subheading' => '51.00',
                'category' => 'footwear',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['shoe', 'boot', 'leather shoe', 'footwear'],
                'materials' => ['leather', 'rubber', 'plastic'],
            ],

            // ============================================
            // ELECTRONICS - COMPUTERS
            // ============================================
            [
                'code' => '8471.30.01',
                'description' => 'Portable automatic data processing machines, weighing not more than 10 kg, consisting of a central processing unit, a keyboard and a display',
                'chapter' => '84',
                'heading' => '8471',
                'subheading' => '30.01',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['laptop', 'notebook', 'computer', 'portable computer', 'macbook'],
                'materials' => ['plastic', 'metal', 'aluminum'],
            ],
            [
                'code' => '8471.30.02',
                'description' => 'Portable automatic data processing machines, weighing not more than 10 kg, consisting of a central processing unit, a keyboard and a display',
                'chapter' => '84',
                'heading' => '8471',
                'subheading' => '30.02',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['tablet', 'ipad', 'surface', 'electronic device'],
                'materials' => ['plastic', 'metal', 'glass'],
            ],
            [
                'code' => '8471.30.03',
                'description' => 'Other portable automatic data processing machines',
                'chapter' => '84',
                'heading' => '8471',
                'subheading' => '30.03',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['computer', 'laptop', 'notebook', 'data processing'],
                'materials' => ['plastic', 'metal'],
            ],

            // ============================================
            // ELECTRONICS - PHONES
            // ============================================
            [
                'code' => '8517.12.00',
                'description' => 'Telephones for cellular networks or for other wireless networks',
                'chapter' => '85',
                'heading' => '8517',
                'subheading' => '12.00',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['phone', 'smartphone', 'mobile', 'cell phone', 'iphone', 'android', 'samsung'],
                'materials' => ['glass', 'metal', 'plastic'],
            ],
            [
                'code' => '8517.11.00',
                'description' => 'Telephone sets, including telephones for cellular networks or for other wireless networks',
                'chapter' => '85',
                'heading' => '8517',
                'subheading' => '11.00',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['phone', 'telephone', 'mobile', 'smartphone'],
                'materials' => ['plastic', 'metal'],
            ],

            // ============================================
            // ELECTRONICS - DISPLAYS
            // ============================================
            [
                'code' => '8528.72.00',
                'description' => 'Monitors and projectors, not incorporating television reception apparatus',
                'chapter' => '85',
                'heading' => '8528',
                'subheading' => '72.00',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['monitor', 'display', 'screen', 'computer monitor', 'lcd', 'led'],
                'materials' => ['plastic', 'metal', 'glass'],
            ],
            [
                'code' => '8528.73.00',
                'description' => 'Reception apparatus for television, whether or not incorporating radio-broadcast receivers or sound or video recording or reproducing apparatus',
                'chapter' => '85',
                'heading' => '8528',
                'subheading' => '73.00',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['tv', 'television', 'smart tv', 'led tv', 'lcd tv'],
                'materials' => ['plastic', 'metal', 'glass'],
            ],

            // ============================================
            // ELECTRONICS - APPLIANCES
            // ============================================
            [
                'code' => '8516.50.00',
                'description' => 'Microwave ovens',
                'chapter' => '85',
                'heading' => '8516',
                'subheading' => '50.00',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['microwave', 'oven', 'microwave oven', 'cooking', 'appliance'],
                'materials' => ['metal', 'plastic', 'glass'],
            ],
            [
                'code' => '8516.60.00',
                'description' => 'Ovens; cookers, cooking plates, boiling rings, grillers and roasters',
                'chapter' => '85',
                'heading' => '8516',
                'subheading' => '60.00',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['oven', 'cooker', 'cooking', 'appliance', 'kitchen'],
                'materials' => ['metal', 'plastic'],
            ],
            [
                'code' => '8418.10.00',
                'description' => 'Combined refrigerator-freezers, fitted with separate external doors',
                'chapter' => '84',
                'heading' => '8418',
                'subheading' => '10.00',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['refrigerator', 'fridge', 'freezer', 'appliance'],
                'materials' => ['metal', 'plastic'],
            ],

            // ============================================
            // ACCESSORIES - BAGS
            // ============================================
            [
                'code' => '4202.12.00',
                'description' => 'Handbags, whether or not with shoulder strap, including those without handle, with outer surface of leather or of composition leather',
                'chapter' => '42',
                'heading' => '4202',
                'subheading' => '12.00',
                'category' => 'accessories',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['handbag', 'purse', 'bag', 'clutch', 'tote', 'shoulder bag'],
                'materials' => ['leather', 'fabric', 'synthetic', 'canvas'],
            ],
            [
                'code' => '4202.22.00',
                'description' => 'Handbags, whether or not with shoulder strap, including those without handle, with outer surface of plastic sheeting or of textile materials',
                'chapter' => '42',
                'heading' => '4202',
                'subheading' => '22.00',
                'category' => 'accessories',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['handbag', 'purse', 'bag', 'clutch', 'tote'],
                'materials' => ['plastic', 'textile', 'fabric'],
            ],
            [
                'code' => '4202.92.00',
                'description' => 'Other bags, with outer surface of leather or of composition leather',
                'chapter' => '42',
                'heading' => '4202',
                'subheading' => '92.00',
                'category' => 'accessories',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['bag', 'backpack', 'luggage', 'travel bag'],
                'materials' => ['leather', 'fabric'],
            ],

            // ============================================
            // ACCESSORIES - WATCHES
            // ============================================
            [
                'code' => '9102.11.00',
                'description' => 'Wrist-watches, pocket-watches and other watches, with case of precious metal or of metal clad with precious metal',
                'chapter' => '91',
                'heading' => '9102',
                'subheading' => '11.00',
                'category' => 'accessories',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['watch', 'wristwatch', 'timepiece', 'luxury watch'],
                'materials' => ['metal', 'gold', 'silver', 'precious metal'],
            ],
            [
                'code' => '9102.12.00',
                'description' => 'Wrist-watches, pocket-watches and other watches, with case of base metal',
                'chapter' => '91',
                'heading' => '9102',
                'subheading' => '12.00',
                'category' => 'accessories',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['watch', 'wristwatch', 'timepiece'],
                'materials' => ['metal', 'steel', 'titanium'],
            ],

            // ============================================
            // TOYS AND GAMES
            // ============================================
            [
                'code' => '9503.00.00',
                'description' => 'Tricycles, scooters, pedal cars and similar wheeled toys; dolls\' carriages; dolls; other toys; reduced-size ("scale") models and similar recreational models',
                'chapter' => '95',
                'heading' => '9503',
                'subheading' => '00.00',
                'category' => 'toys',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['toy', 'game', 'doll', 'action figure', 'puzzle', 'board game', 'tricycle', 'scooter'],
                'materials' => ['plastic', 'fabric', 'wood', 'metal'],
            ],
            [
                'code' => '9504.30.00',
                'description' => 'Video game consoles and machines',
                'chapter' => '95',
                'heading' => '9504',
                'subheading' => '30.00',
                'category' => 'toys',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['video game', 'console', 'playstation', 'xbox', 'nintendo', 'gaming'],
                'materials' => ['plastic', 'metal'],
            ],

            // ============================================
            // FURNITURE
            // ============================================
            [
                'code' => '9401.40.00',
                'description' => 'Mattress supports; articles of bedding and similar furnishing (for example, mattresses, quilts, eiderdowns, cushions, pouffes and pillows) fitted with springs or stuffed or internally fitted with any material or of cellular rubber or plastics',
                'chapter' => '94',
                'heading' => '9401',
                'subheading' => '40.00',
                'category' => 'furniture',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['mattress', 'bed', 'sleeping', 'bedding', 'pillow'],
                'materials' => ['foam', 'spring', 'memory foam', 'latex', 'fabric'],
            ],
            [
                'code' => '9403.20.00',
                'description' => 'Other metal furniture',
                'chapter' => '94',
                'heading' => '9403',
                'subheading' => '20.00',
                'category' => 'furniture',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['furniture', 'chair', 'table', 'desk', 'metal furniture'],
                'materials' => ['metal', 'steel'],
            ],
            [
                'code' => '9403.30.00',
                'description' => 'Other wooden furniture',
                'chapter' => '94',
                'heading' => '9403',
                'subheading' => '30.00',
                'category' => 'furniture',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['furniture', 'chair', 'table', 'desk', 'wooden furniture', 'wood'],
                'materials' => ['wood', 'wooden'],
            ],

            // ============================================
            // MEDICAL/PHARMACEUTICALS
            // ============================================
            [
                'code' => '3004.90.00',
                'description' => 'Medicaments (excluding goods of heading 3002, 3005 or 3006) consisting of mixed or unmixed products for therapeutic or prophylactic uses, put up in measured doses or in forms or packings for retail sale',
                'chapter' => '30',
                'heading' => '3004',
                'subheading' => '90.00',
                'category' => 'medical',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['medicine', 'pharmaceutical', 'drug', 'pill', 'tablet', 'capsule', 'medication'],
                'materials' => ['chemical', 'organic'],
            ],
            [
                'code' => '9018.90.00',
                'description' => 'Instruments and appliances used in medical, surgical, dental or veterinary sciences',
                'chapter' => '90',
                'heading' => '9018',
                'subheading' => '90.00',
                'category' => 'medical',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['medical', 'surgical', 'dental', 'instrument', 'appliance', 'equipment'],
                'materials' => ['metal', 'plastic'],
            ],

            // ============================================
            // COSMETICS/BEAUTY
            // ============================================
            [
                'code' => '3304.99.00',
                'description' => 'Beauty or make-up preparations and preparations for the care of the skin',
                'chapter' => '33',
                'heading' => '3304',
                'subheading' => '99.00',
                'category' => 'cosmetics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['cosmetic', 'makeup', 'beauty', 'skincare', 'cream', 'lotion'],
                'materials' => ['chemical', 'organic'],
            ],
            [
                'code' => '3303.00.00',
                'description' => 'Perfumes and toilet waters',
                'chapter' => '33',
                'heading' => '3303',
                'subheading' => '00.00',
                'category' => 'cosmetics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['perfume', 'cologne', 'fragrance', 'toilet water'],
                'materials' => ['chemical', 'alcohol'],
            ],

            // ============================================
            // JEWELRY
            // ============================================
            [
                'code' => '7113.19.00',
                'description' => 'Articles of jewellery and parts thereof, of precious metal or of metal clad with precious metal',
                'chapter' => '71',
                'heading' => '7113',
                'subheading' => '19.00',
                'category' => 'jewelry',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['jewelry', 'jewellery', 'necklace', 'ring', 'bracelet', 'earring', 'gold', 'silver'],
                'materials' => ['gold', 'silver', 'precious metal'],
            ],
            [
                'code' => '7117.19.00',
                'description' => 'Imitation jewellery',
                'chapter' => '71',
                'heading' => '7117',
                'subheading' => '19.00',
                'category' => 'jewelry',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['jewelry', 'jewellery', 'costume jewelry', 'fashion jewelry', 'imitation'],
                'materials' => ['metal', 'plastic', 'glass'],
            ],

            // ============================================
            // SPORTS EQUIPMENT
            // ============================================
            [
                'code' => '9506.51.00',
                'description' => 'Articles and equipment for general physical exercise, gymnastics or athletics',
                'chapter' => '95',
                'heading' => '9506',
                'subheading' => '51.00',
                'category' => 'sports',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['sports', 'exercise', 'gym', 'fitness', 'athletic', 'equipment'],
                'materials' => ['metal', 'plastic', 'rubber'],
            ],
            [
                'code' => '9506.61.00',
                'description' => 'Articles and equipment for outdoor games',
                'chapter' => '95',
                'heading' => '9506',
                'subheading' => '61.00',
                'category' => 'sports',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['sports', 'outdoor', 'game', 'ball', 'equipment'],
                'materials' => ['rubber', 'plastic', 'leather'],
            ],

            // =============================
            // AUTOMOTIVE & PARTS
            // =============================
            [
                'code' => '8708.29.00',
                'description' => 'Other parts and accessories of motor vehicles',
                'chapter' => '87',
                'heading' => '8708',
                'subheading' => '29.00',
                'category' => 'automotive',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['auto part', 'car part', 'vehicle accessory'],
                'materials' => ['metal', 'plastic'],
            ],
            [
                'code' => '4011.10.00',
                'description' => 'New pneumatic tyres, of rubber, of a kind used on motor cars',
                'chapter' => '40',
                'heading' => '4011',
                'subheading' => '10.00',
                'category' => 'automotive',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['tyre', 'tire', 'car tyre'],
                'materials' => ['rubber'],
            ],
            [
                'code' => '8512.20.00',
                'description' => 'Electrical lighting or signalling equipment for motor vehicles',
                'chapter' => '85',
                'heading' => '8512',
                'subheading' => '20.00',
                'category' => 'automotive',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['car light', 'headlight', 'indicator'],
                'materials' => ['plastic', 'glass', 'metal'],
            ],


            // =============================
            // BATTERIES & POWER
            // =============================
            [
                'code' => '8507.60.00',
                'description' => 'Lithium-ion accumulators',
                'chapter' => '85',
                'heading' => '8507',
                'subheading' => '60.00',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['battery', 'lithium battery', 'li-ion'],
                'materials' => ['lithium', 'metal'],
            ],
            [
                'code' => '8506.10.00',
                'description' => 'Manganese dioxide primary cells and batteries',
                'chapter' => '85',
                'heading' => '8506',
                'subheading' => '10.00',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['battery', 'dry cell', 'aa battery'],
                'materials' => ['chemical'],
            ],

            // =============================
            // CABLES & ACCESSORIES
            // =============================
            [
                'code' => '8544.42.00',
                'description' => 'Electric conductors fitted with connectors',
                'chapter' => '85',
                'heading' => '8544',
                'subheading' => '42.00',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['usb cable', 'charging cable', 'wire'],
                'materials' => ['copper', 'plastic'],
            ],
            [
                'code' => '8536.69.00',
                'description' => 'Plugs, sockets and other connectors',
                'chapter' => '85',
                'heading' => '8536',
                'subheading' => '69.00',
                'category' => 'electronics',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['connector', 'plug', 'socket'],
                'materials' => ['plastic', 'metal'],
            ],
            // =============================
            // KITCHEN & HOUSEHOLD
            // =============================
            [
                'code' => '7323.93.00',
                'description' => 'Table, kitchen or other household articles of stainless steel',
                'chapter' => '73',
                'heading' => '7323',
                'subheading' => '93.00',
                'category' => 'household',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['kitchen utensil', 'spoon', 'pan'],
                'materials' => ['stainless steel'],
            ],
            [
                'code' => '6912.00.00',
                'description' => 'Ceramic tableware, kitchenware',
                'chapter' => '69',
                'heading' => '6912',
                'subheading' => '00.00',
                'category' => 'household',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['plate', 'bowl', 'ceramic'],
                'materials' => ['ceramic'],
            ],
            // =============================
            // TOOLS & HARDWARE
            // =============================
            [
                'code' => '8205.59.00',
                'description' => 'Hand tools, not elsewhere specified',
                'chapter' => '82',
                'heading' => '8205',
                'subheading' => '59.00',
                'category' => 'tools',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['tool', 'hand tool', 'hardware'],
                'materials' => ['steel', 'metal'],
            ],
            [
                'code' => '8467.29.00',
                'description' => 'Other tools for working in the hand, with self-contained electric motor',
                'chapter' => '84',
                'heading' => '8467',
                'subheading' => '29.00',
                'category' => 'tools',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['power tool', 'drill', 'electric tool'],
                'materials' => ['metal', 'plastic'],
            ],
            // =============================
            // PLASTICS & PACKAGING
            // =============================
            [
                'code' => '3923.21.00',
                'description' => 'Sacks and bags of polymers of ethylene',
                'chapter' => '39',
                'heading' => '3923',
                'subheading' => '21.00',
                'category' => 'packaging',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['plastic bag', 'poly bag', 'packaging'],
                'materials' => ['plastic'],
            ],
            [
                'code' => '3926.90.00',
                'description' => 'Other articles of plastics',
                'chapter' => '39',
                'heading' => '3926',
                'subheading' => '90.00',
                'category' => 'plastic',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['plastic item', 'plastic product'],
                'materials' => ['plastic'],
            ],
            // =============================
            // STATIONERY & OFFICE
            // =============================
            [
                'code' => '4820.10.00',
                'description' => 'Registers, notebooks, diaries, exercise books',
                'chapter' => '48',
                'heading' => '4820',
                'subheading' => '10.00',
                'category' => 'stationery',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['notebook', 'diary', 'register'],
                'materials' => ['paper'],
            ],
            [
                'code' => '9608.10.00',
                'description' => 'Ball point pens',
                'chapter' => '96',
                'heading' => '9608',
                'subheading' => '10.00',
                'category' => 'stationery',
                'active_year' => 2024,
                'is_active' => true,
                'keywords' => ['pen', 'ball pen'],
                'materials' => ['plastic', 'ink'],
            ],
        ];

        foreach ($hsCodes as $code) {
            HSCode::updateOrCreate(
                ['code' => $code['code']],
                $code
            );
        }

        $this->command->info('Seeded ' . count($hsCodes) . ' HS codes');
    }
}
