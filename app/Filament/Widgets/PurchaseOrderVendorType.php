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
        $years = collect(range(now()->subYears(4)->year, now()->year));

        $data = PurchaseOrder::query()
            ->join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
            ->join('purchase_requisitions', 'purchase_orders.purchase_requisition_id', '=', 'purchase_requisitions.id')
            ->selectRaw('YEAR(purchase_requisitions.created_at) as year, vendors.type as vendor_type, COUNT(purchase_orders.id) as total_orders')
            ->whereYear('purchase_requisitions.created_at', '>=', now()->subYears(4)->year)
            ->where('vendors.type', '!=', 0)
            ->groupBy('year', 'vendor_type')
            ->orderBy('year')
            ->get()
            ->map(function ($row) {
                $row->vendor_type_label = $this->vendorTypeLabels[$row->vendor_type] ?? 'Unknown';
                return $row;
            });

        $vendorTypes = $data->pluck('vendor_type_label')->unique();

        $datasets = $vendorTypes->map(function ($typeLabel) use ($years, $data) {
            return [
                'label' => $typeLabel,
                'data' => $years->map(fn($year) => $data->where('vendor_type_label', $typeLabel)->where('year', $year)->sum('total_orders'))->toArray(),
                'backgroundColor' => $this->generateColor($typeLabel),
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

    protected array $vendorTypeLabels = [
        0 => 'Foreign',
        1 => 'International',
        2 => 'Domestic',
        3 => 'Contractor',
    ];

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
