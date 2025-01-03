<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->string('nama_produk')->unique();
            $table->unsignedBigInteger('harga_beli');
            $table->enum('penentuan_harga', ['persen', 'nominal'])->default('persen');
            $table->unsignedInteger('persen_keuntungan')->default(0);
            $table->unsignedBigInteger('harga_jual');
            $table->unsignedBigInteger('stok');
            $table->unsignedBigInteger('stok_minimal')->default(0);
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produks');
    }
};
