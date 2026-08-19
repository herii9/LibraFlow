<?php

namespace App\Filament\Resources\Borrowings\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class BorrowingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('book_id')
                    ->relationship('book', 'title')
                    ->required(),
                TextInput::make('borrower_name')
                    ->required(),
                DatePicker::make('borrow_date')
                    ->required(),
                DatePicker::make('due_date')
                    ->required(),
                DatePicker::make('return_date'),
                TextInput::make('fine_amount')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                Select::make('status')
                    ->options(['dipinjam' => 'Dipinjam', 'dikembalikan' => 'Dikembalikan'])
                    ->default('dipinjam')
                    ->required(),
            ]);
    }
}
