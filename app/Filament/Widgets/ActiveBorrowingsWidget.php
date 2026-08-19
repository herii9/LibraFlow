<?php

namespace App\Filament\Widgets;

use App\Models\Borrowing;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;

class ActiveBorrowingsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected static ?string $heading = 'Katalog Peminjaman Aktif';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Borrowing::query()
                    ->where('status', 'dipinjam')
                    ->latest('borrow_date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('book.title')
                    ->label('Buku')
                    ->searchable(),

                Tables\Columns\TextColumn::make('borrower_name')
                    ->label('Peminjam')
                    ->searchable(),

                Tables\Columns\TextColumn::make('borrow_date')
                    ->label('Tgl Pinjam')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('due_date')
                    ->label('Tgl Kembali')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (Borrowing $record) =>
                        $record->due_date->isPast() ? 'Overdue' : 'Active'
                    )
                    ->color(fn (Borrowing $record) =>
                        $record->due_date->isPast() ? 'danger' : 'info'
                    ),
            ])
            ->recordActions([
                Actions\Action::make('kembalikan')
                    ->label('Kembalikan')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->schema([
                        Forms\Components\DatePicker::make('return_date')
                            ->label('Tanggal Dikembalikan')
                            ->default(now())
                            ->required()
                            ->afterOrEqual(fn (Borrowing $record) => $record->borrow_date)
                            ->validationMessages([
                                'after_or_equal' => 'Tanggal kembali tidak boleh lebih awal dari tanggal pinjam.',
                            ]),
                    ])
                    ->action(function (Borrowing $record, array $data) {
                        DB::transaction(function () use ($record, $data) {
                            $record->return_date = $data['return_date'];
                            $record->fine_amount = $record->calculateFine();
                            $record->status = 'dikembalikan';
                            $record->save();

                            $record->book()->increment('available_stock');
                        });

                        Notification::make()
                            ->title('Buku berhasil dikembalikan')
                            ->body('Denda: Rp ' . number_format($record->fine_amount, 0, ',', '.'))
                            ->success()
                            ->send();
                    }),
            ])
            ->paginated([5, 10, 25]);
    }
}
