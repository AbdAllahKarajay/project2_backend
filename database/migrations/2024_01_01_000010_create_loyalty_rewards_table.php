<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_rewards', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('points_required');
            $table->enum('type', ['discount', 'free_service', 'upgrade', 'cashback']);
            $table->decimal('value', 10, 2)->nullable(); // For discount/upgrade values
            $table->string('code')->unique()->nullable(); // For discount codes
            $table->boolean('is_active')->default(true);
            $table->integer('max_redemptions')->nullable(); // Unlimited if null
            $table->integer('current_redemptions')->default(0);
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->timestamps();
            
            // Indexes for better performance
            $table->index(['is_active', 'points_required']);
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_rewards');
    }
};
