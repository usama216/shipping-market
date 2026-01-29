<?php

namespace App\Notifications;

use App\Models\Ship;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notification sent when a shipment is marked as delivered.
 */
class ShipmentDeliveredNotification extends Notification
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
        $trackingNumber = $this->ship->carrier_tracking_number ?? 'N/A';
        $carrierName = $this->getCarrierDisplayName();

        return (new MailMessage)
            ->subject("✅ Shipment Delivered - #{$this->ship->id}")
            ->greeting("Hello {$notifiable->name}!")
            ->line('Great news! Your shipment has been delivered.')
            ->line('## Shipment Information')
            ->line("**Shipment ID:** #{$this->ship->id}")
            ->line("**Carrier:** {$carrierName}")
            ->line("**Tracking Number:** {$trackingNumber}")
            ->action('View Shipment Details', url(route('customer.shipment.details', $this->ship->id)))
            ->line('Thank you for choosing our shipping service!')
            ->line('If you have any questions or concerns about your delivery, please contact our support team.');
    }

    /**
     * Get the array representation of the notification (for database).
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shipment_delivered',
            'ship_id' => $this->ship->id,
            'carrier_tracking_number' => $this->ship->carrier_tracking_number,
            'carrier_name' => $this->ship->carrier_name,
            'message' => "Shipment #{$this->ship->id} has been delivered",
        ];
    }

    /**
     * Get carrier display name.
     */
    private function getCarrierDisplayName(): string
    {
        return match ($this->ship->carrier_name) {
            'fedex' => 'FedEx',
            'dhl' => 'DHL Express',
            'ups' => 'UPS',
            default => ucfirst($this->ship->carrier_name ?? 'Carrier'),
        };
    }
}
