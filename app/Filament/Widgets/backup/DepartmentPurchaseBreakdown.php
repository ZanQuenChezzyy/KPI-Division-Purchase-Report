<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Widgets\ChartWidget;

class DepartmentPurchaseBreakdown extends ChartWidget
{
    protected static ?string $heading = 'Department-wise Purchase Breakdown';
    protected static ?string $description = 'Compares the total number of purchases across departments, helping identify which department is the most active.';
    protected static ?int $sort = 6;
    protected static ?string $maxHeight = '185px';

    protected function getData(): array
    {
        $data = PurchaseOrder::join('purchase_requisitions', 'purchase_orders.purchase_requisition_id', '=', 'purchase_requisitions.id')
            ->join('departments', 'purchase_requisitions.department_id', '=', 'departments.id')
            ->selectRaw('departments.name as department_name, COUNT(*) as total_purchases')
            ->groupBy('departments.name')
            ->orderByDesc('total_purchases')
            ->take(3) // Ambil hanya 3 data teratas
            ->pluck('total_purchases', 'department_name');

        $colors = [
            'rgba(54, 162, 235, 0.5)',  // Blue
            'rgba(255, 99, 132, 0.5)',  // Red
            'rgba(255, 206, 86, 0.5)',  // Yellow
            'rgba(75, 192, 192, 0.5)',  // Teal
            'rgba(153, 102, 255, 0.5)', // Purple
            'rgba(255, 159, 64, 0.5)',  // Orange
            'rgba(199, 199, 199, 0.5)', // Gray
            'rgba(255, 99, 255, 0.5)',  // Pink
            'rgba(99, 255, 132, 0.5)',  // Lime
            'rgba(99, 132, 255, 0.5)',  // Light Blue
        ];

        return [
            'datasets' => [
                [
                    'label' => 'Total Purchases',
                    'data' => $data->values(),
                    'backgroundColor' => array_slice($colors, 0, $data->count()),
                    'borderWidth' => 0,
                    'borderRadius' => 4,
                ],
            ],
            'labels' => $data->keys(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'indexAxis' => 'y', // Horizontal bar chart
            'scales' => [
                'x' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}
