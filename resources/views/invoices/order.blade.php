<!DOCTYPE html>
<html>
    <head>
        <style>
            body { font-family: 'Helvetica', sans-serif; color: #1a1a1a; line-height: 1.6; }
            .header { text-align: center; margin-bottom: 50px; border-bottom: 2px solid #f4f4f4; padding-bottom: 20px; }
            .invoice-info { margin-bottom: 40px; }
            .invoice-info table { width: 100%; }
            .section-title { font-size: 10px; font-weight: 900; text-transform: uppercase; color: #999; letter-spacing: 2px; margin-bottom: 10px; }
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 40px; }
            .items-table th { text-align: left; font-size: 10px; font-weight: 900; text-transform: uppercase; padding: 10px 0; border-bottom: 1px solid #eee; }
            .items-table td { padding: 15px 0; border-bottom: 1px solid #f9f9f9; font-size: 12px; }
            .total-section { text-align: right; }
            .total-amount { font-size: 24px; font-weight: 900; color: #c5a059; /* Your Primary Gold/Primary Color */ }
            .footer { margin-top: 100px; text-align: center; font-size: 10px; color: #ccc; text-transform: uppercase; }
        </style>
    </head>
    <body>
        <div class="header">
            <img src="{{ public_path('images/logo-text.png') }}" alt="Carlsson Digital Commerce" style="height: 50px; margin-bottom: 10px;">
            <div style="font-size: 10px; text-transform: uppercase; letter-spacing: 3px;">Official Invoice</div>
        </div>

        <div class="invoice-info">
            <table>
                <tr>
                    <td width="50%">
                        <div class="section-title">Customer</div>
                        <strong>{{ $order->customer->name }}</strong><br>
                        {{ $order->customer->email }}
                    </td>
                    <td width="50%" style="text-align: right;">
                        <div class="section-title">
                            {{ $order->shipping_address ? 'Shipping Destination' : 'Boutique Pickup Location' }}
                        </div>
                        @if($order->shipping_address)
                            <strong>Standard Shipping ({{ $order->courier_service }})</strong><br>
                            {{ $order->shipping_address ?? '' }}
                        @else
                            <strong>{{ $order->branch->name }}</strong><br>
                            <span style="font-size: 10px; color: #666;">
                                Please bring your ID and Order QR for collection.
                            </span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <div class="section-title">Purchase Summary</div>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Item Description</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td><strong>{{ $item->product->name }}</strong></td>
                    <td>{{ $item->quantity }}</td>
                    <td>Rp {{ number_format($item->unit_price) }}</td>
                    <td style="text-align: right;">Rp {{ number_format($item->total_price) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="total-section">
            <div class="section-title">Total Amount Paid</div>
            <div class="total-amount">Rp {{ number_format($order->total_amount) }}</div>
        </div>

        <div class="footer">
            Thank you for choosing Carlsson Digital Commerce.<br>
            All luxury pieces are verified for authenticity.
        </div>

        <div style="margin-top: 50px; padding: 20px; border: 1px solid #eee; border-radius: 10px;">
            <div class="section-title">Terms & Authenticity</div>
            <ul style="font-size: 9px; color: #777; padding-left: 15px;">
                <li>All items are guaranteed authentic by Republican Jewelry.</li>
                <li>For in-store pickups, items must be collected within 7 days of the "Ready" status.</li>
                <li>Returns or exchanges are subject to our boutique's inspection and policy.</li>
                @if($order->shipping_address)
                    <li>Insurance for shipping is covered by the assigned courier ({{ $order->courier_service }}).</li>
                @endif
            </ul>
        </div>
    </body>
</html>
