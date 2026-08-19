<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('author');
            $table->string('category')->nullable();   // Fiction, History, Self-Help, dst
            $table->string('publisher')->nullable();
            $table->string('isbn')->nullable();
            $table->unsignedInteger('total_stock')->default(0);     // total eksemplar dimiliki
            $table->unsignedInteger('available_stock')->default(0); // sisa yang bisa dipinjam
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
