<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PurchaseOrderStats extends BaseWidget
{
    protected function getStats(): array
    {
        // Total PO
        $totalPO = PurchaseOrder::count();

        // PO yang sudah dikonfirmasi
        $confirmedPO = PurchaseOrder::where('is_confirmed', true)->count();
        $confirmedRate = $totalPO > 0 ? round(($confirmedPO / $totalPO) * 100, 2) : 0;

        // PO yang sudah diterima
        $receivedPO = PurchaseOrder::where('is_received', true)->count();
        $receivedRate = $totalPO > 0 ? round(($receivedPO / $totalPO) * 100, 2) : 0;

        // PO yang sudah selesai/ditutup
        $closedPO = PurchaseOrder::where('is_closed', true)->count();
        $closedRate = $totalPO > 0 ? round(($closedPO / $totalPO) * 100, 2) : 0;

        // Total PO Lines
        $totalPOLines = PurchaseOrderLine::count();

        return [
            // PO Confirmed
            Stat::make('Confirmed Purchase Orders', "{$confirmedRate}%")
                ->description("{$confirmedPO} / {$totalPO} PO Confirmed")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart($this->getTrends('is_confirmed')),

            // PO Received
            Stat::make('Received Purchase Orders', "{$receivedRate}%")
                ->description("{$receivedPO} / {$totalPO} PO Received")
                ->descriptionIcon('heroicon-o-truck')
                ->color('info')
                ->chart($this->getTrends('is_received')),

            // PO Closed
            Stat::make('Closed Purchase Orders', "{$closedRate}%")
                ->description("{$closedPO} / {$totalPO} PO Closed")
                ->descriptionIcon('heroicon-o-lock-closed')
                ->color('gray')
                ->chart($this->getTrends('is_closed')),

            // Total PO
            Stat::make('Total Purchase Orders', "{$totalPO}")
                ->description('Total Purchase Orders')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('primary')
                ->chart($this->getTrends()),

            // Total PO Lines
            Stat::make('Total Purchase Order Lines', "{$totalPOLines}")
                ->description('Total Lines in all POs')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('info')
                ->chart($this->getLinesTrends()),
        ];
    }

    private function getTrends($statusColumn = null)
    {
        $dateRange = Carbon::now()->subDays(6)->startOfDay();

        $query = PurchaseOrder::selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
            ->where('created_at', '>=', $dateRange)
            ->groupBy('tanggal')
            ->orderBy('tanggal');

        if (!is_null($statusColumn)) {
            $query->where($statusColumn, true);
        }

        $data = $query->pluck('total', 'tanggal')->toArray();

        // Isi data 7 hari terakhir dengan default 0 jika tidak ada data
        $sevenDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $sevenDays[] = $data[$date] ?? 0;
        }

        return $sevenDays;
    }

    private function getLinesTrends()
    {
        $dateRange = Carbon::now()->subDays(6)->startOfDay();

        $data = PurchaseOrderLine::selectRaw('DATE(created_at) as tanggal, COUNT(*) as total')
            ->where('created_at', '>=', $dateRange)
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->pluck('total', 'tanggal')
            ->toArray();

        // Isi data 7 hari terakhir dengan default 0 jika tidak ada data
        $sevenDays = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->toDateString();
            $sevenDays[] = $data[$date] ?? 0;
        }

        return $sevenDays;
    }
}
