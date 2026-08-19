<?php

namespace App\Filament\Pages;

use App\Filament\Exports\BorrowingExporter;
use App\Models\Borrowing;
use Filament\Actions;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Support\Icons\Heroicon;

class Laporan extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static ?string $navigationLabel = 'Laporan';
    protected static ?string $title = 'Laporan Riwayat Peminjaman';
    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';

    protected string $view = 'filament.pages.laporan';

    protected function getHeaderActions(): array
    {
        return [
            Actions\ExportAction::make()
                ->exporter(BorrowingExporter::class)
                ->label('Download Excel'),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Borrowing::query()->where('status', 'dikembalikan')
            )
            ->defaultSort('return_date', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('book.title')
                    ->label('Buku')
                    ->searchable(),

                Tables\Columns\TextColumn::make('borrower_name')
                    ->label('Peminjam')
                    ->searchable(),

                    Tables\Columns\TextColumn::make('kelas')
                    ->label('Kelas'),

                Tables\Columns\TextColumn::make('borrow_date')
                    ->label('Tgl Pinjam')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('return_date')
                    ->label('Tgl Kembali')
                    ->date('d M Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('fine_amount')
                    ->label('Denda')
                    ->money('IDR')
                    ->sortable(),
            ])
            ->filters([
    Tables\Filters\Filter::make('bulan')
        ->schema([
            Forms\Components\Select::make('bulan')
                ->label('Pilih Bulan')
                ->options([
                    1 => 'Januari', 2 => 'Februari', 3 => 'Maret',
                    4 => 'April', 5 => 'Mei', 6 => 'Juni',
                    7 => 'Juli', 8 => 'Agustus', 9 => 'September',
                    10 => 'Oktober', 11 => 'November', 12 => 'Desember',
                ])
                ->native(false),
            Forms\Components\Select::make('tahun')
                ->label('Tahun')
                ->options(
                    collect(range(now()->year, now()->year - 3))
                        ->mapWithKeys(fn ($year) => [$year => $year])
                )
                ->default(now()->year)
                ->native(false),
        ])
        ->query(function (\Illuminate\Database\Eloquent\Builder $query, array $data) {
            return $query
                ->when($data['bulan'], fn ($q) => $q->whereMonth('return_date', $data['bulan']))
                ->when($data['tahun'], fn ($q) => $q->whereYear('return_date', $data['tahun']));
        })
        ->indicateUsing(function (array $data): ?string {
            if (! $data['bulan']) return null;
            $namaBulan = \Carbon\Carbon::create()->month((int)$data['bulan'])->translatedFormat('F');
            return "Periode: {$namaBulan} " . ($data['tahun'] ?? now()->year);
        }),
]);
    }
}
