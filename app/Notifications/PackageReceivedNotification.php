<?php

namespace App\Notifications;

use App\Models\Package;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a new package is received at the warehouse.
 */
class PackageReceivedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Package $package
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $warehouseName = $this->package->warehouse?->name ?? 'our warehouse';
        $from = $this->package->from ?? 'Unknown Sender';

        return (new MailMessage)
            ->subject("📦 New Package Received - {$this->package->package_id}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("Great news! A new package has arrived at {$warehouseName}.")
            ->line('## Package Information')
            ->line("**Package ID:** {$this->package->package_id}")
            ->line("**From:** {$from}")
            ->line("**Store Tracking:** {$this->package->store_tracking_id}")
            ->action('View Package Details', url(route('customer.dashboard')))
            ->line('Log in to your dashboard to view more details and take action.');
    }

    /**
     * Get the array representation of the notification (for database).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'package_received',
            'package_id' => $this->package->id,
            'package_number' => $this->package->package_id,
            'from' => $this->package->from,
            'store_tracking_id' => $this->package->store_tracking_id,
            'message' => "New package received: {$this->package->package_id}",
        ];
    }
}
