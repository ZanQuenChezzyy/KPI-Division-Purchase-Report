<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequisition;
use Filament\Widgets\ChartWidget;

class PurchaseRequisitionBuyer extends ChartWidget
{
    protected static ?string $heading = 'Purchase Requisition by Buyer';
    public function getDescription(): ?string
    {
        return 'Displays the total number of purchase requisitions submitted by each buyer, offering insights into purchasing patterns and procurement trends.';
    }
    protected int|string|array $columnSpan = '2';
    protected static ?int $sort = 9;
    protected static ?string $maxHeight = '325px';

    protected function getData(): array
    {
        // Ambil data buyer dan jumlah PR yang mereka buat
        $buyers = PurchaseRequisition::query()
            ->join('users', 'purchase_requisitions.requested_by', '=', 'users.id')
            ->selectRaw('users.name as buyer_name, COUNT(purchase_requisitions.id) as total_pr')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('total_pr')
            ->get();

        return [
            'labels' => $buyers->pluck('buyer_name')->toArray(), // Nama buyer sebagai label
            'datasets' => [
                [
                    'label' => 'Total PR',
                    'data' => $buyers->pluck('total_pr')->toArray(), // Jumlah PR tiap buyer
                    'backgroundColor' => $this->generateColors($buyers->count()), // Warna untuk setiap buyer
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    // Fungsi untuk menghasilkan warna secara dinamis
    private function generateColors(int $count): array
    {
        $baseColors = [
            'rgba(255, 99, 132, 0.5)',
            'rgba(54, 162, 235, 0.5)',
            'rgba(255, 206, 86, 0.5)',
            'rgba(75, 192, 192, 0.5)',
            'rgba(153, 102, 255, 0.5)',
            'rgba(255, 159, 64, 0.5)',
            'rgba(34, 197, 94, 0.5)',
            'rgba(255, 0, 0, 0.5)',
            'rgba(59, 130, 246, 0.5)',
            'rgba(76, 175, 80, 0.5)',
            'rgba(156, 39, 176, 0.5)',
            'rgba(244, 67, 54, 0.5)',
            'rgba(3, 169, 244, 0.5)',
        ];

        return array_slice(array_merge($baseColors, $baseColors), 0, $count);
    }
}
