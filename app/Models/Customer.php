<?php

namespace App\Models;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

/**
 * Customer Model
 * 
 * Represents a customer user (external users who use the shipping/logistics services).
 * Completely separate from system users (admins, operators, etc.).
 * 
 * Implements MustVerifyEmail to require email verification after registration.
 */
class Customer extends Authenticatable implements MustVerifyEmail
{

    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'customers';

    protected $guard = 'customer';

    /**
     * Send the email verification notification.
     * Overridden to use customer-specific verification route.
     */
    public function sendEmailVerificationNotification(): void
    {
        // Create a custom verification URL using the customer route
        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'customer.verification.verify',
                Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });

        $this->notify(new VerifyEmail);
    }

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'address',
        'country',
        'tax_id',
        'suite',
        'warehouse_id',
        'date_of_birth',
        'password',
        'is_active',
        'is_old',
        'user_name',
        'stripe_id',
        'avatar',
        'state',
        'city',
        'zip_code',
        'loyalty_points',
        'lifetime_spend',
        'referral_code',
        'referred_by_id',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'is_old' => 'boolean',
            'loyalty_points' => 'integer',
            'lifetime_spend' => 'decimal:2',
        ];
    }

    protected $appends = ['name', 'active', 'tier'];

    /**
     * Get full name attribute
     */
    public function getNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get active status as string
     */
    public function getActiveAttribute(): string
    {
        return $this->is_active ? 'Active' : 'Inactive';
    }

    // ==========================================
    // Scopes
    // ==========================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // ==========================================
    // Relationships
    // ==========================================

    /**
     * Get the warehouse assigned to this customer
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Get customer's addresses
     */
    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id');
    }

    /**
     * Get customer's default US address
     */
    public function defaultUsAddress()
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id')
            ->where('is_default_us', true);
    }

    /**
     * Get customer's default UK address
     */
    public function defaultUkAddress()
    {
        return $this->hasOne(CustomerAddress::class, 'customer_id')
            ->where('is_default_uk', true);
    }

    /**
     * Get all default addresses for the customer
     */
    public function defaultAddresses()
    {
        return $this->hasMany(CustomerAddress::class, 'customer_id')
            ->where(function ($query) {
                $query->where('is_default_us', true)
                    ->orWhere('is_default_uk', true);
            });
    }

    /**
     * Get customer's packages
     */
    public function packages()
    {
        return $this->hasMany(Package::class, 'customer_id');
    }

    /**
     * Get customer's shipments
     */
    public function shipments()
    {
        return $this->hasMany(Ship::class, 'customer_id');
    }

    /**
     * Get customer's payment cards
     */
    public function cards()
    {
        return $this->hasMany(UserCard::class, 'customer_id');
    }

    /**
     * Get customer's transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'customer_id');
    }

    /**
     * Get loyalty transactions for the customer
     */
    public function loyaltyTransactions()
    {
        return $this->hasMany(LoyaltyTransaction::class, 'customer_id');
    }

    /**
     * Get coupon usages for the customer
     */
    public function couponUsages()
    {
        return $this->hasMany(CouponUsage::class, 'customer_id');
    }

    /**
     * Get loyalty milestones for the customer
     */
    public function loyaltyMilestones()
    {
        return $this->hasMany(LoyaltyMilestone::class, 'customer_id');
    }

    /**
     * Get package change requests for the customer
     */
    public function packageChangeRequests()
    {
        return $this->hasMany(PackageChangeRequest::class, 'customer_id');
    }

    // ==========================================
    // Loyalty Points Methods
    // ==========================================

    /**
     * Add loyalty points to customer
     */
    public function addLoyaltyPoints(int $points): void
    {
        $this->increment('loyalty_points', $points);
    }

    /**
     * Deduct loyalty points from customer
     */
    public function deductLoyaltyPoints(int $points): bool
    {
        if ($this->loyalty_points >= $points) {
            $this->decrement('loyalty_points', $points);
            return true;
        }
        return false;
    }

    /**
     * Check if customer has enough loyalty points
     */
    public function hasEnoughLoyaltyPoints(int $points): bool
    {
        return $this->loyalty_points >= $points;
    }

    /**
     * Add to lifetime spend and check for tier upgrade
     */
    public function addLifetimeSpend(float $amount): void
    {
        $this->increment('lifetime_spend', $amount);
    }

    /**
     * Get current loyalty tier based on lifetime spend
     * 
     * Wrapped in try-catch to prevent 500 errors if loyalty_tiers table
     * has schema issues (e.g., missing migrations on production)
     */
    public function getTierAttribute(): ?LoyaltyTier
    {
        try {
            return LoyaltyTier::getForSpend($this->lifetime_spend ?? 0);
        } catch (\Exception $e) {
            // Log the error but don't crash the request
            \Log::warning('Failed to load loyalty tier for customer', [
                'customer_id' => $this->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get tier progress to next level
     */
    public function getTierProgress(): array
    {
        $tier = $this->tier;
        if (!$tier) {
            return [
                'current_tier' => null,
                'next_tier' => LoyaltyTier::getDefaultTier(),
                'percentage' => 0,
            ];
        }
        return array_merge(
            ['current_tier' => $tier],
            $tier->getProgressToNext($this->lifetime_spend ?? 0)
        );
    }

    // ==========================================
    // Referral Relationships
    // ==========================================

    /**
     * Get the customer who referred this customer
     */
    public function referrer()
    {
        return $this->belongsTo(Customer::class, 'referred_by_id');
    }

    /**
     * Get customers referred by this customer
     */
    public function referrals()
    {
        return $this->hasMany(Customer::class, 'referred_by_id');
    }

    /**
     * Generate a unique referral code
     */
    public static function generateReferralCode(): string
    {
        do {
            $code = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
        } while (static::where('referral_code', $code)->exists());

        return $code;
    }

    // ==========================================
    // Helper Methods
    // ==========================================

    /**
     * Generate unique suite number for new customers
     * 
     * Format: X + Island Initial + 4-digit number (e.g., XC0001 for Curaçao)
     * The prefix is based on the customer's country/island.
     * 
     * @param string|null $countryCode ISO country code (e.g., 'CW' for Curaçao)
     * @param string|null $countryName Country name (used to derive initial if mapping not found)
     */
    public static function generateSuiteNumber(?string $countryCode = null, ?string $countryName = null): string
    {
        // Island/country code to suite prefix mapping (from business requirements)
        $prefixMap = [
            // Caribbean Netherlands BES Islands (split from BQ)
            'BQ-BO' => 'XB',     // Bonaire
            'BQ-SE' => 'XE',     // Sint Eustatius
            'BQ-SA' => 'AN',     // Saba

            // ABC Islands
            'CW' => 'XC',        // Curaçao
            'AW' => 'XA',        // Aruba

            // Dutch Caribbean
            'SX' => 'XM',        // Sint Maarten (Dutch & French)

            // Lesser Antilles
            'AI' => 'XAI',       // Anguilla
            'AG' => 'XAG',       // Antigua and Barbuda
            'BS' => 'XBA',       // Bahamas
            'BB' => 'XBB',       // Barbados
            'VG' => 'XBVI',      // British Virgin Islands
            'KY' => 'XCYM',      // Cayman Islands
            'CU' => 'XCUB',      // Cuba
            'DM' => 'XDM',       // Dominica
            'DO' => 'XD',        // Dominican Republic
            'GD' => 'XG',        // Grenada
            'GP' => 'XGUA',      // Guadeloupe
            'HT' => 'XH',        // Haiti
            'JM' => 'XJ',        // Jamaica
            'MQ' => 'XMAR',      // Martinique
            'MS' => 'XMONT',     // Montserrat
            'PR' => 'XP',        // Puerto Rico
            'BL' => 'XBLM',      // Saint Barthélemy
            'KN' => 'XK',        // Saint Kitts and Nevis
            'LC' => 'XL',        // Saint Lucia
            'MF' => 'XMAF',      // Saint Martin (French Collectivity)
            'VC' => 'XV',        // Saint Vincent and the Grenadines
            'TT' => 'XT',        // Trinidad and Tobago
            'TC' => 'XTCA',      // Turks and Caicos Islands
            'VI' => 'XUSVI',     // United States Virgin Islands

            // Legacy fallback for existing BQ customers
            'BQ' => 'XC',
        ];

        // Get prefix from mapping, or derive from country name's first letter
        if ($countryCode && isset($prefixMap[$countryCode])) {
            $prefix = $prefixMap[$countryCode];
        } elseif ($countryName) {
            // Use first letter of country name
            $firstLetter = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $countryName), 0, 1));
            $prefix = 'X' . ($firstLetter ?: 'X');
        } else {
            // Fallback for legacy calls without country info
            $prefix = 'MKT';
            $lastCustomer = static::orderBy('id', 'desc')->first();
            $nextNumber = $lastCustomer ? ($lastCustomer->id + 1) : 1;
            return $prefix . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
        }

        // Get next number for THIS specific prefix
        $lastCustomerWithPrefix = static::where('suite', 'LIKE', $prefix . '%')
            ->orderByRaw('CAST(SUBSTRING(suite, ' . (strlen($prefix) + 1) . ') AS UNSIGNED) DESC')
            ->first();

        if ($lastCustomerWithPrefix && preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $lastCustomerWithPrefix->suite, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if customer has verified email
     */
    public function isVerified(): bool
    {
        return !is_null($this->email_verified_at);
    }
}
