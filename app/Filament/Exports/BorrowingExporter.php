<?php

namespace App\Filament\Exports;

use App\Models\Borrowing;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class BorrowingExporter extends Exporter
{
    protected static ?string $model = Borrowing::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('book.title')
                ->label('Judul Buku'),
            ExportColumn::make('borrower_name')
                ->label('Nama Peminjam'),
            ExportColumn::make('kelas')
                ->label('Kelas'),
            ExportColumn::make('borrow_date')
                ->label('Tanggal Pinjam'),
            ExportColumn::make('due_date')
                ->label('Jatuh Tempo'),
            ExportColumn::make('return_date')
                ->label('Tanggal Kembali'),
            ExportColumn::make('fine_amount')
                ->label('Denda (Rp)'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Export laporan peminjaman selesai dan berhasil mengekspor ' . Number::format($export->successful_rows) . ' ' . str('baris')->plural($export->successful_rows) . '.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('baris')->plural($failedRowsCount) . ' gagal diekspor.';
        }

        return $body;
    }
}
