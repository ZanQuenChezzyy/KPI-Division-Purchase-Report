<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchaseRequisitionStats extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Total PR
        $totalPR = PurchaseRequisition::count();

        // PR Approved
        $successfulPR = PurchaseRequisition::where('status', 1)->count();

        // PR Cancelled
        $cancelledPR = PurchaseRequisition::where('status', 2)->count();

        // Total PR Items
        $totalPRItems = PurchaseRequisitionItem::count();

        // Hitung persentase
        $successRate = $totalPR > 0 ? round(($successfulPR / $totalPR) * 100, 2) : 0;
        $cancelRate = $totalPR > 0 ? round(($cancelledPR / $totalPR) * 100, 2) : 0;

        return [
            Stat::make('Purchase Requisition Successful', "{$successRate}%")
                ->description("{$successfulPR} / {$totalPR} PR Approved")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart($this->getTrendsByStatus(1, 'approved_at')),

            Stat::make('Purchase Requisition Cancelled', "{$cancelRate}%")
                ->description("{$cancelledPR} / {$totalPR} PR Cancelled")
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger')
                ->chart($this->getTrendsByStatus(2, 'cancelled_at')),

            Stat::make('Total Purchase Requisition', "{$totalPR}")
                ->description('Total Purchase Requisitions')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info')
                ->chart($this->getTotalPRTrends()),

            Stat::make('Total Purchase Requisition Items', "{$totalPRItems}")
                ->description('Total Items in all PR')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->chart($this->getTotalPRItemsTrends()),
        ];
    }

    /**
     * Mengembalikan tren PR berdasarkan status dan kolom tanggal yang relevan
     */
    private function getTrendsByStatus($status = null, $dateColumn = 'created_at')
    {
        $dateRange = Carbon::now()->subDays(6)->startOfDay();

        $data = PurchaseRequisition::selectRaw("DATE({$dateColumn}) as tanggal, COUNT(*) as total")
            ->whereNotNull($dateColumn)
            ->where($dateColumn, '>=', $dateRange)
            ->when(!is_null($status), fn($query) => $query->where('status', $status))
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->pluck('total', 'tanggal')
            ->toArray();

        return $this->formatSevenDaysData($data);
    }

    /**
     * Mengembalikan tren untuk Total Purchase Requisition
     */
    private function getTotalPRTrends()
    {
        $dateRange = Carbon::now()->subDays(6)->startOfDay();

        $data = PurchaseRequisition::selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
            ->where('created_at', '>=', $dateRange)
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->pluck('total', 'tanggal')
            ->toArray();

        return $this->formatSevenDaysData($data);
    }

    /**
     * Mengembalikan tren untuk Total Purchase Requisition Items
     */
    private function getTotalPRItemsTrends()
    {
        $dateRange = Carbon::now()->subDays(6)->startOfDay();

        $data = PurchaseRequisitionItem::selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
            ->where('created_at', '>=', $dateRange)
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->pluck('total', 'tanggal')
            ->toArray();

        return $this->formatSevenDaysData($data);
    }

    /**
     * Format data agar tetap mencakup 7 hari terakhir meskipun tidak ada data.
     */
    private function formatSevenDaysData($data)
    {
        $sevenDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $sevenDays[] = $data[$date] ?? 0;
        }

        return $sevenDays;
    }
}
