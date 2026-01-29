<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Commercial Invoice #{{ $invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #333;
        }

        .page {
            padding: 20px 30px;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 18pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .header p {
            font-size: 9pt;
            color: #666;
            margin-top: 3px;
        }

        /* Invoice Info */
        .invoice-info {
            width: 100%;
            margin-bottom: 15px;
        }

        .invoice-info table {
            width: 100%;
        }

        .invoice-info td {
            padding: 3px 0;
            vertical-align: top;
        }

        .invoice-info .label {
            font-weight: bold;
            width: 130px;
        }

        /* Address Boxes */
        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }

        .address-box {
            display: table-cell;
            width: 48%;
            border: 1px solid #000;
            padding: 10px;
            vertical-align: top;
        }

        .address-box.left {
            margin-right: 4%;
        }

        .address-box h3 {
            font-size: 10pt;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
            margin-bottom: 8px;
            background: #f5f5f5;
            padding: 5px;
            margin: -10px -10px 8px -10px;
        }

        .address-box p {
            margin-bottom: 3px;
        }

        .address-box .company {
            font-weight: bold;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 9pt;
        }

        .items-table th {
            background: #333;
            color: #fff;
            padding: 8px 5px;
            text-align: left;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
        }

        .items-table th.center,
        .items-table td.center {
            text-align: center;
        }

        .items-table th.right,
        .items-table td.right {
            text-align: right;
        }

        .items-table td {
            padding: 6px 5px;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
        }

        .items-table tr:nth-child(even) td {
            background: #fafafa;
        }

        .items-table .description {
            max-width: 180px;
        }

        /* Totals */
        .totals {
            width: 300px;
            margin-left: auto;
            margin-bottom: 20px;
        }

        .totals table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals td {
            padding: 5px 8px;
            border: 1px solid #ddd;
        }

        .totals .label {
            font-weight: bold;
            background: #f5f5f5;
        }

        .totals .total-row {
            background: #333;
            color: #fff;
            font-weight: bold;
            font-size: 11pt;
        }

        .totals .total-row td {
            border-color: #333;
        }

        /* Footer */
        .declaration {
            border: 1px solid #000;
            padding: 12px;
            margin-bottom: 15px;
            font-size: 9pt;
        }

        .declaration h4 {
            font-size: 10pt;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .declaration p {
            margin-bottom: 5px;
        }

        .signature {
            margin-top: 20px;
        }

        .signature-line {
            border-bottom: 1px solid #000;
            width: 250px;
            margin: 25px 0 5px 0;
        }

        .signature p {
            font-size: 9pt;
        }

        /* Terms */
        .terms {
            font-size: 8pt;
            color: #666;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }

        /* Clearfix */
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
    </style>
</head>

<body>
    <div class="page">
        <!-- Header -->
        <div class="header">
            <h1>Commercial Invoice</h1>
            <p>For Customs Purposes Only</p>
        </div>

        <!-- Invoice Info -->
        <div class="invoice-info">
            <table>
                <tr>
                    <td class="label">Invoice Number:</td>
                    <td>{{ $invoice_number }}</td>
                    <td class="label">Invoice Date:</td>
                    <td>{{ $invoice_date }}</td>
                </tr>
                <tr>
                    <td class="label">Incoterm:</td>
                    <td>{{ $incoterm }}</td>
                    <td class="label">Currency:</td>
                    <td>{{ $currency }}</td>
                </tr>
                <tr>
                    <td class="label">Reason for Export:</td>
                    <td>{{ $reason_for_export }}</td>
                    <td class="label">Total Packages:</td>
                    <td>{{ $total_packages }}</td>
                </tr>
            </table>
        </div>

        <!-- Addresses -->
        <table style="width: 100%; margin-bottom: 15px;">
            <tr>
                <td style="width: 48%; vertical-align: top; padding-right: 10px;">
                    <div class="address-box" style="display: block; width: 100%;">
                        <h3>Shipper / Exporter</h3>
                        <p class="company">{{ $shipper['company'] }}</p>
                        <p>{{ $shipper['name'] }}</p>
                        <p>{{ $shipper['address'] }}</p>
                        @if($shipper['address_2'])
                            <p>{{ $shipper['address_2'] }}</p>
                        @endif
                        <p>{{ $shipper['city'] }}, {{ $shipper['state'] }} {{ $shipper['postal_code'] }}</p>
                        <p>{{ $shipper['country'] }}</p>
                        <p style="margin-top: 5px;">Tel: {{ $shipper['phone'] }}</p>
                        @if($shipper['email'])
                            <p>Email: {{ $shipper['email'] }}</p>
                        @endif
                    </div>
                </td>
                <td style="width: 48%; vertical-align: top; padding-left: 10px;">
                    <div class="address-box" style="display: block; width: 100%;">
                        <h3>Consignee / Importer</h3>
                        @if($consignee['company'])
                            <p class="company">{{ $consignee['company'] }}</p>
                        @endif
                        <p class="company">{{ $consignee['name'] }}</p>
                        <p>{{ $consignee['address'] }}</p>
                        @if($consignee['address_2'])
                            <p>{{ $consignee['address_2'] }}</p>
                        @endif
                        <p>{{ $consignee['city'] }}@if($consignee['state']), {{ $consignee['state'] }}@endif
                            {{ $consignee['postal_code'] }}</p>
                        <p>{{ $consignee['country'] }}</p>
                        @if($consignee['phone'])
                            <p style="margin-top: 5px;">Tel: {{ $consignee['phone'] }}</p>
                        @endif
                        @if($consignee['email'])
                            <p>Email: {{ $consignee['email'] }}</p>
                        @endif
                    </div>
                </td>
            </tr>
        </table>

        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 25px;">#</th>
                    <th class="description">Description of Goods</th>
                    <th class="center" style="width: 70px;">HS Code</th>
                    <th class="center" style="width: 50px;">Origin</th>
                    <th class="center" style="width: 40px;">Qty</th>
                    <th class="right" style="width: 60px;">Unit Value</th>
                    <th class="right" style="width: 70px;">Total Value</th>
                    <th class="right" style="width: 60px;">Weight</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td class="center">{{ $item['number'] }}</td>
                        <td class="description">{{ $item['description'] }}</td>
                        <td class="center">{{ $item['hs_code'] }}</td>
                        <td class="center">{{ $item['country_of_origin'] }}</td>
                        <td class="center">{{ $item['quantity'] }}</td>
                        <td class="right">{{ $currency }} {{ $item['unit_value'] }}</td>
                        <td class="right">{{ $currency }} {{ $item['total_value'] }}</td>
                        <td class="right">{{ $item['weight'] }} {{ $item['weight_unit'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="totals">
            <table>
                <tr>
                    <td class="label">Total Items</td>
                    <td class="right">{{ count($items) }}</td>
                </tr>
                <tr>
                    <td class="label">Total Weight</td>
                    <td class="right">{{ $total_weight }} LB</td>
                </tr>
                <tr class="total-row">
                    <td>Total Declared Value</td>
                    <td class="right">{{ $currency }} {{ $declared_value }}</td>
                </tr>
            </table>
        </div>

        <!-- Declaration -->
        <div class="declaration">
            <h4>Declaration</h4>
            <p>I hereby certify that the information contained in this invoice is true and correct and that the contents
                of this shipment are as stated above.</p>
            <p>Export License/Permit: {{ $exporter_id }}</p>
        </div>

        <!-- Signature -->
        <div class="signature">
            <p><strong>Authorized Signature:</strong></p>
            <div class="signature-line"></div>
            <p>{{ $signature_title }} {{ $signature_name }}</p>
            <p style="color: #666; font-size: 8pt;">Date: {{ $invoice_date }}</p>
        </div>

        <!-- Terms -->
        <div class="terms">
            <p>This invoice is issued for customs purposes only. The goods described above are of U.S. origin unless
                otherwise stated.
                For questions regarding this shipment, please contact the shipper at the address listed above.</p>
        </div>
    </div>
</body>

</html>