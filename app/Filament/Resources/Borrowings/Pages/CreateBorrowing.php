<?php

namespace App\Filament\Resources\Borrowings\Pages;

use App\Filament\Resources\Borrowings\BorrowingResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBorrowing extends CreateRecord
{
    protected static string $resource = BorrowingResource::class;

    protected function afterCreate(): void
    {
        $this->record->book()->decrement('available_stock');
    }

    protected function getRedirectUrl(): string
    {
        return '/admin/borrowings';
    }
}
