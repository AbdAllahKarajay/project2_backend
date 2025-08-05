<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('loyalty_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained();
            $table->integer('points');
            $table->foreignId('source_request_id')->constrained('service_requests');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('loyalty_points');
    }
};