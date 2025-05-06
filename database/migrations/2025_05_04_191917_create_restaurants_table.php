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
    Schema::create('restaurants', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('owner_id');
        $table->string('name');
        $table->text('description')->nullable();
        $table->string('address');
        $table->string('phone');
        $table->string('image')->nullable();
        $table->string('cuisine');
        $table->boolean('is_active')->default(true);
        $table->timestamps();

        $table->foreign('owner_id')->references('id')->on('users')->onDelete('cascade');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
