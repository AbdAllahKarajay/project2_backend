<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('loyalty_reward_id')->constrained()->onDelete('cascade');
            $table->integer('points_spent');
            $table->enum('status', ['pending', 'active', 'used', 'expired'])->default('pending');
            $table->json('metadata')->nullable(); // Additional redemption data
            $table->timestamp('redeemed_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['user_id', 'status']);
            $table->index(['loyalty_reward_id', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_reward_redemptions');
    }
};
