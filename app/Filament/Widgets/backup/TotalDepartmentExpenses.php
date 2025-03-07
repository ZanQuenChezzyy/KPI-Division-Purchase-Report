<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class TotalDepartmentExpenses extends ChartWidget
{
    protected static ?string $heading = 'Total Expenses by Department';
    protected static ?string $description = 'Breaks down the total procurement expenses for each department, enabling budget tracking and analysis.';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = '2';

    protected function getData(): array
    {
        // Ambil total pengadaan per departemen
        $data = Department::with('purchaseRequisitions')
            ->select('departments.name', DB::raw('SUM(purchase_requisition_items.total_price) as total'))
            ->join('purchase_requisitions', 'departments.id', '=', 'purchase_requisitions.department_id')
            ->join('purchase_requisition_items', 'purchase_requisitions.id', '=', 'purchase_requisition_items.purchase_requisition_id')
            ->groupBy('departments.name')
            ->get();

        // Ambil label (nama departemen) dan nilai total pengadaan
        $labels = $data->pluck('name')->toArray();
        $totals = $data->pluck('total')->toArray();

        // Daftar warna RGBA dengan opacity 0.5 untuk 14 departemen
        $colors = [
            'rgba(54, 162, 235, 0.5)',  // Biru
            'rgba(255, 99, 112, 0.5)',  // Merah
            'rgba(255, 206, 86, 0.5)',  // Kuning
            'rgba(75, 192, 192, 0.5)',  // Hijau tosca
            'rgba(153, 102, 255, 0.5)', // Ungu
            'rgba(255, 159, 64, 0.5)',  // Oranye
            'rgba(199, 199, 199, 0.5)', // Abu-abu
            'rgba(255, 99, 255, 0.5)',  // Pink
            'rgba(99, 255, 132, 0.5)',  // Lime
            'rgba(99, 132, 255, 0.5)',  // Biru Muda
            'rgba(132, 99, 255, 0.5)',  // Ungu Muda
            'rgba(255, 132, 99, 0.5)',  // Salmon
            'rgba(192, 255, 99, 0.5)',  // Lemon
            'rgba(99, 255, 255, 0.5)',  // Cyan
        ];

        // Assign warna berdasarkan jumlah data
        $backgroundColors = array_slice($colors, 0, count($labels));

        return [
            'datasets' => [
                [
                    'label' => 'Expenses Total',
                    'data' => $totals,
                    'backgroundColor' => $backgroundColors,
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
}
