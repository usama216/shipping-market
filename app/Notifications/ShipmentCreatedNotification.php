<?php

namespace App\Notifications;

use App\Models\Ship;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a customer creates a new shipment request.
 */
class ShipmentCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Ship $ship
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
        $packageCount = $this->ship->packages?->count() ?? 1;
        $totalCharges = number_format($this->ship->estimated_shipping_charges ?? 0, 2);

        return (new MailMessage)
            ->subject("🚀 Shipment Request Confirmed - #{$this->ship->id}")
            ->greeting("Hello {$notifiable->name}!")
            ->line('Your shipment request has been successfully created and paid.')
            ->line('## Shipment Details')
            ->line("**Shipment ID:** #{$this->ship->id}")
            ->line("**Packages:** {$packageCount}")
            ->line("**Total Cost:** \${$totalCharges}")
            ->line('Our team will process your shipment and you will receive tracking information once it ships.')
            ->action('View Shipment', url(route('customer.shipment.details', $this->ship->id)))
            ->line('Thank you for shipping with us!');
    }

    /**
     * Get the array representation of the notification (for database).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shipment_created',
            'ship_id' => $this->ship->id,
            'package_count' => $this->ship->packages?->count() ?? 1,
            'total_charges' => $this->ship->estimated_shipping_charges,
            'message' => "Shipment #{$this->ship->id} created successfully",
        ];
    }
}
