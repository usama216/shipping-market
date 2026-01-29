<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * User Model
 * 
 * Represents a SYSTEM USER (admin, operator, warehouse staff, etc.).
 * Customers are stored in a separate 'customers' table.
 * 
 * @property int $id
 * @property string $email
 * @property string $first_name
 * @property string $last_name
 * @property int $type
 * @property bool $is_active
 */
class User extends Authenticatable
{
    // System user types - customers are in separate table now
    const USER_TYPE_ADMIN = 1;
    const USER_TYPE_OPERATOR = 4;
    const USER_TYPE_WAREHOUSE = 3;
    const USER_TYPE_SUPPORT = 5;
    const USER_TYPE_SALES = 6;

    const STATUS_ACTIVE = 1;
    const STATUS_INACTIVE = 0;

    use HasFactory, Notifiable, HasRoles {
        HasRoles::hasPermissionTo as traitHasPermissionTo;
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'phone',
        'password',
        'is_active',
        'type',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    protected $appends = ['name', 'active'];
    public function getNameAttribute(): string
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    public function getActiveAttribute()
    {
        return $this->is_active === 1 ? 'Active' : 'In Active';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }

    /**
     * Scope: get only system users (non-customers - which are now in separate table)
     */
    public function scopeSystemUsers($query)
    {
        return $query->whereIn('type', [
            self::USER_TYPE_ADMIN,
            self::USER_TYPE_OPERATOR,
            self::USER_TYPE_WAREHOUSE,
            self::USER_TYPE_SUPPORT,
            self::USER_TYPE_SALES,
        ]);
    }

    // ==========================================
    // Role & Permission Helpers
    // ==========================================

    /**
     * Check if user is a system user (admin, operator, warehouse, etc.)
     */
    public function isSystemUser(): bool
    {
        return in_array($this->type, [
            self::USER_TYPE_ADMIN,
            self::USER_TYPE_OPERATOR,
            self::USER_TYPE_WAREHOUSE,
            self::USER_TYPE_SUPPORT,
            self::USER_TYPE_SALES,
        ]);
    }

    /**
     * Check if user is a super admin (has super-admin role)
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermissionTo(string $permission): bool
    {
        // Super admins have all permissions
        if ($this->isSuperAdmin()) {
            return true;
        }
        return $this->traitHasPermissionTo($permission);
    }

    /**
     * Get the user's role name (single role per user)
     */
    public function getRoleName(): ?string
    {
        return $this->roles->first()?->name;
    }

    /**
     * Get user type label
     */
    public function getTypeLabel(): string
    {
        return match ($this->type) {
            self::USER_TYPE_ADMIN => 'Admin',
            self::USER_TYPE_OPERATOR => 'Operator',
            self::USER_TYPE_WAREHOUSE => 'Warehouse',
            self::USER_TYPE_SUPPORT => 'Support',
            self::USER_TYPE_SALES => 'Sales',
            default => 'Unknown',
        };
    }

    public function addresses()
    {
        return $this->hasMany(CustomerAddress::class, 'user_id', 'id');
    }

    /**
     * Get the user's default US address
     */
    public function defaultUsAddress()
    {
        return $this->hasOne(CustomerAddress::class, 'user_id', 'id')
            ->where('is_default_us', true);
    }

    /**
     * Get the user's default UK address
     */
    public function defaultUkAddress()
    {
        return $this->hasOne(CustomerAddress::class, 'user_id', 'id')
            ->where('is_default_uk', true);
    }

    /**
     * Get all default addresses for the user
     */
    public function defaultAddresses()
    {
        return $this->hasMany(CustomerAddress::class, 'user_id', 'id')
            ->where(function ($query) {
                $query->where('is_default_us', true)
                    ->orWhere('is_default_uk', true);
            });
    }

}
