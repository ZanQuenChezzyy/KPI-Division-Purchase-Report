<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use App\Models\PurchaseRequisition;
use App\Services\ExchangeRateService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Hitung Total IDR dari purchase_order_lines
        $totalIDR = PurchaseOrder::join('purchase_order_lines', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->sum('purchase_order_lines.total_price');

        // Konversi ke USD menggunakan ExchangeRateService
        $rate = ExchangeRateService::getRate('IDR', 'USD');
        $totalUSD = $rate ? $totalIDR * $rate : null;

        return [
            // Total Purchase Requisitions
            Stat::make('Total Purchase Requisitions', PurchaseRequisition::count() . ' Requisitions')
                ->description(PurchaseRequisition::whereNotNull('approved_at')->count() . ' Purchase Requisitions has been Approved')
                ->descriptionIcon('heroicon-o-clipboard-document-check')
                ->chart($this->getMonthlyData(PurchaseRequisition::class))
                ->color('primary'),

            // Total Purchase Orders
            Stat::make('Total Purchase Orders', PurchaseOrder::count() . ' Order')
                ->description(PurchaseOrder::whereNotNull('confirmed_at')->count() . ' Purchase Order has been confirmed')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->chart($this->getMonthlyData(PurchaseOrder::class))
                ->color('info'),

            // Purchase Order Expenses (IDR)
            Stat::make('Purchase Order Expenses (IDR)', $this->formatRupiahShort($totalIDR))
                ->description('Total Amount Rp' . number_format($totalIDR, 0, ',', '.'))
                ->descriptionIcon('heroicon-o-banknotes')
                ->chart($this->getMonthlyExpenseData())
                ->color('warning'),

            // Purchase Order Expenses (USD)
            Stat::make('Purchase Order Expenses (USD)', $this->formatUSDShort($totalUSD))
                ->description(
                    ($rate && $rate != 0
                        ? "Total Amount $" . number_format($totalUSD, 2, '.', ',') . " Exchange Rate $1 = Rp" . number_format(1 / $rate, 0, ',', '.')
                        : "Total Amount = $" . number_format($totalUSD, 2, '.', ',') . " Exchange Rate: Unavailable"
                    )
                )
                ->descriptionIcon('heroicon-o-banknotes')
                ->chart($this->getMonthlyExpenseData())
                ->color('warning')
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
        // Mengambil data total_price dari purchase_order_lines
        $data = PurchaseOrder::join('purchase_order_lines', 'purchase_orders.id', '=', 'purchase_order_lines.purchase_order_id')
            ->selectRaw('MONTH(purchase_orders.created_at) as month, SUM(purchase_order_lines.total_price) as total')
            ->groupByRaw('MONTH(purchase_orders.created_at)')
            ->pluck('total', 'month')
            ->toArray();

        return array_replace(array_fill(1, 12, 0), $data);
    }

    private function formatRupiahShort($number)
    {
        if ($number >= 1_000_000_000_000_000_000) {
            return 'Rp' . number_format($number / 1_000_000_000_000_000_000, 2, '.', ',') . ' Quintillion';
        } elseif ($number >= 1_000_000_000_000_000) {
            return 'Rp' . number_format($number / 1_000_000_000_000_000, 2, '.', ',') . ' Quadrillion';
        } elseif ($number >= 1_000_000_000_000) {
            return 'Rp' . number_format($number / 1_000_000_000_000, 2, '.', ',') . ' Trillion';
        } elseif ($number >= 1_000_000_000) {
            return 'Rp' . number_format($number / 1_000_000_000, 2, '.', ',') . ' Billion';
        } elseif ($number >= 1_000_000) {
            return 'Rp' . number_format($number / 1_000_000, 0, '.', ',') . ' Million';
        } elseif ($number >= 1_000) {
            return 'Rp' . number_format($number / 1_000, 0, '.', ',') . ' Thousand';
        }

        return 'Rp' . number_format($number, 0, '.', ',');
    }

    private function formatUSDShort($number)
    {
        if ($number >= 1_000_000_000_000_000_000) {
            return '$' . number_format($number / 1_000_000_000_000_000_000, 2, '.', ',') . ' Quintillion';
        } elseif ($number >= 1_000_000_000_000_000) {
            return '$' . number_format($number / 1_000_000_000_000_000, 2, '.', ',') . ' Quadrillion';
        } elseif ($number >= 1_000_000_000_000) {
            return '$' . number_format($number / 1_000_000_000_000, 2, '.', ',') . ' Trillion';
        } elseif ($number >= 1_000_000_000) {
            return '$' . number_format($number / 1_000_000_000, 2, '.', ',') . ' Billion';
        } elseif ($number >= 1_000_000) {
            return '$' . number_format($number / 1_000_000, 2, '.', ',') . ' Million';
        } elseif ($number >= 1_000) {
            return '$' . number_format($number / 1_000, 2, '.', ',') . ' Thousand';
        }

        return '$' . number_format($number, 2, '.', ',');
    }
}
