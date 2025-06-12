<?php use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up(): void
    {
        Schema::create('deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');

            $table->string('address');
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('phone')->nullable();

            // Complementary information 
            $table->text('instructions')->nullable();      // Extra notes for delivery
 
            $table->enum('status', ['pending', 'in_transit', 'delivered', 'failed'])->default('pending');
            $table->timestamp('delivered_at')->nullable();

            $table->timestamps(); // Includes created_at and updated_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery');
    }
 
};