<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ ucfirst($reportType) }} Report - {{ now()->format('Y-m-d') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            color: #1a202c;
            line-height: 1.5;
            padding: 20px;
            font-size: 13px;
        }
        .header {
            margin-bottom: 30px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2d3748;
        }
        .header .meta {
            margin-top: 5px;
            color: #718096;
            font-size: 12px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        .stats-grid.three-cols {
            grid-template-columns: repeat(3, 1fr);
        }
        .stats-grid.two-cols {
            grid-template-columns: repeat(2, 1fr);
        }
        .stat-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            background: #f7fafc;
        }
        .stat-card .label {
            font-size: 11px;
            color: #718096;
            text-transform: uppercase;
            font-weight: bold;
        }
        .stat-card .value {
            font-size: 20px;
            font-weight: bold;
            color: #2d3748;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th {
            background-color: #edf2f7;
            color: #4a5568;
            font-weight: bold;
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #cbd5e0;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        .text-right {
            text-align: right;
        }
        .badge {
            display: inline-block;
            padding: 2px 6px;
            font-size: 10px;
            font-weight: bold;
            border-radius: 4px;
            background-color: #e2e8f0;
            color: #4a5568;
            text-transform: uppercase;
        }
        .badge-success {
            background-color: #c6f6d5;
            color: #22543d;
        }
        .badge-warning {
            background-color: #feebc8;
            color: #744210;
        }
        .badge-danger {
            background-color: #fed7d7;
            color: #742a2a;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
            .stat-card {
                background: none !important;
                border: 1px solid #cbd5e0 !important;
            }
        }
    </style>
</head>
<body>

    <div class="header">
        <h1>{{ ucfirst($reportType) }} Report</h1>
        <div class="meta">
            Generated on: {{ now()->format('Y-m-d H:i:s') }}
            @if ($reportType !== 'inventory')
                | Period: {{ $startDate ?? 'All Time' }} to {{ $endDate ?? 'All Time' }}
            @endif
        </div>
    </div>

    @if ($reportType === 'sales')
        <div class="stats-grid">
            <div class="stat-card">
                <div class="label">Total Orders</div>
                <div class="value">{{ number_format($data->count()) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Gross Sales</div>
                <div class="value">${{ number_format($data->sum('total'), 2) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Total Discounts</div>
                <div class="value">${{ number_format($data->sum('discount'), 2) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Net Revenue</div>
                <div class="value">${{ number_format($data->sum('total') - $data->sum('discount'), 2) }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Order Number</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th class="text-right">Subtotal</th>
                    <th class="text-right">Discount</th>
                    <th class="text-right">Shipping</th>
                    <th class="text-right">Total</th>
                    <th>Status</th>
                    <th>Payment</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $order)
                    <tr>
                        <td><strong>{{ $order->order_number }}</strong></td>
                        <td>{{ $order->created_at->format('Y-m-d') }}</td>
                        <td>{{ $order->user?->name ?? 'Guest' }}</td>
                        <td class="text-right">${{ number_format($order->subtotal, 2) }}</td>
                        <td class="text-right">${{ number_format($order->discount, 2) }}</td>
                        <td class="text-right">${{ number_format($order->shipping_cost, 2) }}</td>
                        <td class="text-right"><strong>${{ number_format($order->total, 2) }}</strong></td>
                        <td><span class="badge">{{ $order->status->value ?? $order->status }}</span></td>
                        <td><span class="badge badge-success">{{ $order->payment_status->value ?? $order->payment_status }}</span></td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" style="text-align: center;">No orders found for this period.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($reportType === 'inventory')
        @php
            $totalStock = 0;
            $totalValue = 0;
            foreach ($data as $product) {
                $stock = $product->sizes()->exists() ? $product->sizes()->sum('product_size.stock') : ($product->quantity ?? 0);
                $totalStock += $stock;
                $totalValue += ($product->price * $stock);
            }
        @endphp

        <div class="stats-grid three-cols">
            <div class="stat-card">
                <div class="label">Total Products</div>
                <div class="value">{{ number_format($data->count()) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Items in Stock</div>
                <div class="value">{{ number_format($totalStock) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Inventory Valuation</div>
                <div class="value">${{ number_format($totalValue, 2) }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>SKU / Slug</th>
                    <th class="text-right">Price</th>
                    <th class="text-right">Stock</th>
                    <th class="text-right">Valuation</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $product)
                    @php
                        $stock = $product->sizes()->exists() ? $product->sizes()->sum('product_size.stock') : ($product->quantity ?? 0);
                    @endphp
                    <tr>
                        <td><strong>{{ $product->name }}</strong></td>
                        <td>{{ $product->category?->name ?? 'Uncategorized' }}</td>
                        <td>{{ $product->slug }}</td>
                        <td class="text-right">${{ number_format($product->price, 2) }}</td>
                        <td class="text-right">{{ number_format($stock) }}</td>
                        <td class="text-right"><strong>${{ number_format($product->price * $stock, 2) }}</strong></td>
                        <td>
                            <span class="badge {{ $stock > 10 ? 'badge-success' : ($stock > 0 ? 'badge-warning' : 'badge-danger') }}">
                                {{ $stock > 10 ? 'In Stock' : ($stock > 0 ? 'Low Stock' : 'Out of Stock') }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center;">No product data available.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

    @elseif ($reportType === 'promotions')
        <div class="stats-grid two-cols">
            <div class="stat-card">
                <div class="label">Total Discount Campaigns</div>
                <div class="value">{{ number_format($data->count()) }}</div>
            </div>
            <div class="stat-card">
                <div class="label">Active Discount Campaigns</div>
                <div class="value">{{ number_format($data->where('is_active', true)->count()) }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Promo Name</th>
                    <th>Type</th>
                    <th>Value</th>
                    <th>Status</th>
                    <th>Linked Products</th>
                    <th>Starts At</th>
                    <th>Ends At</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($data as $discount)
                    <tr>
                        <td><strong>{{ $discount->name }}</strong></td>
                        <td style="text-transform: capitalize;">{{ $discount->type->value ?? $discount->type }}</td>
                        <td><strong>{{ $discount->value }}{{ ($discount->type->value ?? $discount->type) === 'percentage' ? '%' : '$' }}</strong></td>
                        <td>
                            <span class="badge {{ $discount->is_active ? 'badge-success' : '' }}">
                                {{ $discount->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>{{ $discount->products_count ?? $discount->products()->count() }} products</td>
                        <td>{{ $discount->starts_at ? $discount->starts_at->format('Y-m-d') : 'Immediate' }}</td>
                        <td>{{ $discount->ends_at ? $discount->ends_at->format('Y-m-d') : 'No expiry' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" style="text-align: center;">No promotions campaigns found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            setTimeout(() => {
                window.print();
            }, 500);
        });
    </script>
</body>
</html>
