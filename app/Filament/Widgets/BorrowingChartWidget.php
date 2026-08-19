<?php

namespace App\Filament\Widgets;

use App\Models\Borrowing;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class BorrowingChartWidget extends ChartWidget
{
    protected ?string $heading = 'Grafik Peminjaman Mingguan';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = collect(range(6, 0))->map(function ($daysAgo) {
            $date = Carbon::today()->subDays($daysAgo);
            return Borrowing::whereDate('borrow_date', $date)->count();
        });

        $labels = collect(range(6, 0))->map(function ($daysAgo) {
            return Carbon::today()->subDays($daysAgo)->format('d M');
        });

        return [
            'datasets' => [
                [
                    'label' => 'Peminjaman',
                    'data' => $data->toArray(),
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'fill' => true,
                ],
            ],
            'labels' => $labels->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
