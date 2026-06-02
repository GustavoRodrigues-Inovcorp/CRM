<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /* Tabela pivot entre negócios e produtos */
        Schema::create('deal_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2); /* Preço na altura da venda */
            $table->decimal('total', 10, 2);      /* quantity * unit_price */
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deal_products');
    }
};