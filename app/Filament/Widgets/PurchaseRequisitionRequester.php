<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequisition;
use Filament\Widgets\ChartWidget;

class PurchaseRequisitionRequester extends ChartWidget
{
    protected static ?string $heading = 'Purchase Requisitions by Requester';
    public function getDescription(): ?string
    {
        return 'Displays the total number of purchase requisitions submitted by each requester, providing insights into procurement activity and trends.';
    }
    protected int|string|array $columnSpan = '2';
    protected static ?int $sort = 7;
    protected static ?string $maxHeight = '325px';

    protected function getData(): array
    {
        $data = PurchaseRequisition::query()
            ->join('user_departments', 'purchase_requisitions.requested_by', '=', 'user_departments.id')
            ->join('users', 'user_departments.user_id', '=', 'users.id')
            ->selectRaw('users.name as requester_name, COUNT(purchase_requisitions.id) as total_requests')
            ->groupBy('users.name')
            ->orderByDesc('total_requests')
            ->get();

        return [
            'labels' => $data->pluck('requester_name')->toArray(),
            'datasets' => [
                [
                    'label' => 'Total Requests',
                    'data' => $data->pluck('total_requests')->toArray(),
                    'backgroundColor' => $this->generateColors($data->count()),
                    'borderWidth' => 1,
                ],
            ],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    private function generateColors(int $count): array
    {
        $baseColors = [
            'rgba(34, 197, 94, 0.5)',   // Green
            'rgba(255, 0, 0, 0.5)',     // Red
            'rgba(59, 130, 246, 0.5)',  // Blue
            'rgba(76, 175, 80, 0.5)',   // Light Green
            'rgba(156, 39, 176, 0.5)',  // Purple
            'rgba(244, 67, 54, 0.5)',   // Dark Red
            'rgba(3, 169, 244, 0.5)',   // Sky Blue
            'rgba(255, 193, 7, 0.5)',   // Amber
            'rgba(121, 85, 72, 0.5)',   // Brown
            'rgba(233, 30, 99, 0.5)',   // Pink
            'rgba(63, 81, 181, 0.5)',   // Indigo
            'rgba(0, 188, 212, 0.5)',   // Cyan
            'rgba(205, 220, 57, 0.5)',  // Lime
            'rgba(158, 158, 158, 0.5)', // Gray
            'rgba(255, 87, 34, 0.5)',   // Deep Orange
            'rgba(103, 58, 183, 0.5)',  // Deep Purple
            'rgba(139, 195, 74, 0.5)',  // Light Green
            'rgba(255, 235, 59, 0.5)',  // Yellow
            'rgba(96, 125, 139, 0.5)',  // Blue Gray
            'rgba(255, 152, 0, 0.5)',   // Orange
        ];

        // Jika jumlah warna kurang dari yang dibutuhkan, ulangi warna yang ada
        return array_slice(array_merge($baseColors, $baseColors), 0, $count);
    }
}
