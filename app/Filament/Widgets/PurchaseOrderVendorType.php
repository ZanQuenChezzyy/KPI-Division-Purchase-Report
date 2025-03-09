<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use App\Models\Vendor;
use Filament\Widgets\ChartWidget;

class PurchaseOrderVendorType extends ChartWidget
{
    protected static ?string $heading = 'Total Orders by Vendor Type';

    public function getDescription(): ?string
    {
        return 'Displays the total number of purchase orders categorized by vendor type, providing insights into procurement distribution.';
    }

    protected int|string|array $columnSpan = '2';
    protected static ?int $sort = 6;
    protected static ?string $maxHeight = '230px';

    protected function getData(): array
    {
        // Ambil 5 tahun terakhir
        $years = collect(range(now()->subYears(4)->year, now()->year));

        // Ambil semua tipe vendor
        $vendorTypes = Vendor::distinct()->pluck('type');

        // Data hasil query
        $data = PurchaseOrder::query()
            ->join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
            ->selectRaw('YEAR(purchase_orders.created_at) as year, vendors.type as vendor_type, COUNT(purchase_orders.id) as total_orders')
            ->whereYear('purchase_orders.created_at', '>=', now()->subYears(4)->year)
            ->groupBy('year', 'vendor_type')
            ->orderBy('year')
            ->get();

        // Inisialisasi datasets
        $datasets = $vendorTypes->map(function ($type) use ($years, $data) {
            return [
                'label' => $type,
                'data' => $years->map(fn($year) => $data->where('vendor_type', $type)->where('year', $year)->sum('total_orders'))->toArray(),
                'backgroundColor' => $this->generateColor($type),
                'borderWidth' => 0,
                'borderRadius' => 5,
            ];
        });

        return [
            'labels' => $years->toArray(),
            'datasets' => $datasets->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    private function generateColor(string $type): string
    {
        $colors = [
            'rgba(34, 197, 94, 0.5)',   // Green
            'rgba(255, 0, 0, 0.5)',     // Red
            'rgba(59, 130, 246, 0.5)',  // Blue
            'rgba(76, 175, 80, 0.5)',   // Light Green
            'rgba(156, 39, 176, 0.5)',  // Purple
            'rgba(244, 67, 54, 0.5)',   // Light Red
            'rgba(3, 169, 244, 0.5)',   // Cyan Blue
        ];
        return $colors[crc32($type) % count($colors)];
    }
}
