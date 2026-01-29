<?php

namespace App\Helpers;

/**
 * Unified Shipment Status - Covers the complete shipment lifecycle
 * 
 * This replaces fragmented status handling and provides a single source
 * of truth for shipment states across the application.
 */
class ShipmentStatus
{
    // ==========================================
    // PAYMENT LIFECYCLE
    // ==========================================
    const PENDING = 'pending';           // Created, waiting for payment
    const PAID = 'paid';                 // Payment received, ready for processing

    // ==========================================
    // CARRIER LIFECYCLE
    // ==========================================
    const PROCESSING = 'processing';     // Being prepared for carrier submission
    const SUBMITTED = 'submitted';       // Submitted to carrier API
    const LABEL_READY = 'label_ready';   // Label generated, awaiting pickup/dropoff
    const PICKED_UP = 'picked_up';       // Carrier has picked up the package
    const SHIPPED = 'shipped';           // In transit with carrier
    const IN_TRANSIT = 'in_transit';     // Active movement to destination
    const OUT_FOR_DELIVERY = 'out_for_delivery'; // Last-mile delivery in progress
    const DELIVERED = 'delivered';       // Delivered to recipient

    // ==========================================
    // EXCEPTION STATES
    // ==========================================
    const CANCELLED = 'cancelled';       // Cancelled by customer or admin
    const FAILED = 'failed';             // Carrier API submission failed
    const RETURNED = 'returned';         // Package returned to sender
    const ON_HOLD = 'on_hold';           // Held for review (customs, address issue, etc.)

    // ==========================================
    // INTERNATIONAL CUSTOMS
    // ==========================================
    const CUSTOMS_PENDING = 'customs_pending';   // Awaiting customs clearance
    const CUSTOMS_CLEARED = 'customs_cleared';   // Cleared customs successfully
    const CUSTOMS_HOLD = 'customs_hold';         // Held by customs

    /**
     * Get all status values
     */
    public static function all(): array
    {
        return [
            self::PENDING,
            self::PAID,
            self::PROCESSING,
            self::SUBMITTED,
            self::LABEL_READY,
            self::PICKED_UP,
            self::SHIPPED,
            self::IN_TRANSIT,
            self::OUT_FOR_DELIVERY,
            self::DELIVERED,
            self::CANCELLED,
            self::FAILED,
            self::RETURNED,
            self::ON_HOLD,
            self::CUSTOMS_PENDING,
            self::CUSTOMS_CLEARED,
            self::CUSTOMS_HOLD,
        ];
    }

    /**
     * Get basic statuses (backward compatible with existing code)
     */
    public static function basic(): array
    {
        return [
            self::PENDING,
            self::PAID,
            self::SUBMITTED,
            self::SHIPPED,
            self::DELIVERED,
            self::CANCELLED,
            self::FAILED,
        ];
    }

    /**
     * Get status labels for UI
     */
    public static function labels(): array
    {
        return [
            self::PENDING => 'Pending Payment',
            self::PAID => 'Paid',
            self::PROCESSING => 'Processing',
            self::SUBMITTED => 'Submitted to Carrier',
            self::LABEL_READY => 'Label Ready',
            self::PICKED_UP => 'Picked Up',
            self::SHIPPED => 'Shipped',
            self::IN_TRANSIT => 'In Transit',
            self::OUT_FOR_DELIVERY => 'Out for Delivery',
            self::DELIVERED => 'Delivered',
            self::CANCELLED => 'Cancelled',
            self::FAILED => 'Failed',
            self::RETURNED => 'Returned',
            self::ON_HOLD => 'On Hold',
            self::CUSTOMS_PENDING => 'Customs Pending',
            self::CUSTOMS_CLEARED => 'Customs Cleared',
            self::CUSTOMS_HOLD => 'Customs Hold',
        ];
    }

    /**
     * Get label for a specific status
     */
    public static function label(string $status): string
    {
        return self::labels()[$status] ?? ucfirst($status);
    }

    /**
     * Get badge color class for status (DaisyUI)
     */
    public static function badgeClass(string $status): string
    {
        return match ($status) {
            self::PENDING => 'badge-warning',
            self::PAID => 'badge-info',
            self::PROCESSING, self::SUBMITTED, self::LABEL_READY => 'badge-info',
            self::PICKED_UP, self::SHIPPED, self::IN_TRANSIT => 'badge-primary',
            self::OUT_FOR_DELIVERY => 'badge-accent',
            self::DELIVERED, self::CUSTOMS_CLEARED => 'badge-success',
            self::CANCELLED, self::FAILED, self::RETURNED => 'badge-error',
            self::ON_HOLD, self::CUSTOMS_PENDING, self::CUSTOMS_HOLD => 'badge-warning',
            default => 'badge-ghost',
        };
    }

    /**
     * Get icon for status
     */
    public static function icon(string $status): string
    {
        return match ($status) {
            self::PENDING => 'fa-clock',
            self::PAID => 'fa-credit-card',
            self::PROCESSING => 'fa-cog',
            self::SUBMITTED => 'fa-paper-plane',
            self::LABEL_READY => 'fa-tag',
            self::PICKED_UP => 'fa-hand-holding-box',
            self::SHIPPED, self::IN_TRANSIT => 'fa-truck',
            self::OUT_FOR_DELIVERY => 'fa-truck-fast',
            self::DELIVERED => 'fa-check-circle',
            self::CANCELLED => 'fa-times-circle',
            self::FAILED => 'fa-exclamation-triangle',
            self::RETURNED => 'fa-undo',
            self::ON_HOLD => 'fa-pause-circle',
            self::CUSTOMS_PENDING, self::CUSTOMS_HOLD => 'fa-passport',
            self::CUSTOMS_CLEARED => 'fa-check-square',
            default => 'fa-box',
        };
    }

    /**
     * Check if status indicates active shipment
     */
    public static function isActive(string $status): bool
    {
        return in_array($status, [
            self::PROCESSING,
            self::SUBMITTED,
            self::LABEL_READY,
            self::PICKED_UP,
            self::SHIPPED,
            self::IN_TRANSIT,
            self::OUT_FOR_DELIVERY,
            self::CUSTOMS_PENDING,
        ]);
    }

    /**
     * Check if status is a terminal state
     */
    public static function isTerminal(string $status): bool
    {
        return in_array($status, [
            self::DELIVERED,
            self::CANCELLED,
            self::FAILED,
            self::RETURNED,
        ]);
    }

    /**
     * Check if status indicates a problem
     */
    public static function isException(string $status): bool
    {
        return in_array($status, [
            self::CANCELLED,
            self::FAILED,
            self::RETURNED,
            self::ON_HOLD,
            self::CUSTOMS_HOLD,
        ]);
    }

    /**
     * Get allowed next statuses (state machine)
     */
    public static function allowedTransitions(string $currentStatus): array
    {
        return match ($currentStatus) {
            self::PENDING => [self::PAID, self::CANCELLED],
            self::PAID => [self::PROCESSING, self::SUBMITTED, self::CANCELLED],
            self::PROCESSING => [self::SUBMITTED, self::FAILED, self::CANCELLED],
            self::SUBMITTED => [self::LABEL_READY, self::FAILED, self::CANCELLED],
            self::LABEL_READY => [self::PICKED_UP, self::SHIPPED, self::CANCELLED],
            self::PICKED_UP => [self::SHIPPED, self::IN_TRANSIT],
            self::SHIPPED => [self::IN_TRANSIT, self::CUSTOMS_PENDING, self::DELIVERED],
            self::IN_TRANSIT => [self::OUT_FOR_DELIVERY, self::CUSTOMS_PENDING, self::DELIVERED, self::ON_HOLD],
            self::OUT_FOR_DELIVERY => [self::DELIVERED, self::RETURNED],
            self::CUSTOMS_PENDING => [self::CUSTOMS_CLEARED, self::CUSTOMS_HOLD],
            self::CUSTOMS_CLEARED => [self::IN_TRANSIT, self::DELIVERED],
            self::CUSTOMS_HOLD => [self::CUSTOMS_CLEARED, self::RETURNED, self::ON_HOLD],
            self::ON_HOLD => [self::IN_TRANSIT, self::CANCELLED, self::RETURNED],
            default => [],
        };
    }

    /**
     * Get statuses for validation rules (basic compatibility)
     */
    public static function validationRule(): string
    {
        return 'in:' . implode(',', self::basic());
    }

    /**
     * Get all statuses for validation rules (full lifecycle)
     */
    public static function fullValidationRule(): string
    {
        return 'in:' . implode(',', self::all());
    }

    /**
     * Get statuses grouped for operator-focused filter tabs
     * Simplified 4-category system for clarity
     */
    public static function grouped(): array
    {
        return [
            'ready_to_prepare' => [
                'label' => 'Ready to Prepare',
                'icon' => 'fa-box-open',
                'color' => 'orange',
                'statuses' => [self::PAID],
            ],
            'awaiting_pickup' => [
                'label' => 'Awaiting Pickup',
                'icon' => 'fa-tag',
                'color' => 'blue',
                'statuses' => [self::PROCESSING, self::SUBMITTED, self::LABEL_READY],
            ],
            'in_transit' => [
                'label' => 'In Transit',
                'icon' => 'fa-truck',
                'color' => 'purple',
                'statuses' => [self::PICKED_UP, self::SHIPPED, self::IN_TRANSIT, self::OUT_FOR_DELIVERY, self::CUSTOMS_PENDING, self::CUSTOMS_CLEARED],
            ],
            'completed' => [
                'label' => 'Completed',
                'icon' => 'fa-check-circle',
                'color' => 'green',
                'statuses' => [self::DELIVERED],
            ],
            'needs_attention' => [
                'label' => 'Needs Attention',
                'icon' => 'fa-exclamation-triangle',
                'color' => 'red',
                'statuses' => [self::FAILED, self::RETURNED, self::ON_HOLD, self::CUSTOMS_HOLD, self::CANCELLED],
            ],
        ];
    }

    /**
     * Get operator-friendly category for a status
     */
    public static function getCategory(string $status): ?string
    {
        foreach (self::grouped() as $key => $group) {
            if (in_array($status, $group['statuses'])) {
                return $key;
            }
        }
        return null;
    }

    /**
     * Get operator-friendly label for a status (simplified)
     */
    public static function operatorLabel(string $status): string
    {
        $category = self::getCategory($status);
        if ($category) {
            return self::grouped()[$category]['label'];
        }
        return self::label($status);
    }

    /**
     * Format for frontend (Vue props)
     */
    public static function forFrontend(): array
    {
        $result = [];
        foreach (self::all() as $status) {
            $result[] = [
                'value' => $status,
                'label' => self::label($status),
                'badge' => self::badgeClass($status),
                'icon' => self::icon($status),
                'isActive' => self::isActive($status),
                'isTerminal' => self::isTerminal($status),
                'isException' => self::isException($status),
            ];
        }
        return $result;
    }
}
