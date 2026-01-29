<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice for Customs Purposes - {{ $invoiceNumber }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            color: #000;
            line-height: 1.4;
        }
        .invoice-container {
            max-width: 8.5in;
            margin: 0 auto;
            padding: 0.5in;
        }
        .header {
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        .header h1 {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .company-info {
            margin-bottom: 20px;
        }
        .company-info h2 {
            font-size: 12pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .two-column {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 0 10px;
        }
        .section {
            margin-bottom: 20px;
        }
        .section-title {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table th,
        table td {
            border: 1px solid #000;
            padding: 6px;
            text-align: left;
            font-size: 9pt;
        }
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .totals {
            margin-top: 20px;
            margin-left: auto;
            width: 300px;
        }
        .totals table {
            margin-bottom: 0;
        }
        .totals td {
            border: none;
            padding: 4px;
        }
        .totals .total-row {
            border-top: 2px solid #000;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 8pt;
        }
        .signature-section {
            margin-top: 40px;
        }
        .signature-line {
            border-top: 1px solid #000;
            width: 300px;
            margin-top: 40px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="header">
            <h1>INVOICE FOR CUSTOMS PURPOSES</h1>
            <div class="two-column">
                <div class="column">
                    <strong>Invoice Number:</strong> {{ $invoiceNumber }}<br>
                    <strong>Invoice Date:</strong> {{ $invoiceDate }}
                </div>
                <div class="column text-right">
                    @if($trackingNumber)
                        <strong>Tracking Number:</strong> {{ $trackingNumber }}<br>
                    @endif
                    <strong>Shipment ID:</strong> {{ $ship->tracking_number ?? $ship->id }}
                </div>
            </div>
        </div>

        <!-- Company Information -->
        <div class="company-info">
            <h2>Exporter / Shipper</h2>
            <div>
                <strong>{{ $company['name'] }}</strong><br>
                {{ $company['full_address'] }}
            </div>
        </div>

        <!-- Shipment Details -->
        <div class="two-column">
            <div class="column">
                <div class="section">
                    <div class="section-title">Consignee / Buyer</div>
                    <div>
                        <strong>{{ $ship->customerAddress->full_name ?? 'N/A' }}</strong><br>
                        {{ $ship->customerAddress->address_line_1 ?? '' }}<br>
                        @if($ship->customerAddress->address_line_2)
                            {{ $ship->customerAddress->address_line_2 }}<br>
                        @endif
                        {{ $ship->customerAddress->city ?? '' }}, 
                        {{ $ship->customerAddress->state ?? '' }} 
                        {{ $ship->customerAddress->postal_code ?? '' }}<br>
                        {{ $ship->customerAddress->country_code ?? 'US' }}
                        @if($clientTaxId)
                            <br><br><strong>Tax ID:</strong> {{ $clientTaxId }}
                        @endif
                    </div>
                </div>
            </div>
            <div class="column">
                <div class="section">
                    <div class="section-title">Export Compliance</div>
                    <table style="border: none;">
                        <tr>
                            <td style="border: none; padding: 2px;"><strong>Incoterm:</strong></td>
                            <td style="border: none; padding: 2px;">{{ $compliance['incoterm'] }}</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2px;"><strong>Exporter ID:</strong></td>
                            <td style="border: none; padding: 2px;">{{ $compliance['exporter_id_license'] }}</td>
                        </tr>
                        <tr>
                            <td style="border: none; padding: 2px;"><strong>US Filing Type:</strong></td>
                            <td style="border: none; padding: 2px;">{{ $compliance['us_filing_type'] }}</td>
                        </tr>
                        @if($compliance['exporter_code'])
                        <tr>
                            <td style="border: none; padding: 2px;"><strong>Exporter Code:</strong></td>
                            <td style="border: none; padding: 2px;">{{ $compliance['exporter_code'] }}</td>
                        </tr>
                        @endif
                        @if($compliance['itn_number'])
                        <tr>
                            <td style="border: none; padding: 2px;"><strong>ITN:</strong></td>
                            <td style="border: none; padding: 2px;">{{ $compliance['itn_number'] }}</td>
                        </tr>
                        @endif
                        @if(!empty($sellerNames))
                        <tr>
                            <td style="border: none; padding: 2px;"><strong>Seller:</strong></td>
                            <td style="border: none; padding: 2px;">{{ implode(', ', $sellerNames) }}</td>
                        </tr>
                        @endif
                    </table>
                    <div style="margin-top: 10px; padding: 8px; background-color: #f0f0f0; border: 1px solid #ccc;">
                        <strong>NO EEI REQUIRED – FTR 30.37(a)</strong>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items Table -->
        <div class="section">
            <div class="section-title">Items Description</div>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%;">#</th>
                        <th style="width: 25%;">Description</th>
                        <th style="width: 12%;">HS Code</th>
                        <th style="width: 8%;" class="text-center">Qty</th>
                        <th style="width: 10%;" class="text-right">Unit Value</th>
                        <th style="width: 12%;" class="text-right">Total Value</th>
                        <th style="width: 10%;" class="text-right">Weight</th>
                        <th style="width: 10%;">Origin</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $index => $item)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>
                            <strong>{{ $item['title'] }}</strong>
                            @if($item['description'])
                                <br><small>{{ $item['description'] }}</small>
                            @endif
                            @if($item['material'])
                                <br><small>Material: {{ $item['material'] }}</small>
                            @endif
                        </td>
                        <td>{{ $item['hs_code'] ?? 'N/A' }}</td>
                        <td class="text-center">{{ $item['quantity'] }}</td>
                        <td class="text-right">${{ number_format($item['unit_value'], 2) }}</td>
                        <td class="text-right">${{ number_format($item['total_value'], 2) }}</td>
                        <td class="text-right">{{ number_format($item['weight'], 2) }} {{ strtoupper($item['weight_unit']) }}</td>
                        <td>US</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td style="border: none;"><strong>Total Quantity:</strong></td>
                    <td style="border: none;" class="text-right">{{ $totals['total_quantity'] }}</td>
                </tr>
                <tr>
                    <td style="border: none;"><strong>Total Weight:</strong></td>
                    <td style="border: none;" class="text-right">{{ number_format($totals['total_weight'], 2) }} LB</td>
                </tr>
                <tr class="total-row">
                    <td style="border-top: 2px solid #000;"><strong>Total Value ({{ $totals['currency'] }}):</strong></td>
                    <td style="border-top: 2px solid #000;" class="text-right"><strong>${{ number_format($totals['total_value'], 2) }}</strong></td>
                </tr>
            </table>
        </div>

        <!-- Signature -->
        <div class="signature-section">
            <div style="margin-top: 5px;">
                <strong>{{ $compliance['invoice_signature_name'] }}</strong><br>
                Certified true and correct by Packing Specialist<br>
                <span style="font-size: 9pt;">Date: {{ now()->format('F d, Y') }}</span>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>Note:</strong> This is an invoice for customs purposes. All items are for commercial use.</p>
            <p style="margin-top: 5px;">Generated by {{ $company['name'] }} on {{ now()->format('F d, Y') }}</p>
        </div>
    </div>
</body>
</html>
