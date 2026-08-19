<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Borrowing extends Model
{
    protected $fillable = [
        'book_id', 'borrower_name', 'kelas', 'borrow_date',
        'due_date', 'return_date', 'fine_amount', 'status',
    ];

    protected $casts = [
        'borrow_date' => 'date',
        'due_date'    => 'date',
        'return_date' => 'date',
        'fine_amount' => 'decimal:2',
    ];

    public static function kelasOptions(): array
    {
        return [
            'X1' => 'X1', 'X2' => 'X2', 'X3' => 'X3',
            'X4' => 'X4', 'X5' => 'X5', 'X6' => 'X6',
            'XI A1' => 'XI A1', 'XI A2' => 'XI A2', 'XI A3' => 'XI A3',
            'XI B1' => 'XI B1', 'XI B2' => 'XI B2',
            'XI C1' => 'XI C1', 'XI C2' => 'XI C2',
            'XII A1' => 'XII A1', 'XII A2' => 'XII A2',
            'XII B1' => 'XII B1', 'XII B2' => 'XII B2',
            'XII C1' => 'XII C1', 'XII C2' => 'XII C2',
        ];
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    public function calculateFine(int $ratePerDay = 1000): int
    {
        $returnDate = $this->return_date ?? now();
        $daysLate = (int) $this->due_date->diffInDays($returnDate, false);

        return $daysLate > 0 ? $daysLate * $ratePerDay : 0;
    }

    protected static function booted(): void
    {
        static::deleting(function (Borrowing $borrowing) {
            // Kalau transaksi yang dihapus masih berstatus "dipinjam",
            // kembalikan stok buku sebelum record-nya benar-benar terhapus.
            if ($borrowing->status === 'dipinjam') {
                $borrowing->book()->increment('available_stock');
            }
        });
    }
}
