<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class PurchaseOrderStats extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Total PO
        $totalPO = PurchaseOrder::count();

        // PO yang sudah dikonfirmasi
        $confirmedPO = PurchaseOrder::where('is_confirmed', true)->count();
        $confirmedRate = $totalPO > 0 ? round(($confirmedPO / $totalPO) * 100, 2) : 0;

        // PO yang belum dikonfirmasi
        $notConfirmedPO = PurchaseOrder::where('is_confirmed', false)->count();
        $notConfirmedRate = $totalPO > 0 ? round(($notConfirmedPO / $totalPO) * 100, 2) : 0;

        // Total PO Lines
        $totalPOLines = PurchaseOrderLine::count();

        return [
            Stat::make('Confirmed Purchase Orders', "{$confirmedRate}%")
                ->description("{$confirmedPO} / {$totalPO} PO Confirmed")
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success')
                ->chart($this->getTrendsByStatus(true)),

            Stat::make('Not Confirmed Purchase Orders', "{$notConfirmedRate}%")
                ->description("{$notConfirmedPO} / {$totalPO} PO Not Confirmed")
                ->descriptionIcon('heroicon-o-x-circle')
                ->color('danger')
                ->chart($this->getTrendsByStatus(false)),

            Stat::make('Total Purchase Orders', "{$totalPO}")
                ->description('Total Purchase Orders')
                ->descriptionIcon('heroicon-o-document-text')
                ->color('info')
                ->chart($this->getTotalPOTrends()),

            Stat::make('Total Purchase Order Lines', "{$totalPOLines}")
                ->description('Total Lines in all POs')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('gray')
                ->chart($this->getTotalPOLinesTrends()),
        ];
    }

    private function getTrendsByStatus(bool $status): array
    {
        $dateColumn = $status ? 'confirmed_at' : 'created_at';
        return $this->getTrends($dateColumn, function ($query) use ($status) {
            return $query->where('is_confirmed', $status);
        });
    }

    private function getTotalPOTrends(): array
    {
        return $this->getTrends('created_at');
    }

    private function getTotalPOLinesTrends(): array
    {
        return $this->getTrends('created_at', null, PurchaseOrderLine::class);
    }

    private function getTrends(string $dateColumn, ?callable $filter = null, string $model = PurchaseOrder::class): array
    {
        // Ambil 7 hari terakhir dari hari ini
        $dates = collect(range(6, 0))->map(fn($i) => now()->subDays($i)->toDateString())->toArray();

        // Query data
        $query = $model::selectRaw("DATE({$dateColumn}) as tanggal, COUNT(*) as total")
            ->whereBetween($dateColumn, [now()->subDays(6)->startOfDay(), now()->endOfDay()])
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc');

        if ($filter) {
            $query = $filter($query);
        }

        $data = $query->pluck('total', 'tanggal')->toArray();

        // Pastikan setiap tanggal dalam 7 hari terakhir memiliki nilai
        return collect($dates)->mapWithKeys(fn($date) => [$date => $data[$date] ?? 0])->values()->toArray();
    }
}
