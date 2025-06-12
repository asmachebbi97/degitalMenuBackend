<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cart_id');
            $table->unsignedBigInteger('menu_item_id');
            $table->integer('quantity')->default(1);
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();

            $table->foreign('cart_id')->references('id')->on('cart')->onDelete('cascade');
            $table->foreign('menu_item_id')->references('id')->on('menu_items')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
