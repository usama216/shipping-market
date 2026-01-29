<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Ship;
use App\Notifications\PackageReceivedNotification;
use App\Notifications\PackageStatusChangedNotification;
use App\Notifications\ShipmentCreatedNotification;
use App\Notifications\ShipmentDeliveredNotification;

/**
 * Centralized service for dispatching customer notifications.
 * 
 * All notifications are sent via both email and database channels
 * for a consistent customer communication experience.
 */
class NotificationService
{
    /**
     * Notify customer when a new package is received at the warehouse.
     */
    public function notifyPackageReceived(Customer $customer, Package $package): void
    {
        $customer->notify(new PackageReceivedNotification($package));
    }

    /**
     * Notify customer when their package status changes.
     * 
     * @param string $oldStatus The previous status name
     */
    public function notifyPackageStatusChanged(Customer $customer, Package $package, string $oldStatus): void
    {
        // Don't notify for minor status changes if desired
        // For now, notify on all status changes
        $customer->notify(new PackageStatusChangedNotification($package, $oldStatus));
    }

    /**
     * Notify customer when they successfully create a shipment request.
     */
    public function notifyShipmentCreated(Customer $customer, Ship $ship): void
    {
        $customer->notify(new ShipmentCreatedNotification($ship));
    }

    /**
     * Notify customer when their shipment is delivered.
     */
    public function notifyShipmentDelivered(Customer $customer, Ship $ship): void
    {
        $customer->notify(new ShipmentDeliveredNotification($ship));
    }
}
