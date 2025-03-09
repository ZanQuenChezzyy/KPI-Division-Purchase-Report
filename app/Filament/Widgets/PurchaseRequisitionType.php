<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequisition;
use App\Models\PurchaseType;
use Filament\Widgets\ChartWidget;

class PurchaseRequisitionType extends ChartWidget
{
    protected static ?string $heading = 'Purchase Requisition by Type';
    protected int|string|array $columnSpan = '2';
    protected static ?int $sort = 5;
    protected static ?string $maxHeight = '230px';
    public function getDescription(): ?string
    {
        return 'The total number of purchase requisitions submitted each year, tracking approval status and procurement trends.';
    }

    protected function getData(): array
    {
        // Ambil jumlah purchase requisition berdasarkan tipe
        $data = PurchaseRequisition::selectRaw('purchase_type_id, COUNT(*) as total')
            ->groupBy('purchase_type_id')
            ->pluck('total', 'purchase_type_id')
            ->toArray();

        // Ambil nama tipe dari tabel PurchaseType
        $labels = PurchaseType::whereIn('id', array_keys($data))
            ->pluck('name', 'id')
            ->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Total Purchase Requisition',
                    'data' => array_values($data), // Ambil nilai total untuk setiap tipe
                    'backgroundColor' => [
                        'rgba(34, 197, 94, 0.5)',
                        'rgba(255, 0, 0, 0.5)',
                        'rgba(59, 130, 246, 0.5)',
                        '#4BC0C0',
                        '#9966FF',
                        '#FF9F40',
                    ],
                    'borderWidth' => 2,
                    'borderRadius' => 5,
                ],
            ],
            'labels' => array_values($labels), // Ambil nama tipe untuk label pie chart
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
