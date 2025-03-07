<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Widgets\ChartWidget;

class PurchaseOrderPerYear extends ChartWidget
{
    protected static ?string $heading = 'Purchase Order Per Year';
    protected int|string|array $columnSpan = '2';
    protected static ?int $sort = 4;
    public function getDescription(): ?string
    {
        return 'The total number of purchase orders created each year, including confirmed and unconfirmed orders.';
    }

    protected function getData(): array
    {
        $currentYear = now()->year;
        $years = range($currentYear - 5, $currentYear); // Menampilkan 5 tahun terakhir

        // Inisialisasi data awal dengan nilai 0 untuk tiap tahun
        $totalPO = array_fill_keys($years, 0);
        $confirmedPO = array_fill_keys($years, 0);
        $notConfirmedPO = array_fill_keys($years, 0);

        // Ambil data dari database
        $data = PurchaseOrder::selectRaw('YEAR(created_at) as year, is_confirmed, COUNT(*) as total')
            ->whereYear('created_at', '>=', min($years))
            ->groupBy('year', 'is_confirmed')
            ->orderBy('year')
            ->get();

        // Proses data berdasarkan status is_confirmed
        foreach ($data as $item) {
            $totalPO[$item->year] += $item->total;
            if ($item->is_confirmed) {
                $confirmedPO[$item->year] = $item->total;
            } else {
                $notConfirmedPO[$item->year] = $item->total;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Confirmed PO',
                    'data' => array_values($confirmedPO),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)', // Biru dengan opacity 0.5
                    'borderWidth' => 0,
                    'borderRadius' => 5,
                ],
                [
                    'label' => 'Not Confirmed PO',
                    'data' => array_values($notConfirmedPO),
                    'backgroundColor' => 'rgba(255, 0, 0, 0.5)', // Merah dengan opacity 0.5
                    'borderWidth' => 0,
                    'borderRadius' => 5,
                ],
                [
                    'label' => 'Total PO',
                    'data' => array_values($totalPO),
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)', // Kuning dengan opacity 0.5
                    'borderWidth' => 0,
                    'borderRadius' => 5,
                ],
            ],
            'labels' => $years, // Pastikan semua tahun muncul
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
