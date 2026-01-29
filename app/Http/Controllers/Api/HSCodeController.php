<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HSCodeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * HSCodeController
 * 
 * Provides HS Code (Harmonized System Code) lookup functionality.
 * Similar to DHL's Commodity Code Lookup.
 */
class HSCodeController extends Controller
{
    /**
     * Lookup HS code based on item description, material, and usage
     * 
     * POST /api/hs-codes/lookup
     * 
     * Returns suggestions (NEVER auto-selects) - user must manually confirm
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function lookup(Request $request)
    {
        $request->validate([
            'item_description' => 'required|string|max:500',
            'material' => 'nullable|string|max:200',
            'usage' => 'nullable|string|max:200',
        ]);

        $itemDescription = $request->input('item_description');
        $material = $request->input('material');
        $usage = $request->input('usage');

        // Use the new HSCodeService (3-layer architecture)
        $hsCodeService = app(HSCodeService::class);
        $suggestions = $hsCodeService->getSuggestions($itemDescription, $material, $usage);

        // Also try TradeTariff API for additional results
        $tradeTariffResult = $this->lookupTradeTariff($itemDescription, $material, $usage);
        if ($tradeTariffResult) {
            $suggestions = array_merge($suggestions, $tradeTariffResult);
        }

        // Deduplicate and sort
        $suggestions = $this->deduplicateAndSort($suggestions);

        return response()->json([
            'success' => true,
            'query' => [
                'item_description' => $itemDescription,
                'material' => $material,
                'usage' => $usage,
            ],
            'suggestions' => array_slice($suggestions, 0, 5), // Return top 5 suggestions
            'total' => count($suggestions),
            'message' => 'Please review and select the most appropriate HS code. These are suggestions only.',
        ]);
    }

    /**
     * Lookup using TradeTariff API (UK government - free)
     * Enhanced with multiple search strategies for better results
     */
    private function lookupTradeTariff(string $description, ?string $material, ?string $usage): array
    {
        try {
            $cacheKey = 'hs_trade_tariff_' . md5($description . $material . $usage);
            
            $results = Cache::remember($cacheKey, 3600, function () use ($description, $material, $usage) {
                $allResults = [];
                
                // Strategy 1: Search with original description
                $response1 = $this->searchTradeTariff($description);
                if ($response1) {
                    $allResults = array_merge($allResults, $response1);
                }
                
                // Strategy 2: If description is short/common term, try expanded search
                if (strlen($description) < 15) {
                    // Try with "clothing" appended for clothing-related terms
                    $clothingTerms = ['clothes', 'clothing', 'dress', 'shirt', 'pants', 'jacket', 'sweater'];
                    foreach ($clothingTerms as $term) {
                        if (stripos($description, $term) !== false) {
                            $expandedQuery = $description . ' ' . $term;
                            $response2 = $this->searchTradeTariff($expandedQuery);
                            if ($response2) {
                                $allResults = array_merge($allResults, $response2);
                            }
                            break;
                        }
                    }
                }
                
                // Strategy 3: If material is provided, try searching with material
                if ($material && strlen($material) > 2) {
                    $materialQuery = $description . ' ' . $material;
                    $response3 = $this->searchTradeTariff($materialQuery);
                    if ($response3) {
                        $allResults = array_merge($allResults, $response3);
                    }
                }
                
                // Remove duplicates by HS code
                $uniqueResults = [];
                foreach ($allResults as $result) {
                    $code = $result['hs_code'];
                    if (!isset($uniqueResults[$code])) {
                        $uniqueResults[$code] = $result;
                    }
                }
                
                return array_values($uniqueResults);
            });

            // Ensure we always return an array
            return is_array($results) ? $results : [];
        } catch (\Exception $e) {
            Log::warning('HS Code TradeTariff lookup failed', [
                'error' => $e->getMessage(),
                'description' => $description,
            ]);
            return [];
        }
    }
    
    /**
     * Perform a single TradeTariff API search
     */
    private function searchTradeTariff(string $query): array
    {
        try {
            $response = Http::timeout(15)
                ->get('https://www.trade-tariff.service.gov.uk/api/v2/commodities/search', [
                    'q' => $query,
                    'limit' => 20, // Increased limit for better results
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $results = [];

                foreach ($data['data'] ?? [] as $item) {
                    $hsCode = $item['attributes']['goods_nomenclature_item_id'] ?? null;
                    if ($hsCode) {
                        $results[] = [
                            'hs_code' => $hsCode,
                            'description' => $item['attributes']['description'] ?? $query,
                            'full_description' => $item['attributes']['formatted_description'] ?? null,
                            'source' => 'trade_tariff',
                            'relevance_score' => 0.8,
                        ];
                    }
                }

                return $results;
            }
            
            return [];
        } catch (\Exception $e) {
            Log::debug('TradeTariff single search failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Lookup using internal database (if we have one)
     */
    private function lookupInternal(string $description, ?string $material, ?string $usage): array
    {
        // TODO: If you have an internal HS code database, implement here
        // For now, return empty array
        return [];
    }

    /**
     * Lookup using keyword matching with common HS codes
     * Enhanced with synonym matching and fuzzy search
     */
    private function lookupByKeywords(string $description, ?string $material, ?string $usage): array
    {
        $descriptionLower = strtolower(trim($description));
        $materialLower = $material ? strtolower(trim($material)) : '';
        $usageLower = $usage ? strtolower(trim($usage)) : '';

        // Get synonyms for common clothing terms
        $synonyms = $this->getClothingSynonyms();
        
        // Expand search terms with synonyms
        $expandedTerms = $this->expandWithSynonyms($descriptionLower, $synonyms);

        $commonCodes = $this->getCommonHSCodes();
        $results = [];

        foreach ($commonCodes as $code => $keywords) {
            $score = 0;
            $matchedKeywords = [];

            // Check description - now checks both original and expanded terms
            foreach ($keywords['description'] as $keyword) {
                $keywordLower = strtolower($keyword);
                
                // Direct match
                if (str_contains($descriptionLower, $keywordLower)) {
                    $score += 2;
                    $matchedKeywords[] = $keyword;
                }
                
                // Check expanded synonyms
                foreach ($expandedTerms as $expandedTerm) {
                    if (str_contains($expandedTerm, $keywordLower) || str_contains($keywordLower, $expandedTerm)) {
                        $score += 1.5;
                        $matchedKeywords[] = $keyword;
                        break;
                    }
                }
            }

            // Check material
            if ($materialLower) {
                foreach ($keywords['material'] ?? [] as $keyword) {
                    if (str_contains($materialLower, strtolower($keyword))) {
                        $score += 1;
                        $matchedKeywords[] = $keyword;
                    }
                }
            }

            // Check usage - also check for clothing-related usage terms
            if ($usageLower) {
                foreach ($keywords['usage'] ?? [] as $keyword) {
                    if (str_contains($usageLower, strtolower($keyword))) {
                        $score += 1;
                        $matchedKeywords[] = $keyword;
                    }
                }
            }
            
            // Special handling: if description contains clothing-related terms, boost score
            $clothingTerms = ['clothes', 'clothing', 'apparel', 'garment', 'wear', 'outfit', 'attire'];
            foreach ($clothingTerms as $term) {
                if (str_contains($descriptionLower, $term)) {
                    // Check if this code is clothing-related
                    if (in_array('clothing', $keywords['usage'] ?? []) || 
                        in_array('apparel', $keywords['description'] ?? [])) {
                        $score += 1.5;
                        $matchedKeywords[] = $term;
                    }
                }
            }

            if ($score > 0) {
                $results[] = [
                    'hs_code' => $code,
                    'description' => $keywords['name'],
                    'full_description' => $keywords['description_full'] ?? $keywords['name'],
                    'source' => 'keyword_match',
                    'relevance_score' => min($score / 5, 1.0), // Normalize to 0-1
                    'matched_keywords' => array_unique($matchedKeywords),
                ];
            }
        }

        return $results;
    }
    
    /**
     * Get synonyms for common clothing terms
     */
    private function getClothingSynonyms(): array
    {
        return [
            'clothes' => ['clothing', 'apparel', 'garments', 'wear', 'attire', 'outfit', 'wardrobe'],
            'clothing' => ['clothes', 'apparel', 'garments', 'wear', 'attire', 'outfit'],
            'dress' => ['gown', 'frock', 'garment', 'clothing', 'apparel'],
            'shirt' => ['top', 'blouse', 'tee', 't-shirt', 'tshirt'],
            'pants' => ['trousers', 'slacks', 'bottoms'],
            'shoes' => ['footwear', 'sneakers', 'boots', 'sandals'],
            'jacket' => ['coat', 'outerwear', 'blazer'],
            'sweater' => ['pullover', 'jumper', 'cardigan', 'knitwear'],
        ];
    }
    
    /**
     * Expand search terms with synonyms
     */
    private function expandWithSynonyms(string $term, array $synonyms): array
    {
        $expanded = [$term];
        
        foreach ($synonyms as $key => $synonymList) {
            if (str_contains($term, $key)) {
                $expanded = array_merge($expanded, $synonymList);
            }
            foreach ($synonymList as $synonym) {
                if (str_contains($term, $synonym)) {
                    $expanded[] = $key;
                    $expanded = array_merge($expanded, $synonymList);
                }
            }
        }
        
        return array_unique($expanded);
    }

    /**
     * Get common HS codes database
     * Enhanced with more clothing and apparel codes
     */
    private function getCommonHSCodes(): array
    {
        return [
            // CLOTHING AND APPAREL
            '6204.62.00' => [
                'name' => 'Women\'s or girls\' trousers, bib and brace overalls, breeches and shorts',
                'description' => ['dress', 'gown', 'frock', 'women dress', 'ladies dress', 'clothing', 'clothes', 'apparel', 'garment', 'women', 'ladies'],
                'material' => ['cotton', 'polyester', 'silk', 'fabric', 'textile'],
                'usage' => ['clothing', 'apparel', 'fashion', 'personal'],
                'description_full' => 'Women\'s or girls\' trousers, bib and brace overalls, breeches and shorts, of cotton',
            ],
            '6204.63.00' => [
                'name' => 'Women\'s or girls\' trousers and shorts',
                'description' => ['pants', 'trousers', 'shorts', 'women', 'ladies', 'clothing', 'clothes', 'apparel'],
                'material' => ['synthetic', 'polyester', 'nylon', 'fabric'],
                'usage' => ['clothing', 'apparel', 'fashion'],
                'description_full' => 'Women\'s or girls\' trousers, bib and brace overalls, breeches and shorts, of synthetic fibres',
            ],
            '6203.42.00' => [
                'name' => 'Men\'s or boys\' trousers, bib and brace overalls, breeches and shorts',
                'description' => ['pants', 'trousers', 'shorts', 'men', 'boys', 'clothing', 'clothes', 'apparel'],
                'material' => ['cotton', 'denim', 'fabric'],
                'usage' => ['clothing', 'apparel', 'fashion'],
                'description_full' => 'Men\'s or boys\' trousers, bib and brace overalls, breeches and shorts, of cotton',
            ],
            '6109.10.00' => [
                'name' => 'T-shirts, singlets and other vests, knitted or crocheted',
                'description' => ['t-shirt', 'tshirt', 'tee', 'shirt', 'top', 'singlet', 'vest', 'clothing', 'clothes', 'apparel'],
                'material' => ['cotton', 'polyester', 'fabric'],
                'usage' => ['clothing', 'apparel', 'casual'],
                'description_full' => 'T-shirts, singlets and other vests, knitted or crocheted, of cotton',
            ],
            '6110.20.00' => [
                'name' => 'Sweaters and pullovers',
                'description' => ['sweater', 'pullover', 'jumper', 'cardigan', 'knitwear', 'clothing', 'clothes', 'apparel'],
                'material' => ['wool', 'cotton', 'synthetic', 'cashmere'],
                'usage' => ['clothing', 'apparel', 'warmth'],
                'description_full' => 'Jerseys, pullovers, cardigans, waistcoats and similar articles, knitted or crocheted, of wool or fine animal hair',
            ],
            '6201.93.00' => [
                'name' => 'Men\'s or boys\' suits',
                'description' => ['suit', 'men suit', 'formal', 'clothing', 'clothes', 'apparel'],
                'material' => ['wool', 'synthetic', 'fabric'],
                'usage' => ['clothing', 'apparel', 'formal'],
                'description_full' => 'Men\'s or boys\' suits, ensembles, jackets, blazers, trousers, bib and brace overalls, breeches and shorts, of synthetic fibres',
            ],
            '6202.93.00' => [
                'name' => 'Women\'s or girls\' suits',
                'description' => ['suit', 'women suit', 'ladies suit', 'formal', 'clothing', 'clothes', 'apparel'],
                'material' => ['synthetic', 'fabric'],
                'usage' => ['clothing', 'apparel', 'formal'],
                'description_full' => 'Women\'s or girls\' suits, ensembles, jackets, blazers, dresses, skirts, divided skirts, trousers, bib and brace overalls, breeches and shorts, of synthetic fibres',
            ],
            '6204.44.00' => [
                'name' => 'Women\'s or girls\' dresses',
                'description' => ['dress', 'gown', 'frock', 'women dress', 'ladies dress', 'clothing', 'clothes', 'apparel', 'garment'],
                'material' => ['cotton', 'silk', 'polyester', 'fabric'],
                'usage' => ['clothing', 'apparel', 'fashion'],
                'description_full' => 'Women\'s or girls\' dresses, of cotton',
            ],
            '6203.23.00' => [
                'name' => 'Men\'s or boys\' jackets and blazers',
                'description' => ['jacket', 'blazer', 'coat', 'men', 'boys', 'clothing', 'clothes', 'apparel'],
                'material' => ['cotton', 'fabric'],
                'usage' => ['clothing', 'apparel', 'outerwear'],
                'description_full' => 'Men\'s or boys\' suits, ensembles, jackets, blazers, trousers, bib and brace overalls, breeches and shorts, of cotton',
            ],
            '6202.13.00' => [
                'name' => 'Women\'s or girls\' overcoats, car coats, capes, cloaks',
                'description' => ['coat', 'overcoat', 'jacket', 'women', 'ladies', 'clothing', 'clothes', 'apparel'],
                'material' => ['wool', 'fabric'],
                'usage' => ['clothing', 'apparel', 'outerwear'],
                'description_full' => 'Women\'s or girls\' overcoats, car coats, capes, cloaks, anoraks (including ski-jackets), wind-cheaters, wind-jackets and similar articles, of wool or fine animal hair',
            ],
            '6109.90.00' => [
                'name' => 'T-shirts, singlets and other vests',
                'description' => ['t-shirt', 'tshirt', 'shirt', 'top', 'clothing', 'clothes', 'apparel'],
                'material' => ['other', 'fabric', 'textile'],
                'usage' => ['clothing', 'apparel'],
                'description_full' => 'T-shirts, singlets and other vests, knitted or crocheted, of other textile materials',
            ],
            '6204.32.00' => [
                'name' => 'Women\'s or girls\' jackets and blazers',
                'description' => ['jacket', 'blazer', 'women', 'ladies', 'clothing', 'clothes', 'apparel'],
                'material' => ['cotton', 'fabric'],
                'usage' => ['clothing', 'apparel'],
                'description_full' => 'Women\'s or girls\' suits, ensembles, jackets, blazers, dresses, skirts, divided skirts, trousers, bib and brace overalls, breeches and shorts, of cotton',
            ],
            
            // FOOTWEAR
            '6403.99.90' => [
                'name' => 'Footwear',
                'description' => ['shoe', 'sneaker', 'boot', 'sandal', 'footwear'],
                'material' => ['leather', 'rubber', 'fabric', 'synthetic'],
                'usage' => ['personal', 'sports', 'casual'],
                'description_full' => 'Other footwear, with outer soles of rubber, plastics, leather or composition leather and uppers of textile materials',
            ],
            
            // ACCESSORIES
            '4202.12.00' => [
                'name' => 'Handbags',
                'description' => ['handbag', 'purse', 'bag', 'clutch', 'tote'],
                'material' => ['leather', 'fabric', 'synthetic', 'canvas'],
                'usage' => ['personal', 'fashion', 'accessory'],
                'description_full' => 'Handbags, whether or not with shoulder strap, including those without handle, with outer surface of leather or of composition leather',
            ],
            
            // ELECTRONICS
            '8471.30.01' => [
                'name' => 'Portable automatic data processing machines',
                'description' => ['computer', 'laptop', 'notebook', 'tablet', 'electronic device', 'data processing'],
                'material' => ['plastic', 'metal', 'aluminum'],
                'usage' => ['personal', 'business', 'consumer'],
                'description_full' => 'Portable automatic data processing machines, weighing not more than 10 kg, consisting of a central processing unit, a keyboard and a display',
            ],
            '8517.12.00' => [
                'name' => 'Smartphones',
                'description' => ['phone', 'smartphone', 'mobile', 'cell phone', 'iphone', 'android'],
                'material' => ['glass', 'metal', 'plastic'],
                'usage' => ['communication', 'personal', 'consumer'],
                'description_full' => 'Telephones for cellular networks or for other wireless networks',
            ],
            '8528.72.00' => [
                'name' => 'Monitors and displays',
                'description' => ['monitor', 'display', 'screen', 'tv', 'television'],
                'material' => ['plastic', 'metal', 'glass'],
                'usage' => ['electronics', 'display', 'entertainment'],
                'description_full' => 'Monitors and projectors, not incorporating television reception apparatus; reception apparatus for television, whether or not incorporating radio-broadcast receivers or sound or video recording or reproducing apparatus',
            ],
            '8516.50.00' => [
                'name' => 'Microwave ovens',
                'description' => ['microwave', 'oven', 'cooking', 'appliance'],
                'material' => ['metal', 'plastic', 'glass'],
                'usage' => ['kitchen', 'cooking', 'home'],
                'description_full' => 'Microwave ovens',
            ],
            
            // OTHER
            '9503.00.00' => [
                'name' => 'Toys and games',
                'description' => ['toy', 'game', 'doll', 'action figure', 'puzzle', 'board game'],
                'material' => ['plastic', 'fabric', 'wood', 'metal'],
                'usage' => ['recreational', 'children', 'entertainment'],
                'description_full' => 'Tricycles, scooters, pedal cars and similar wheeled toys; dolls\' carriages; dolls; other toys; reduced-size ("scale") models and similar recreational models',
            ],
            '9401.40.00' => [
                'name' => 'Mattresses',
                'description' => ['mattress', 'bed', 'sleeping'],
                'material' => ['foam', 'spring', 'memory foam', 'latex'],
                'usage' => ['furniture', 'home', 'sleeping'],
                'description_full' => 'Mattress supports; articles of bedding and similar furnishing (for example, mattresses, quilts, eiderdowns, cushions, pouffes and pillows) fitted with springs or stuffed or internally fitted with any material or of cellular rubber or plastics',
            ],
            '3004.90.00' => [
                'name' => 'Medicines and pharmaceuticals',
                'description' => ['medicine', 'pharmaceutical', 'drug', 'pill', 'tablet', 'capsule'],
                'material' => ['chemical', 'organic'],
                'usage' => ['medical', 'health', 'treatment'],
                'description_full' => 'Medicaments (excluding goods of heading 3002, 3005 or 3006) consisting of mixed or unmixed products for therapeutic or prophylactic uses, put up in measured doses or in forms or packings for retail sale',
            ],
        ];
    }

    /**
     * Deduplicate and sort results by relevance
     */
    private function deduplicateAndSort(array $results): array
    {
        // Normalize all results to have a consistent score field
        foreach ($results as &$result) {
            // Normalize: use confidence_score if available, otherwise relevance_score, otherwise default to 0.5
            if (isset($result['confidence_score'])) {
                $result['relevance_score'] = $result['confidence_score'];
            } elseif (!isset($result['relevance_score'])) {
                $result['relevance_score'] = 0.5; // Default score for results without explicit scoring
            }
        }
        unset($result); // Break reference

        // Remove duplicates by HS code
        $unique = [];
        foreach ($results as $result) {
            $code = $result['hs_code'];
            $currentScore = $result['relevance_score'] ?? 0;
            $existingScore = $unique[$code]['relevance_score'] ?? 0;
            
            if (!isset($unique[$code]) || $existingScore < $currentScore) {
                $unique[$code] = $result;
            }
        }

        // Sort by relevance score (highest first)
        usort($unique, function ($a, $b) {
            $scoreA = $a['relevance_score'] ?? 0;
            $scoreB = $b['relevance_score'] ?? 0;
            return $scoreB <=> $scoreA;
        });

        return array_values($unique);
    }
}
