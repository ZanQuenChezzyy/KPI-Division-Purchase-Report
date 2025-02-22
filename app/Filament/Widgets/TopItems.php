<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequisitionItem;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TopItems extends ChartWidget
{
    protected static ?string $heading = 'Top 5 Most Purchased Items';
    protected static ?string $description = 'Displays the five most frequently purchased items based on total quantity. Useful for monitoring high-demand items.';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = '2';
    protected static ?string $maxHeight = '185px';

    protected function getData(): array
    {
        // Query untuk mendapatkan top 5 barang dengan total qty tertinggi
        $topItems = PurchaseRequisitionItem::select(
            'items.name as item_name',
            DB::raw('SUM(purchase_requisition_items.qty) as total_qty')
        )
            ->join('items', 'purchase_requisition_items.item_id', '=', 'items.id')
            ->groupBy('items.name')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->get();

        // Format data untuk chart
        $labels = $topItems->pluck('item_name')->toArray();
        $totals = $topItems->pluck('total_qty')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Purchase Amount',
                    'data' => $totals,
                    'backgroundColor' => [
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)',
                        'rgba(153, 102, 255, 0.5)',
                    ],
                    'borderWidth' => 0,
                    'borderRadius' => 4, // Membuat batang chart rounded
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar'; // Bar Chart
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y', // Horizontal Bar Chart
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
