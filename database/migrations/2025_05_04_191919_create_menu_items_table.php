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
    Schema::create('menu_items', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('restaurant_id'); // Foreign key to restaurants
        $table->unsignedBigInteger('promo_id')->nullable(); // This line moved up and 'after()' removed
        $table->string('name');
        $table->text('description')->nullable();
        $table->decimal('price', 8, 2);
        $table->blob('image')->nullable();
        $table->string('category')->nullable();
        $table->boolean('is_available')->default(true);
        $table->timestamps();
    
        $table->foreign('promo_id')->references('id')->on('promotions')->onDelete('set null');
        $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
    });
    
}



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
