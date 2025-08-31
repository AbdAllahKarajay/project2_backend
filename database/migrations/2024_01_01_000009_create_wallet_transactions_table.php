<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['topup', 'payment', 'refund', 'bonus', 'deduction']);
            $table->decimal('amount', 10, 2);
            $table->decimal('balance_before', 10, 2);
            $table->decimal('balance_after', 10, 2);
            $table->string('reference')->nullable(); // External reference (payment ID, etc.)
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Additional transaction data
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'status']);
            $table->index('reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
