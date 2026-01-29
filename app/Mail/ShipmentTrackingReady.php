<?php

namespace App\Mail;

use App\Models\Ship;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Email notification sent when carrier tracking is ready
 */
class ShipmentTrackingReady extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ship $ship
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Shipment Is On Its Way! - Tracking #' . $this->ship->carrier_tracking_number,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.shipment-tracking',
            with: [
                'ship' => $this->ship,
                'trackingUrl' => $this->getTrackingUrl(),
                'carrierName' => $this->getCarrierDisplayName(),
            ],
        );
    }

    /**
     * Get tracking URL based on carrier
     */
    private function getTrackingUrl(): string
    {
        $tracking = $this->ship->carrier_tracking_number;

        return match ($this->ship->carrier_name) {
            'fedex' => "https://www.fedex.com/fedextrack/?trknbr={$tracking}",
            'dhl' => "https://www.dhl.com/en/express/tracking.html?AWB={$tracking}",
            'ups' => "https://www.ups.com/track?tracknum={$tracking}",
            default => '#',
        };
    }

    /**
     * Get carrier display name
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

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
