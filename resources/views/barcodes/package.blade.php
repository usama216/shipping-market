<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Package Barcode - {{ $packageId }}</title>
    <style>
        @page {
            margin: 0;
            size: 2in 1in;
        }
        body {
            margin: 0;
            padding: 10px;
            font-family: Arial, sans-serif;
            text-align: center;
        }
        .barcode-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
        }
        .barcode-image {
            max-width: 100%;
            height: auto;
            margin-bottom: 5px;
        }
        .package-id {
            font-size: 14px;
            font-weight: bold;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="barcode-container">
        <img src="data:image/png;base64,{{ $barcodeBase64 }}" alt="Barcode" class="barcode-image">
        <div class="package-id">{{ $packageId }}</div>
    </div>
</body>
</html>
