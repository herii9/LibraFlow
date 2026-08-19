<?php

namespace App\Filament\Resources\Borrowings;

use App\Filament\Resources\Borrowings\Pages;
use App\Models\Book;
use App\Models\Borrowing;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\DB;

class BorrowingResource extends Resource
{
    protected static ?string $model = Borrowing::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;
    protected static ?string $navigationLabel = 'Peminjaman';
    protected static ?string $modelLabel = 'Peminjaman';
    protected static ?string $pluralModelLabel = 'Peminjaman';
    protected static string|\UnitEnum|null $navigationGroup = 'Transaksi';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\Select::make('book_id')
                ->label('Pilih Buku')
                ->options(fn () => Book::where('available_stock', '>', 0)
                    ->get()
                    ->mapWithKeys(fn ($book) => [
                        $book->id => "{$book->title} (Stok: {$book->available_stock})",
                    ]))
                ->searchable()
                ->required()
                ->disabledOn('edit'),

            Forms\Components\TextInput::make('borrower_name')
                ->label('Nama Peminjam')
                ->required()
                ->maxLength(150),

            Forms\Components\Select::make('kelas')
            ->label('Kelas')
            ->options(Borrowing::kelasOptions())
            ->searchable()
            ->required(),

            Forms\Components\DatePicker::make('borrow_date')
                ->label('Tanggal Pinjam')
                ->default(now())
                ->required()
                ->reactive(),

            Forms\Components\DatePicker::make('due_date')
                ->label('Tanggal Jatuh Tempo')
                ->default(now()->addDays(7))
                ->required()
                ->afterOrEqual('borrow_date')
                ->validationMessages([
                    'after_or_equal' => 'Tanggal jatuh tempo harus sama atau setelah tanggal pinjam.',
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
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
                    ->label('Jatuh Tempo')
                    ->date('d M Y'),

                Tables\Columns\TextColumn::make('return_date')
                    ->label('Tgl Kembali')
                    ->date('d M Y')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('fine_amount')
                    ->label('Denda')
                    ->money('IDR'),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'dipinjam' => 'warning',
                        'dikembalikan' => 'success',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'dipinjam' => 'Dipinjam',
                        'dikembalikan' => 'Dikembalikan',
                    ]),
            ])
            ->recordActions([
                Actions\Action::make('kembalikan')
                ->label('Kembalikan')
                ->color('primary')
                ->modalIcon('heroicon-o-arrow-uturn-left')
                ->modalIconColor('primary')
                ->modalHeading('Proses Pengembalian')
                ->modalDescription('Sistem Penghitung Denda Otomatis. ')
                ->modalSubmitActionLabel('Selesaikan')
                ->modalCancelActionLabel('Batal')
                ->modalWidth('md') // Memperkecil lebar modal agar lebih ramping
                ->visible(fn (Borrowing $record) => $record->status === 'dipinjam')
                ->schema(fn (Borrowing $record) => [

                    // Menggabungkan info peminjaman menjadi satu blok HTML agar rata kiri-kanan (inline)
                    Forms\Components\Placeholder::make('summary')
                        ->hiddenLabel()
                        ->content(function () use ($record) {
                            return new \Illuminate\Support\HtmlString('
                                <div style="display: flex; flex-direction: column; gap: 0.875rem; font-size: 0.875rem; margin-bottom: 0.5rem;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="color: #6b7280; font-weight: 500;">Judul Buku</span>
                                        <span style="font-weight: 700; color: #1f2937;">' . $record->book->title . '</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="color: #6b7280; font-weight: 500;">Peminjam</span>
                                        <span style="font-weight: 500; color: #4b5563;">' . $record->borrower_name . '</span>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span style="color: #6b7280; font-weight: 500;">Tenggat Waktu</span>
                                        <span style="font-weight: 500; color: #4b5563;">' . $record->due_date->translatedFormat('d F Y') . '</span>
                                    </div>
                                </div>
                                <hr style="border-color: #e5e7eb; margin-top: 1rem; margin-bottom: 0.5rem;" />
                            ');
                        }),

                    Forms\Components\DatePicker::make('return_date')
                        ->label('Tanggal Kembali (Hari ini)')
                        ->default(now())
                        ->required()
                        ->live()
                        ->afterOrEqual($record->borrow_date)
                        ->validationMessages([
                            'after_or_equal' => 'Tanggal kembali tidak boleh lebih awal dari tanggal pinjam.',
                        ]),

                    Forms\Components\Placeholder::make('fine_preview')
                        ->hiddenLabel()
                        ->content(function (callable $get) use ($record) {
                            $returnDate = $get('return_date') ? \Carbon\Carbon::parse($get('return_date')) : now();
                            $daysLate = max(0, (int) $record->due_date->diffInDays($returnDate, false));
                            $fine = $daysLate * 1000;

                            if ($daysLate <= 0) {
                                return new \Illuminate\Support\HtmlString('
                                    <div style="border-radius: 0.75rem; background-color: #f0fdf4; border: 1px solid #bbf7d0; padding: 1rem; color: #16a34a; font-size: 0.875rem; display: flex; align-items: center; gap: 0.75rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <span>Tidak ada keterlambatan. Buku dikembalikan tepat waktu.</span>
                                    </div>
                                ');
                            }

                            // Desain kotak peringatan denda sesuai gambar pertama
                            return new \Illuminate\Support\HtmlString('
                                <div style="border-radius: 0.75rem; background-color: #fff1f2; border: 1px solid #ffe4e6; padding: 1rem 1.25rem; display: flex; align-items: center; justify-content: space-between;">
                                    <div style="display: flex; align-items: center; gap: 0.875rem;">
                                        <div style="background-color: #ffe4e6; padding: 0.5rem; border-radius: 9999px; color: #ef4444; display: flex; align-items: center; justify-content: center;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 1.5rem; height: 1.5rem;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                            </svg>
                                        </div>
                                        <div>
                                            <div style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; color: #ef4444;">TERLAMBAT</div>
                                            <div style="font-size: 1.125rem; font-weight: 800; color: #b91c1c;">' . $daysLate . ' Hari</div>
                                        </div>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 0.7rem; font-weight: 700; letter-spacing: 0.05em; color: #ef4444;">TOTAL DENDA</div>
                                        <div style="font-size: 1.25rem; font-weight: 800; color: #ef4444;">Rp ' . number_format($fine, 0, ',', '.') . '</div>
                                        <div style="font-size: 0.65rem; color: #9ca3af; font-weight: 500;">(Rp 1.000 / hari)</div>
                                    </div>
                                </div>
                            ');
                        }),

                    Forms\Components\Placeholder::make('info_note')
                        ->hiddenLabel()
                        ->content(new \Illuminate\Support\HtmlString('
                            <div style="border-radius: 0.5rem; background-color: #f0f9ff; border: 1px solid #bfdbfe; padding: 0.875rem 1rem; display: flex; gap: 0.75rem; align-items: flex-start;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#3b82f6" style="width: 1.25rem; height: 1.25rem; flex-shrink: 0; margin-top: 0.125rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                </svg>
                                <div style="color: #2563eb; font-size: 0.8rem; line-height: 1.4; font-weight: 400;">
                                    Denda dihitung otomatis sejak H+1 dari tanggal tenggat waktu. Pastikan denda sudah dibayarkan sebelum melakukan konfirmasi.
                                </div>
                            </div>
                        ')),
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

                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListBorrowings::route('/'),
            'create' => Pages\CreateBorrowing::route('/create'),
            'edit'   => Pages\EditBorrowing::route('/{record}/edit'),
        ];
    }
}
