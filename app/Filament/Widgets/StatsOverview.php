<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Services\ExchangeRateService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalIDR = PurchaseOrder::join('purchase_requisition_items', 'purchase_orders.purchase_requisition_id', '=', 'purchase_requisition_items.purchase_requisition_id')
            ->sum('purchase_requisition_items.total_price');

        $rate = ExchangeRateService::getRate('IDR', 'USD');
        $totalUSD = $rate ? $totalIDR * $rate : null;

        return [
            Stat::make('Total Purchase Requisitions', PurchaseRequisition::count() . ' Requisitions')
                ->description(PurchaseRequisition::whereNotNull('approved_at')->count() . ' Purchase Requisitions has been Approved')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->chart($this->getMonthlyData(PurchaseRequisition::class))
                ->color('primary'),

            Stat::make('Total Purchase Orders', PurchaseOrder::count() . ' Order')
                ->description(PurchaseOrder::whereNotNull('confirmed_at')->count() . ' Purchase Order has been confirmed')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->chart($this->getMonthlyData(PurchaseOrder::class))
                ->color('info'),

            Stat::make('Purchase Order Expenses (IDR)', $this->formatRupiahShort(
                PurchaseOrder::join('purchase_requisition_items', 'purchase_orders.purchase_requisition_id', '=', 'purchase_requisition_items.purchase_requisition_id')
                    ->sum('purchase_requisition_items.total_price')
            ))
                ->description('Total Amount = Rp' . number_format(
                    PurchaseOrder::join('purchase_requisition_items', 'purchase_orders.purchase_requisition_id', '=', 'purchase_requisition_items.purchase_requisition_id')
                        ->sum('purchase_requisition_items.total_price'),
                    0,
                    ',',
                    '.'
                ))
                ->descriptionIcon('heroicon-o-banknotes')
                ->chart($this->getMonthlyExpenseData())
                ->color('warning'),

            Stat::make('Purchase Order Expenses (USD)', '$' . number_format($totalUSD, 2, '.', ','))
                ->description(
                    ($rate && $rate != 0
                        ? "Exchange Rate $1 = Rp" . number_format(1 / $rate, 0, ',', '.')
                        : "Exchange Rate: Unavailable"
                    )
                )
                ->descriptionIcon('heroicon-o-banknotes')
                ->chart($this->getMonthlyExpenseData())
                ->color('warning'),
        ];
    }

    private function getMonthlyData($model, $dateColumn = 'created_at')
    {
        $data = $model::selectRaw('MONTH(' . $dateColumn . ') as month, COUNT(id) as total')
            ->groupByRaw('MONTH(' . $dateColumn . ')')
            ->pluck('total', 'month')
            ->toArray();

        return array_replace(array_fill(1, 12, 0), $data);
    }

    private function getMonthlyExpenseData()
    {
        $data = PurchaseOrder::join('purchase_requisition_items', 'purchase_orders.purchase_requisition_id', '=', 'purchase_requisition_items.purchase_requisition_id')
            ->selectRaw('MONTH(purchase_orders.created_at) as month, SUM(purchase_requisition_items.total_price) as total')
            ->groupByRaw('MONTH(purchase_orders.created_at)')
            ->pluck('total', 'month')
            ->toArray();

        return array_replace(array_fill(1, 12, 0), $data);
    }

    private function formatRupiahShort($number)
    {
        if ($number >= 1_000_000_000) {
            return 'Rp' . number_format($number / 1_000_000_000, 2, ',', '.') . ' Miliar';
        } elseif ($number >= 1_000_000) {
            return 'Rp' . number_format($number / 1_000_000, 0, ',', '.') . ' Juta';
        } elseif ($number >= 1_000) {
            return 'Rp' . number_format($number / 1_000, 0, ',', '.') . ' Ribu';
        }

        return 'Rp' . number_format($number, 0, ',', '.');
    }
}
