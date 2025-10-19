<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - {{ $order->order_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            padding: 30px;
            text-align: center;
            border-radius: 10px 10px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 30px;
            border-radius: 0 0 10px 10px;
        }
        .order-details {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .order-items {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .order-items th,
        .order-items td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        .order-items th {
            background: #f3f4f6;
            font-weight: 600;
        }
        .total-row {
            font-weight: bold;
            font-size: 1.1em;
            border-top: 2px solid #10b981;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
        }
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #10b981;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 10px 0;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        .status-paid {
            background: #dcfce7;
            color: #166534;
        }
        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Order Confirmation</h1>
        <p>Thank you for your order!</p>
    </div>

    <div class="content">
        <p>Dear {{ $user->name }},</p>

        <p>Thank you for shopping with Weekender! Your order has been successfully placed and is being processed.</p>

        <div class="order-details">
            <h2>Order Details</h2>
            <p><strong>Order Number:</strong> {{ $order->order_number }}</p>
            <p><strong>Order Date:</strong> {{ $order->created_at->format('F j, Y \a\t g:i A') }}</p>
            <p><strong>Status:</strong>
                <span class="status-badge status-{{ $order->status }}">
                    {{ ucfirst($order->status) }}
                </span>
            </p>
        </div>

        <h3>Items Ordered</h3>
        <table class="order-items">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->productVariant->product->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format($item->price_at_time, 2) }}</td>
                    <td>${{ number_format($item->line_total, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="order-details">
            <h3>Order Summary</h3>
            <p><strong>Subtotal:</strong> ${{ number_format($order->subtotal, 2) }}</p>
            <p><strong>Tax:</strong> ${{ number_format($order->tax_amount, 2) }}</p>
            <p><strong>Shipping:</strong> ${{ number_format($order->shipping_amount, 2) }}</p>
            <p class="total-row"><strong>Total:</strong> ${{ number_format($order->total_amount, 2) }}</p>
        </div>

        @if($order->billingAddress)
        <div class="order-details">
            <h3>Billing Address</h3>
            <p>
                {{ $order->billingAddress->first_name }} {{ $order->billingAddress->last_name }}<br>
                {{ $order->billingAddress->address_line_1 }}<br>
                @if($order->billingAddress->address_line_2)
                    {{ $order->billingAddress->address_line_2 }}<br>
                @endif
                {{ $order->billingAddress->city }}, {{ $order->billingAddress->state }} {{ $order->billingAddress->postal_code }}<br>
                {{ $order->billingAddress->country }}
            </p>
        </div>
        @endif

        <div class="footer">
            <p>
                <a href="{{ url('/orders/' . $order->id) }}" class="button">
                    View Order Details
                </a>
            </p>
            <p>
                If you have any questions about your order, please don't hesitate to contact our support team.
            </p>
            <p>
                Thank you for choosing Weekender for your solar energy needs!<br>
                <strong>The Weekender Team</strong>
            </p>
        </div>
    </div>
</body>
</html>