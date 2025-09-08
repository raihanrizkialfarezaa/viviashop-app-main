<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>All Products Barcode</title>
</head>
<body>
    <style>
        @page {
            margin: 5mm;
            size: A4 landscape;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 12px;
        }

        .barcode-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
            align-items: flex-start;
            gap: 2mm;
            padding: 2mm;
        }

        .barcode-item {
            width: 45mm;
            height: 25mm;
            border: 1px solid #333;
            padding: 1mm;
            text-align: center;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-inside: avoid;
            box-sizing: border-box;
            background: white;
        }

        .product-info {
            font-size: 6pt;
            line-height: 1.1;
            margin-bottom: 1mm;
        }

        .product-name {
            font-weight: bold;
            font-size: 7pt;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            margin-bottom: 0.5mm;
        }

        .product-sku {
            font-size: 6pt;
            color: #666;
            margin-bottom: 0.5mm;
        }

        .barcode-section {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        .barcode-code {
            font-family: 'Courier New', monospace;
            font-size: 6pt;
            letter-spacing: 1px;
            margin-top: 0.5mm;
        }

        .barcode-item:nth-child(6n+1) {
            clear: left;
        }

        .page-break {
            page-break-before: always;
        }
    </style>

    <div class="barcode-container">
        @foreach ($data as $index => $produk)
            @if($index > 0 && $index % 30 == 0)
                </div>
                <div class="page-break"></div>
                <div class="barcode-container">
            @endif
            <div class="barcode-item">
                <div class="product-info">
                    <div class="product-name" title="{{ $produk->name }}">{{ Str::limit($produk->name, 20) }}</div>
                    <div class="product-sku">SKU: {{ $produk->sku }}</div>
                </div>
                <div class="barcode-section">
                    @if($produk->barcode)
                        {!! DNS1D::getBarcodeHTML($produk->barcode, 'C128', 1, 12) !!}
                        <div class="barcode-code">{{ $produk->barcode }}</div>
                    @else
                        <div style="font-size: 8pt; color: #999;">No Barcode</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
