<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageChangeRequest extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    const TYPE_PACKAGE = 'package';
    const TYPE_ADDRESS = 'address';

    protected $fillable = [
        'package_id',
        'user_id',
        'customer_id',
        'reviewed_by',
        'request_type',
        'original_values',
        'requested_changes',
        'customer_notes',
        'admin_notes',
        'status',
        'reviewed_at',
    ];

    protected $casts = [
        'original_values' => 'array',
        'requested_changes' => 'array',
        'reviewed_at' => 'datetime',
    ];

    protected $appends = ['status_label', 'is_pending'];

    /**
     * Get the package this request is for
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /**
     * Get the customer who made the request
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * @deprecated Use customer() instead
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin who reviewed the request
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get human-readable status label
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Pending Review',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
            default => 'Unknown',
        };
    }

    /**
     * Check if request is still pending
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Scope to get pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope to get requests for a specific customer
     */
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * @deprecated Use scopeForCustomer instead
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Approve the request and apply changes to package
     */
    public function approve($adminId, $adminNotes = null): bool
    {
        $this->status = self::STATUS_APPROVED;
        $this->reviewed_by = $adminId;
        $this->reviewed_at = now();
        $this->admin_notes = $adminNotes;

        // Apply the changes to the package
        if ($this->request_type === self::TYPE_PACKAGE && $this->package) {
            $changes = $this->requested_changes;
            $package = $this->package;

            // Only update allowed fields
            $allowedFields = ['length', 'width', 'height', 'weight', 'dimension_unit', 'weight_unit'];
            foreach ($allowedFields as $field) {
                if (isset($changes[$field]) && $changes[$field] !== null) {
                    $package->$field = $changes[$field];
                }
            }
            $package->save();
        }

        return $this->save();
    }

    /**
     * Reject the request
     */
    public function reject($adminId, $adminNotes = null): bool
    {
        $this->status = self::STATUS_REJECTED;
        $this->reviewed_by = $adminId;
        $this->reviewed_at = now();
        $this->admin_notes = $adminNotes;

        return $this->save();
    }
}
