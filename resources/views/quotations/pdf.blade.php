<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Quotation #{{ $quotation->id }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            font-size: 24px;
            margin: 0;
            color: #6366f1;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h3 {
            font-size: 14px;
            margin-bottom: 10px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
        }
        .total-row {
            font-weight: bold;
            background-color: #f9fafb;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Quotation</h1>
        <p>Reference: QT-{{ str_pad($quotation->id, 5, '0', STR_PAD_LEFT) }}</p>
        <p>Date: {{ $quotation->created_at->format('d M Y') }}</p>
    </div>

    <div class="info-section">
        <h3>Client Information</h3>
        <p>
            <strong>{{ $quotation->client->name ?? 'N/A' }}</strong><br>
            {{ $quotation->client->email ?? '' }}<br>
            {{ $quotation->client->phone ?? '' }}
        </p>
    </div>

    <div class="info-section">
        <h3>Event Details</h3>
        <p>
            <strong>Event Date:</strong> {{ $quotation->event_date ? \Carbon\Carbon::parse($quotation->event_date)->format('d M Y') : 'TBD' }}<br>
            <strong>Venue:</strong> {{ $quotation->venue->name ?? 'TBD' }}<br>
            <strong>Valid Until:</strong> {{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('d M Y') : 'N/A' }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th style="text-align: right;">Amount</th>
            </tr>
        </thead>
        <tbody>
            @forelse($quotation->items ?? [] as $item)
            <tr>
                <td>{{ $item['name'] ?? 'Item' }}</td>
                <td>{{ $item['description'] ?? '' }}</td>
                <td style="text-align: right;">{{ number_format($item['amount'] ?? 0, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="3" style="text-align: center;">No items</td>
            </tr>
            @endforelse
            <tr class="total-row">
                <td colspan="2" style="text-align: right;">Total</td>
                <td style="text-align: right;">{{ number_format($quotation->total ?? 0, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        <p>Thank you for considering our services!</p>
        <p>This quotation is valid until {{ $quotation->valid_until ? \Carbon\Carbon::parse($quotation->valid_until)->format('d M Y') : 'further notice' }}.</p>
    </div>
</body>
</html>
