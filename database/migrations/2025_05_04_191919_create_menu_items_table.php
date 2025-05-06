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
        $table->unsignedBigInteger('restaurant_id'); // Foreign key to menus
        $table->string('name');
        $table->text('description')->nullable();
        $table->decimal('price', 8, 2);
        $table->string('image')->nullable();
        $table->string('category')->nullable();
        $table->boolean('is_available')->default(true);
        $table->timestamps();
        
        $table->unsignedBigInteger('promo_id')->nullable()->after('is_available');
        $table->foreign('promo_id')->references('id')->on('promotions')->onDelete('set null');
   
         // Foreign key constraint
         $table->foreign('restaurant_id')
         ->references('id')
         ->on('restaurants')
         ->onDelete('cascade');
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
