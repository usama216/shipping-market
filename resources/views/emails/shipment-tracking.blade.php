@component('mail::message')
# Your Shipment Is On Its Way! 🚚

Great news! Your shipment has been submitted to **{{ $carrierName }}** and is being processed.

## Tracking Information

**Tracking Number:** {{ $ship->carrier_tracking_number }}

**Carrier:** {{ $carrierName }}

@component('mail::button', ['url' => $trackingUrl])
Track Your Package
@endcomponent

## Shipment Details

- **Shipment ID:** #{{ $ship->id }}
- **Total Weight:** {{ $ship->total_weight }} kg
- **Shipping Cost:** ${{ number_format($ship->estimated_shipping_charges, 2) }}

## What's Next?

1. Your package will be picked up by {{ $carrierName }}
2. You can track the delivery progress using the button above
3. You'll receive updates as your package moves through the shipping network

---

If you have any questions about your shipment, please contact our support team.

Thanks,<br>
{{ config('app.name') }}

@component('mail::subcopy')
Can't click the button? Copy and paste this URL into your browser: {{ $trackingUrl }}
@endcomponent
@endcomponent