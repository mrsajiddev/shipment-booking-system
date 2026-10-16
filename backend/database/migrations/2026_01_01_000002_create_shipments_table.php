<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('tracking_number', 20)->unique();

            $table->string('sender_name', 100);
            $table->string('sender_city', 60);
            $table->string('receiver_name', 100);
            $table->string('receiver_phone', 20);
            $table->string('receiver_address');
            $table->string('receiver_city', 60);

            $table->decimal('weight_kg', 8, 2);
            $table->decimal('price', 10, 2);           // money: DECIMAL, never FLOAT

            $table->string('status', 20)->default('pending');
            $table->string('courier_reference')->nullable();
            $table->timestamp('booked_at')->nullable();
            $table->timestamps();

            // Matches the real queries:
            //   customer list: WHERE user_id = ? AND status = ? ORDER BY created_at
            //   admin list / dashboard: WHERE status = ? ORDER BY created_at
            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
