<?php

namespace App\Services;

use App\Models\HSCode;
use App\Models\HSCodeRule;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * HS Code Resolution Service
 * 
 * Implements a 3-layer architecture:
 * 1. Master HS Code Dataset (Database)
 * 2. Intelligent Matching Layer (Rule Engine)
 * 3. Suggestion Engine (Returns top suggestions, never auto-locks)
 */
class HSCodeService
{
    /**
     * Get HS code suggestions based on description, material, and usage
     * 
     * Returns top 3-5 suggestions with confidence scores
     * NEVER auto-selects - always returns suggestions for manual review
     * 
     * @param string $description Item description
     * @param string|null $material Material
     * @param string|null $usage Usage
     * @return array Suggestions with confidence scores
     */
    public function getSuggestions(string $description, ?string $material = null, ?string $usage = null): array
    {
        $descriptionLower = strtolower(trim($description));
        $materialLower = $material ? strtolower(trim($material)) : '';
        $usageLower = $usage ? strtolower(trim($usage)) : '';

        $suggestions = [];

        // Layer 1: Rule-based matching (from database)
        $ruleSuggestions = $this->matchRules($descriptionLower, $materialLower, $usageLower);
        $suggestions = array_merge($suggestions, $ruleSuggestions);

        // Layer 2: Database HS code search
        $dbSuggestions = $this->searchDatabase($descriptionLower, $materialLower);
        $suggestions = array_merge($suggestions, $dbSuggestions);

        // Layer 3: Fallback to common codes (if database is empty)
        if (empty($suggestions)) {
            $fallbackSuggestions = $this->fallbackToCommonCodes($descriptionLower, $materialLower, $usageLower);
            $suggestions = array_merge($suggestions, $fallbackSuggestions);
        }

        // Layer 4: External API (TradeTariff)
        $apiSuggestions = $this->searchTradeTariffAPI($description, $material, $usage);
        $suggestions = array_merge($suggestions, $apiSuggestions);

        // Deduplicate, sort by confidence, and return top suggestions
        return $this->deduplicateAndRank($suggestions);
    }

    /**
     * Match against rule engine (Layer 1)
     */
    private function matchRules(string $description, string $material, string $usage): array
    {
        try {
            $rules = HSCodeRule::active()
                ->ordered()
                ->get();

            $matches = [];

            foreach ($rules as $rule) {
                $score = $this->calculateRuleScore($rule, $description, $material, $usage);
                
                if ($score > 0) {
                    $matches[] = [
                        'hs_code' => $rule->suggested_hs_code,
                        'description' => $this->getHSCodeDescription($rule->suggested_hs_code),
                        'source' => 'rule_engine',
                        'confidence_score' => min($score * $rule->confidence_score, 1.0),
                        'matched_rule' => $rule->id,
                        'category' => $rule->category,
                    ];
                }
            }

            return $matches;
        } catch (\Exception $e) {
            Log::warning('HS Code rule matching failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Calculate score for a rule match
     */
    private function calculateRuleScore(HSCodeRule $rule, string $description, string $material, string $usage): float
    {
        $score = 0.0;

        // Check keywords
        if ($rule->keywords) {
            foreach ($rule->keywords as $keyword) {
                if (str_contains($description, strtolower($keyword))) {
                    $score += 2.0;
                }
            }
        }

        // Check materials
        if ($material && $rule->materials) {
            foreach ($rule->materials as $mat) {
                if (str_contains($material, strtolower($mat))) {
                    $score += 1.0;
                }
            }
        }

        // Check usage terms
        if ($usage && $rule->usage_terms) {
            foreach ($rule->usage_terms as $term) {
                if (str_contains($usage, strtolower($term))) {
                    $score += 1.0;
                }
            }
        }

        return $score;
    }

    /**
     * Search database HS codes (Layer 2)
     */
    private function searchDatabase(string $description, string $material): array
    {
        try {
            $cacheKey = 'hs_db_search_' . md5($description . $material);
            
            return Cache::remember($cacheKey, 3600, function () use ($description, $material) {
                $codes = HSCode::active()
                    ->where(function ($query) use ($description) {
                        $query->where('description', 'like', "%{$description}%")
                              ->orWhereJsonContains('keywords', $description);
                    })
                    ->limit(10)
                    ->get();

                $results = [];
                foreach ($codes as $code) {
                    $results[] = [
                        'hs_code' => $code->code,
                        'description' => $code->description,
                        'source' => 'database',
                        'confidence_score' => 0.7,
                        'category' => $code->category,
                    ];
                }

                return $results;
            });
        } catch (\Exception $e) {
            Log::warning('HS Code database search failed', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Fallback to common codes (Layer 3 - if database is empty)
     */
    private function fallbackToCommonCodes(string $description, string $material, string $usage): array
    {
        // Only use if database is empty
        if (HSCode::active()->count() > 0) {
            return [];
        }

        // Use the existing common codes logic as fallback
        $commonCodes = $this->getCommonHSCodes();
        $results = [];

        foreach ($commonCodes as $code => $data) {
            $score = 0;
            
            foreach ($data['description'] ?? [] as $keyword) {
                if (str_contains($description, strtolower($keyword))) {
                    $score += 2;
                }
            }

            if ($score > 0) {
                $results[] = [
                    'hs_code' => $code,
                    'description' => $data['name'],
                    'source' => 'fallback',
                    'confidence_score' => min($score / 5, 0.6), // Lower confidence for fallback
                    'category' => $this->detectCategory($code),
                ];
            }
        }

        return $results;
    }

    /**
     * Search TradeTariff API (Layer 4)
     */
    private function searchTradeTariffAPI(string $description, ?string $material, ?string $usage): array
    {
        // Use existing TradeTariff logic
        // This is already implemented in HSCodeController
        return [];
    }

    /**
     * Get HS code description from database
     */
    private function getHSCodeDescription(?string $code): ?string
    {
        if (!$code) {
            return null;
        }

        try {
            $hsCode = HSCode::where('code', $code)->first();
            return $hsCode ? $hsCode->description : null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Deduplicate and rank suggestions
     */
    private function deduplicateAndRank(array $suggestions): array
    {
        // Group by HS code, keep highest confidence
        $unique = [];
        foreach ($suggestions as $suggestion) {
            $code = $suggestion['hs_code'] ?? null;
            if (!$code) continue;

            if (!isset($unique[$code]) || $unique[$code]['confidence_score'] < $suggestion['confidence_score']) {
                $unique[$code] = $suggestion;
            }
        }

        // Sort by confidence (highest first)
        usort($unique, function ($a, $b) {
            return $b['confidence_score'] <=> $a['confidence_score'];
        });

        // Return top 5 suggestions
        return array_slice(array_values($unique), 0, 5);
    }

    /**
     * Detect category from HS code
     */
    private function detectCategory(string $code): string
    {
        $chapter = substr($code, 0, 2);
        
        $categories = [
            '61' => 'apparel',
            '62' => 'apparel',
            '64' => 'footwear',
            '84' => 'electronics',
            '85' => 'electronics',
            '90' => 'medical',
            '95' => 'toys',
        ];

        return $categories[$chapter] ?? 'other';
    }

    /**
     * Get common HS codes (fallback only)
     */
    private function getCommonHSCodes(): array
    {
        // Keep existing common codes as fallback
        // This will be replaced by database once seeded
        return [
            '6204.44.00' => [
                'name' => 'Women\'s or girls\' dresses',
                'description' => ['dress', 'gown', 'frock', 'clothing', 'clothes', 'apparel'],
            ],
            '6110.20.00' => [
                'name' => 'Sweaters and pullovers',
                'description' => ['sweater', 'pullover', 'jumper', 'cardigan', 'clothing'],
            ],
            // Add more as needed for fallback
        ];
    }
}
