<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PurchaseRequisitionStats extends BaseWidget
{
    protected function getStats(): array
    {
        // Total PR
        $totalPR = PurchaseRequisition::count();

        // PR Sukses (status = 2 -> Approved)
        $successfulPR = PurchaseRequisition::where('status', 2)->count();

        // PR Gagal (status = 1 -> Cancelled)
        $cancelledPR = PurchaseRequisition::where('status', 1)->count();

        // Total PR Items
        $totalPRItems = PurchaseRequisitionItem::count();

        // Hitung persentase
        $successRate = $totalPR > 0 ? round(($successfulPR / $totalPR) * 100, 2) : 0;
        $cancelRate = $totalPR > 0 ? round(($cancelledPR / $totalPR) * 100, 2) : 0;

        return [
            // PR Successful
            Stat::make('Purchase Requisition Successful', "{$successRate}%")
                ->description("{$successfulPR} / {$totalPR} PR Approved")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart($this->getTrends(2)), // Ambil data hanya untuk PR Approved

            // PR Cancelled
            Stat::make('Purchase Requisition Cancelled', "{$cancelRate}%")
                ->description("{$cancelledPR} / {$totalPR} PR Cancelled")
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger')
                ->chart($this->getTrends(1)), // Ambil data hanya untuk PR Cancelled

            // Total PR
            Stat::make('Total Purchase Requisition', "{$totalPR}")
                ->description('Total Purchase Requisitions')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary')
                ->chart($this->getTrends()), // Ambil semua PR tanpa filter status

            // Total PR Items
            Stat::make('Total Purchase Requisition Items', "{$totalPRItems}")
                ->description('Total Items in all PRs')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('info')
                ->chart($this->getItemsTrends()), // Ambil data untuk total PR Items
        ];
    }

    private function getTrends($status = null)
    {
        $dateRange = Carbon::now()->subDays(6)->startOfDay();

        $query = PurchaseRequisition::selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
            ->where('created_at', '>=', $dateRange)
            ->groupBy('tanggal')
            ->orderBy('tanggal');

        if (!is_null($status)) {
            $query->where('status', $status);
        }

        $data = $query->pluck('total', 'tanggal')->toArray();

        // Buat array 7 hari terakhir dengan nilai default 0
        $sevenDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $sevenDays[] = $data[$date] ?? 0;
        }

        return $sevenDays;
    }

    private function getItemsTrends()
    {
        $dateRange = Carbon::now()->subDays(6)->startOfDay();

        $data = PurchaseRequisitionItem::selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
            ->where('created_at', '>=', $dateRange)
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->pluck('total', 'tanggal')
            ->toArray();

        // Isi 7 hari terakhir dengan nilai default 0
        $sevenDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $sevenDays[] = $data[$date] ?? 0;
        }

        return $sevenDays;
    }
}
