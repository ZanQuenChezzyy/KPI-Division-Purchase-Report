<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Widgets\ChartWidget;

class VendorTransaction extends ChartWidget
{
    protected static ?string $heading = 'Vendor Transaction Comparison';
    protected static ?string $description = 'Displays the top 10 vendors based on the number of transactions, helping identify the most frequently used vendors.';
    protected static ?int $sort = 5;
    protected static ?string $maxHeight = '185px';

    protected function getData(): array
    {
        // Ambil data vendor dan total transaksi
        $data = PurchaseOrder::join('vendors', 'purchase_orders.vendor_id', '=', 'vendors.id')
            ->selectRaw('vendors.name as vendor_name, COUNT(*) as total_transactions')
            ->groupBy('vendors.name')
            ->orderByDesc('total_transactions')
            ->limit(10)
            ->pluck('total_transactions', 'vendor_name');

        // Array warna untuk chart
        $colors = [
            'rgba(54, 162, 235, 0.5)',  // Blue
            'rgba(255, 99, 112, 0.5)',  // Red
            'rgba(255, 206, 86, 0.5)',  // Yellow
            'rgba(75, 192, 192, 0.5)',  // Teal
            'rgba(153, 102, 255, 0.5)', // Purple
            'rgba(255, 159, 64, 0.5)',  // Orange
            'rgba(199, 199, 199, 0.5)', // Gray
            'rgba(255, 99, 255, 0.5)',  // Pink
            'rgba(99, 255, 132, 0.5)',  // Lime
            'rgba(99, 132, 255, 0.5)',  // Light Blue
        ];

        // Cek jika data kosong
        if ($data->isEmpty()) {
            return [
                'datasets' => [
                    [
                        'label' => 'Number of Transactions',
                        'data' => [],
                        'backgroundColor' => [],
                        'borderWidth' => 0,
                        'borderRadius' => 20,
                    ],
                ],
                'labels' => [],
            ];
        }

        // Atur warna sesuai jumlah data
        $backgroundColors = [];
        foreach (range(0, $data->count() - 1) as $index) {
            $backgroundColors[] = $colors[$index % count($colors)];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Number of Transactions',
                    'data' => $data->values(),
                    'backgroundColor' => $backgroundColors,
                    'borderWidth' => 0,
                    'borderRadius' => 20,
                ],
            ],
            'labels' => $data->keys(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
