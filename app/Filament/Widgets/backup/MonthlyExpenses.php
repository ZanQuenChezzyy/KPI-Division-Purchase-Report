<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrderLine;
use App\Models\PurchaseRequisition;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyExpenses extends ChartWidget
{
    protected static ?string $heading = 'Monthly Expense Trend';
    protected static ?string $description = 'Shows the monthly trend of procurement expenses over the past year, helping to analyze spending patterns.';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = '2';

    protected function getData(): array
    {
        // Query total_price dari purchase_order_lines berdasarkan bulan confirmed_at
        $data = PurchaseOrderLine::select(
            DB::raw("DATE_FORMAT(purchase_orders.confirmed_at, '%Y-%m') as month"),
            DB::raw('SUM(purchase_order_lines.total_price) as total')
        )
            ->join('purchase_orders', 'purchase_order_lines.purchase_order_id', '=', 'purchase_orders.id')
            ->whereNotNull('purchase_orders.confirmed_at')
            ->whereBetween('purchase_orders.confirmed_at', [
                Carbon::now()->subYear()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Format label bulan dan total pengadaan
        $months = [];
        $totals = [];

        $period = Carbon::now()->subYear()->addMonth()->startOfMonth();
        for ($i = 0; $i < 12; $i++) {
            $monthLabel = $period->format('F Y'); // Contoh: January 2024
            $months[] = $monthLabel;

            // Ambil total bulanan atau 0 jika tidak ada data
            $monthlyTotal = $data->firstWhere('month', $period->format('Y-m'))?->total ?? 0;
            $totals[] = $monthlyTotal;

            $period->addMonth();
        }

        return [
            'datasets' => [
                [
                    'label' => 'Total Expenses',
                    'data' => $totals,
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $months,
        ];
    }

    protected function getType(): string
    {
        return 'line'; // Line Chart
    }
}
