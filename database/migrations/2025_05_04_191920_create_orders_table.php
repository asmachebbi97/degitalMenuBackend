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
    Schema::create('orders', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('customer_id');
        $table->unsignedBigInteger('restaurant_id');
        $table->string('status')->default('pending');
        $table->string('payment_method')->default('on Delivery');
        $table->string('payment_status') ; 
        $table->decimal('total_amount', 10, 2);
        $table->timestamps();

        $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('restaurant_id')->references('id')->on('restaurants')->onDelete('cascade');
    });
}

    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
