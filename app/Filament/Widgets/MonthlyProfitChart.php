<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use Carbon\Carbon;
use Filament\Support\RawJs;
use Filament\Widgets\ChartWidget;

class MonthlyProfitChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected static bool $isLazy = false;

    protected ?string $heading = 'Grafik Keuntungan Bulanan';

    protected ?string $description = 'Visualisasi laba bersih dan total penjualan per bulan (Bukan harian)';

    protected ?string $maxHeight = '320px';

    protected int | string | array $columnSpan = 'full';

    protected ?string $pollingInterval = '15s';

    public ?string $filter = 'this_year';

    public static function canView(): bool
    {
        return auth()->check();
    }

    protected function getFilters(): ?array
    {
        $currentYear = date('Y');
        return [
            'this_year' => "Tahun Ini ({$currentYear})",
            'last_12_months' => '12 Bulan Terakhir',
            'last_6_months' => '6 Bulan Terakhir',
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public function getData(): array
    {
        $filter = $this->filter ?? 'this_year';
        $now = Carbon::now();

        $periods = [];

        if ($filter === 'last_6_months') {
            for ($i = 5; $i >= 0; $i--) {
                $dt = $now->copy()->subMonths($i);
                $key = $dt->format('Y-m');
                $periods[$key] = [
                    'label' => $dt->translatedFormat('M Y'),
                    'start' => $dt->copy()->startOfMonth(),
                    'end' => $dt->copy()->endOfMonth(),
                ];
            }
        } elseif ($filter === 'last_12_months') {
            for ($i = 11; $i >= 0; $i--) {
                $dt = $now->copy()->subMonths($i);
                $key = $dt->format('Y-m');
                $periods[$key] = [
                    'label' => $dt->translatedFormat('M Y'),
                    'start' => $dt->copy()->startOfMonth(),
                    'end' => $dt->copy()->endOfMonth(),
                ];
            }
        } else {
            // this_year: 12 bulan (Januari - Desember)
            $year = (int) $now->format('Y');
            for ($m = 1; $m <= 12; $m++) {
                $dt = Carbon::create($year, $m, 1);
                $key = $dt->format('Y-m');
                $periods[$key] = [
                    'label' => $dt->translatedFormat('F'),
                    'start' => $dt->copy()->startOfMonth(),
                    'end' => $dt->copy()->endOfMonth(),
                ];
            }
        }

        $startDate = reset($periods)['start'];
        $endDate = end($periods)['end'];

        $monthlyData = [];
        foreach ($periods as $key => $p) {
            $monthlyData[$key] = [
                'label' => $p['label'],
                'revenue' => 0.0,
                'cost' => 0.0,
                'profit' => 0.0,
            ];
        }

        $invoices = Invoice::with(['items.part' => fn ($q) => $q->withTrashed()])
            ->whereNotIn('status', ['VOID'])
            ->whereBetween('invoice_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        foreach ($invoices as $inv) {
            $date = $inv->invoice_date ? Carbon::parse($inv->invoice_date) : Carbon::parse($inv->created_at);
            $key = $date->format('Y-m');

            if (! isset($monthlyData[$key])) {
                continue;
            }

            $revenue = (float) ($inv->subtotal ?? 0) - (float) ($inv->discount_amount ?? 0);
            if ($revenue <= 0 && (float) ($inv->grand_total ?? 0) > 0) {
                $revenue = (float) $inv->grand_total;
            }

            $cost = 0.0;
            foreach ($inv->items as $item) {
                $rawCost = (float) ($item->part?->getRawOriginal('cost_price') ?? $item->part?->cost_price ?? 0);
                $cost += ((float) $item->qty * $rawCost);
            }

            $profit = $revenue - $cost;

            $monthlyData[$key]['revenue'] += $revenue;
            $monthlyData[$key]['cost'] += $cost;
            $monthlyData[$key]['profit'] += $profit;
        }

        $labels = [];
        $profitData = [];
        $revenueData = [];

        foreach ($monthlyData as $row) {
            $labels[] = strtoupper($row['label']);
            $profitData[] = round($row['profit']);
            $revenueData[] = round($row['revenue']);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Keuntungan Bersih (Laba)',
                    'data' => $profitData,
                    'backgroundColor' => '#10b981', // Emerald 500
                    'borderRadius' => 6,
                ],
                [
                    'label' => 'Total Penjualan (Omset)',
                    'data' => $revenueData,
                    'backgroundColor' => '#3b82f6', // Blue 500
                    'borderRadius' => 6,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): RawJs
    {
        return RawJs::make(<<<JS
            {
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                let val = context.parsed.y;
                                if (val !== null) {
                                    label += 'Rp ' + new Intl.NumberFormat('id-ID').format(val);
                                }
                                return label;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                if (value >= 1000000000) {
                                    return 'Rp ' + (value / 1000000000).toFixed(1) + ' M';
                                }
                                if (value >= 1000000) {
                                    return 'Rp ' + (value / 1000000).toFixed(0) + ' jt';
                                }
                                if (value >= 1000) {
                                    return 'Rp ' + (value / 1000).toFixed(0) + ' rb';
                                }
                                return 'Rp ' + value;
                            }
                        }
                    }
                }
            }
        JS);
    }
}
