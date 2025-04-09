<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequisition;
use Filament\Widgets\ChartWidget;

class PurchaseRequisitionPerYear extends ChartWidget
{
    protected static ?string $heading = 'Purchase Requisition Per Year';
    protected int|string|array $columnSpan = '2';
    protected static ?int $sort = 3;
    public function getDescription(): ?string
    {
        return 'The total number of purchase requisitions submitted each year, tracking approval status and procurement trends.';
    }

    protected function getData(): array
    {
        $currentYear = now()->year;
        $years = range($currentYear - 5, $currentYear); // Pastikan semua 5 tahun terakhir muncul

        // Inisialisasi data awal untuk tiap tahun
        $totalPR = array_fill_keys($years, 0);
        $approvedPR = array_fill_keys($years, 0);
        $cancelledPR = array_fill_keys($years, 0);

        // Ambil data dari database
        $data = PurchaseRequisition::selectRaw('YEAR(created_at) as year, status, COUNT(*) as total')
            ->whereYear('created_at', '>=', min($years))
            ->groupBy('year', 'status')
            ->orderBy('year')
            ->get();

        // Proses data berdasarkan status
        foreach ($data as $item) {
            $totalPR[$item->year] += $item->total;
            if ($item->status == 1) {
                $approvedPR[$item->year] = $item->total;
            } elseif ($item->status == 2) {
                $cancelledPR[$item->year] = $item->total;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Cancelled',
                    'data' => array_values($cancelledPR),
                    'backgroundColor' => 'rgba(255, 0, 0, 0.5)', // Warna merah dengan opacity 0.5
                    'borderWidth' => 0,
                    'borderRadius' => 5,
                ],
                [
                    'label' => 'Approved',
                    'data' => array_values($approvedPR),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)', // Warna biru dengan opacity 0.5
                    'borderWidth' => 0, // Tidak ada border
                    'borderRadius' => 5, // Border radius 5
                ],
                [
                    'label' => 'Total PR',
                    'data' => array_values($totalPR),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)', // Warna kuning dengan opacity 0.5
                    'borderWidth' => 0,
                    'borderRadius' => 5,
                ],
            ],
            'labels' => $years, // Pastikan label mencakup semua tahun
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
