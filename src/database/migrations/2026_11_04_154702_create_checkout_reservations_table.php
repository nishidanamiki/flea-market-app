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
        Schema::create('checkout_reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->unique()->constrained('items');
            $table->foreignId('user_id')->constrained('users');
            $table->string('status')->default('initializing');
            $table->timestamp('expires_at')->nullable();
            $table->string('stripe_session_id')->nullable()->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_reservations');
    }
};
