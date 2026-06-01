<x-filament-panels::page>
    <x-filament::card>
        <form wire:submit="submit">
            {{ $this->form }}
        </form>
    </x-filament::card>

    @php
        $reportType = $this->data['report_type'] ?? 'sales';
    @endphp

    <div class="mt-6 space-y-6">
        @if ($reportType === 'sales')
            @php
                $salesData = $this->getSalesData();
                $totalOrders = $salesData->count();
                $grossSales = $salesData->sum('total');
                $totalDiscounts = $salesData->sum('discount');
                $totalShipping = $salesData->sum('shipping_cost');
                $netSales = $grossSales - $totalDiscounts;
            @endphp

            <!-- Stats section -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="grid gap-y-1">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Orders</span>
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-1">{{ number_format($totalOrders) }}</span>
                    </div>
                </div>
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="grid gap-y-1">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Gross Sales</span>
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-1">${{ number_format($grossSales, 2) }}</span>
                    </div>
                </div>
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="grid gap-y-1">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Discounts</span>
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-1">${{ number_format($totalDiscounts, 2) }}</span>
                    </div>
                </div>
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="grid gap-y-1">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Net Revenue</span>
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-1">${{ number_format($netSales, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mt-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Report Preview</h3>
                <div class="flex items-center gap-2">
                    <x-filament::button wire:click="exportCsv" color="success" icon="heroicon-m-arrow-down-tray">
                        Export Excel/CSV
                    </x-filament::button>
                    <x-filament::button href="{{ route('admin.reports.print', $this->data) }}" tag="a" target="_blank" color="info" icon="heroicon-m-printer">
                        Print / Save PDF
                    </x-filament::button>
                </div>
            </div>

            <!-- Table Preview -->
            <div class="fi-ta-ctn border border-gray-200 shadow-sm rounded-xl bg-white dark:bg-gray-900 dark:border-white/10 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5 text-sm">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Order Number</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Date</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Customer</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Subtotal</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Discount</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Shipping</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Total</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Payment</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @forelse ($salesData as $order)
                                <tr class="fi-ta-row [@media(hover:hover)]:hover:bg-gray-50/50 dark:[@media(hover:hover)]:hover:bg-white/5">
                                    <td class="fi-ta-cell px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $order->order_number }}</td>
                                    <td class="fi-ta-cell px-6 py-4 text-gray-500 dark:text-gray-400">{{ $order->created_at->format('Y-m-d') }}</td>
                                    <td class="fi-ta-cell px-6 py-4 text-gray-500 dark:text-gray-400">{{ $order->user?->name ?? 'Guest' }}</td>
                                    <td class="fi-ta-cell px-6 py-4 text-right text-gray-500 dark:text-gray-400">${{ number_format($order->subtotal, 2) }}</td>
                                    <td class="fi-ta-cell px-6 py-4 text-right text-gray-500 dark:text-gray-400">${{ number_format($order->discount, 2) }}</td>
                                    <td class="fi-ta-cell px-6 py-4 text-right text-gray-500 dark:text-gray-400">${{ number_format($order->shipping_cost, 2) }}</td>
                                    <td class="fi-ta-cell px-6 py-4 text-right font-medium text-gray-900 dark:text-white">${{ number_format($order->total, 2) }}</td>
                                    <td class="fi-ta-cell px-6 py-4">
                                        <x-filament::badge color="gray">
                                            {{ $order->status->value ?? $order->status }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="fi-ta-cell px-6 py-4">
                                        <x-filament::badge :color="($order->payment_status->value ?? $order->payment_status) === 'paid' ? 'success' : 'danger'">
                                            {{ $order->payment_status->value ?? $order->payment_status }}
                                        </x-filament::badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="fi-ta-cell px-6 py-8 text-center text-gray-500 dark:text-gray-400">No sales data found for the selected date range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @elseif ($reportType === 'inventory')
            @php
                $inventoryData = $this->getInventoryData();
                $totalProducts = $inventoryData->count();
                $totalStock = 0;
                $totalValue = 0;

                foreach ($inventoryData as $product) {
                    $stock = $product->sizes()->exists()
                        ? $product->sizes()->sum('product_size.stock')
                        : ($product->quantity ?? 0);
                    $totalStock += $stock;
                    $totalValue += ($product->price * $stock);
                }
            @endphp

            <!-- Stats section -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="grid gap-y-1">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Products</span>
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-1">{{ number_format($totalProducts) }}</span>
                    </div>
                </div>
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="grid gap-y-1">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Items in Stock</span>
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-1">{{ number_format($totalStock) }}</span>
                    </div>
                </div>
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="grid gap-y-1">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Valuation</span>
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-1">${{ number_format($totalValue, 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mt-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Report Preview</h3>
                <div class="flex items-center gap-2">
                    <x-filament::button wire:click="exportCsv" color="success" icon="heroicon-m-arrow-down-tray">
                        Export Excel/CSV
                    </x-filament::button>
                    <x-filament::button href="{{ route('admin.reports.print', $this->data) }}" tag="a" target="_blank" color="info" icon="heroicon-m-printer">
                        Print / Save PDF
                    </x-filament::button>
                </div>
            </div>

            <!-- Table Preview -->
            <div class="fi-ta-ctn border border-gray-200 shadow-sm rounded-xl bg-white dark:bg-gray-900 dark:border-white/10 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5 text-sm">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product Name</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Category</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">SKU / Slug</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Price</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-right text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Value</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Stock Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @forelse ($inventoryData as $product)
                                @php
                                    $stock = $product->sizes()->exists()
                                        ? $product->sizes()->sum('product_size.stock')
                                        : ($product->quantity ?? 0);
                                @endphp
                                <tr class="fi-ta-row [@media(hover:hover)]:hover:bg-gray-50/50 dark:[@media(hover:hover)]:hover:bg-white/5">
                                    <td class="fi-ta-cell px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $product->name }}</td>
                                    <td class="fi-ta-cell px-6 py-4 text-gray-500 dark:text-gray-400">{{ $product->category?->name ?? 'Uncategorized' }}</td>
                                    <td class="fi-ta-cell px-6 py-4 text-gray-500 dark:text-gray-400">{{ $product->slug }}</td>
                                    <td class="fi-ta-cell px-6 py-4 text-right text-gray-500 dark:text-gray-400">${{ number_format($product->price, 2) }}</td>
                                    <td class="fi-ta-cell px-6 py-4 text-right text-gray-500 dark:text-gray-400">{{ number_format($stock) }}</td>
                                    <td class="fi-ta-cell px-6 py-4 text-right font-medium text-gray-900 dark:text-white">${{ number_format($product->price * $stock, 2) }}</td>
                                    <td class="fi-ta-cell px-6 py-4">
                                        <x-filament::badge :color="$stock > 10 ? 'success' : ($stock > 0 ? 'warning' : 'danger')">
                                            {{ $stock > 10 ? 'In Stock' : ($stock > 0 ? 'Low Stock' : 'Out of Stock') }}
                                        </x-filament::badge>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="fi-ta-cell px-6 py-8 text-center text-gray-500 dark:text-gray-400">No inventory data found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        @elseif ($reportType === 'promotions')
            @php
                $promotionsData = $this->getPromotionsData();
                $totalPromos = $promotionsData->count();
                $activePromos = $promotionsData->where('is_active', true)->count();
            @endphp

            <!-- Stats section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="grid gap-y-1">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Discounts</span>
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-1">{{ number_format($totalPromos) }}</span>
                    </div>
                </div>
                <div class="fi-wi-stats-overview-stat relative rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10">
                    <div class="grid gap-y-1">
                        <span class="text-sm font-medium text-gray-500 dark:text-gray-400">Active Discounts</span>
                        <span class="text-3xl font-semibold tracking-tight text-gray-950 dark:text-white mt-1">{{ number_format($activePromos) }}</span>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between mt-4">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Report Preview</h3>
                <div class="flex items-center gap-2">
                    <x-filament::button wire:click="exportCsv" color="success" icon="heroicon-m-arrow-down-tray">
                        Export Excel/CSV
                    </x-filament::button>
                    <x-filament::button href="{{ route('admin.reports.print', $this->data) }}" tag="a" target="_blank" color="info" icon="heroicon-m-printer">
                        Print / Save PDF
                    </x-filament::button>
                </div>
            </div>

            <!-- Table Preview -->
            <div class="fi-ta-ctn border border-gray-200 shadow-sm rounded-xl bg-white dark:bg-gray-900 dark:border-white/10 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5 text-sm">
                        <thead class="bg-gray-50 dark:bg-white/5">
                            <tr>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Promo Name</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Type</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Value</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Linked Products</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Starts At</th>
                                <th class="fi-ta-header-cell px-6 py-3.5 text-start text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Ends At</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                            @forelse ($promotionsData as $discount)
                                <tr class="fi-ta-row [@media(hover:hover)]:hover:bg-gray-50/50 dark:[@media(hover:hover)]:hover:bg-white/5">
                                    <td class="fi-ta-cell px-6 py-4 font-medium text-gray-900 dark:text-white">{{ $discount->name }}</td>
                                    <td class="fi-ta-cell px-6 py-4 text-gray-500 dark:text-gray-400">
                                        <span class="capitalize">{{ $discount->type->value ?? $discount->type }}</span>
                                    </td>
                                    <td class="fi-ta-cell px-6 py-4 text-gray-950 dark:text-white font-medium">
                                        {{ $discount->value }}{{ ($discount->type->value ?? $discount->type) === 'percentage' ? '%' : '$' }}
                                    </td>
                                    <td class="fi-ta-cell px-6 py-4">
                                        <x-filament::badge :color="$discount->is_active ? 'success' : 'danger'">
                                            {{ $discount->is_active ? 'Active' : 'Inactive' }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="fi-ta-cell px-6 py-4 text-gray-500 dark:text-gray-400">
                                        {{ $discount->products_count ?? $discount->products()->count() }} products
                                    </td>
                                    <td class="fi-ta-cell px-6 py-4 text-gray-500 dark:text-gray-400">
                                        {{ $discount->starts_at ? $discount->starts_at->format('Y-m-d') : 'Immediate' }}
                                    </td>
                                    <td class="fi-ta-cell px-6 py-4 text-gray-500 dark:text-gray-400">
                                        {{ $discount->ends_at ? $discount->ends_at->format('Y-m-d') : 'No expiry' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="fi-ta-cell px-6 py-8 text-center text-gray-500 dark:text-gray-400">No promotions data found for the selected date range.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-filament-panels::page>
