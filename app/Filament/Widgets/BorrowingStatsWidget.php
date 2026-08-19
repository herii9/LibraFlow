<?php

namespace App\Filament\Widgets;

use App\Models\Book;
use App\Models\Borrowing;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BorrowingStatsWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalBooks = Book::sum('total_stock');
        $activeBorrowings = Borrowing::where('status', 'dipinjam')->count();
        $overdueCount = Borrowing::where('status', 'dipinjam')
            ->where('due_date', '<', now())
            ->count();
        $returnedToday = Borrowing::where('status', 'dikembalikan')
            ->whereDate('return_date', today())
            ->count();

        return [
                Stat::make('Total Buku', $totalBooks)
                    ->description('Total eksemplar')
                    ->descriptionIcon('heroicon-m-book-open')
                    ->color('info'),

                Stat::make('Sedang Dipinjam', $activeBorrowings)
                    ->description('Transaksi aktif')
                    ->descriptionIcon('heroicon-m-arrow-path')
                    ->color('warning'),

                Stat::make('Terlambat (Overdue)', $overdueCount)
                    ->description('Lewat jatuh tempo')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color($overdueCount > 0 ? 'danger' : 'success'),

                Stat::make('Dikembalikan Hari Ini', $returnedToday)
                    ->description('Kembali hari ini')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),
        ];
    }
}
