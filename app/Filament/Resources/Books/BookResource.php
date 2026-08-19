<?php

namespace App\Filament\Resources\Books;

use App\Models\Book;
use App\Filament\Resources\Books\Pages;
use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Filament\Actions;

class BookResource extends Resource
{
    protected static ?string $model = Book::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;
    protected static ?string $navigationLabel = 'Buku';
    protected static ?string $modelLabel = 'Buku';
    protected static ?string $pluralModelLabel = 'Buku';

    protected static string|\UnitEnum|null $navigationGroup = 'Data Master';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Forms\Components\TextInput::make('title')
                ->label('Judul Buku')
                ->required()
                ->maxLength(255),

            Forms\Components\TextInput::make('author')
                ->label('Pengarang')
                ->required()
                ->maxLength(150),

            Forms\Components\Select::make('category')
                ->label('Kategori')
                ->options([
                    'Fiction'    => 'Fiction',
                    'History'    => 'History',
                    'Self-Help'  => 'Self-Help',
                    'Science'    => 'Science',
                    'Biography'  => 'Biography',
                    'Textbook'   => 'Textbook',
                ])
                ->searchable()
                ->native(false),

            Forms\Components\TextInput::make('publisher')
                ->label('Penerbit')
                ->maxLength(150),

            Forms\Components\TextInput::make('isbn')
                ->label('ISBN')
                ->maxLength(30),

            Forms\Components\TextInput::make('total_stock')
                ->label('Total Stok')
                ->numeric()
                ->required()
                ->minValue(0)
                ->default(0)
                ->live()
                ->afterStateUpdated(function ($state, callable $set, callable $get, string $context, ?Book $record) {
                    if ($context === 'create') {
                        $set('available_stock', $state);
                    }

                    if ($context === 'edit' && $record) {
                        $oldTotal = $record->total_stock;
                        $oldAvailable = $record->available_stock;
                        $diff = $state - $oldTotal;
                        $set('available_stock', max(0, $oldAvailable + $diff));
                    }
                }),

            Forms\Components\TextInput::make('available_stock')
                ->label('Stok Tersedia')
                ->numeric()
                ->required()
                ->minValue(0)
                ->default(0)
                ->disabledOn('edit')
                ->dehydrated()
                ->helperText('Otomatis berubah saat ada transaksi pinjam/kembali'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->description(fn (Book $record) => $record->author),

                Tables\Columns\TextColumn::make('category')
                    ->label('Kategori')
                    ->badge()
                    ->color('primary'),

                Tables\Columns\TextColumn::make('isbn')
                    ->label('ISBN')
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('available_stock')
                    ->label('Stok')
                    ->formatStateUsing(fn (Book $record) => "{$record->available_stock} / {$record->total_stock}")
                    ->badge()
                    ->color(fn (Book $record) => $record->available_stock === 0 ? 'danger' : 'success'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category')
                    ->label('Kategori')
                    ->options([
                        'Fiction'    => 'Fiction',
                        'History'    => 'History',
                        'Self-Help'  => 'Self-Help',
                        'Science'    => 'Science',
                        'Biography'  => 'Biography',
                        'Textbook'   => 'Textbook',
                    ]),
            ])
            ->recordActions([
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
            'index'  => Pages\ListBooks::route('/'),
            'create' => Pages\CreateBook::route('/create'),
            'edit'   => Pages\EditBook::route('/{record}/edit'),
        ];
    }
}
