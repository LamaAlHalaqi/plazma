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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('service_id');
            $table->unsignedBigInteger('employee_id');

            // استخدم start_time و end_time بدل reservation_date
            $table->dateTime('start_time');
            $table->dateTime('end_time');

            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->enum('payment_method', ['cash', 'online', 'points']);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->integer('points_used')->default(0);
            $table->integer('points_earned')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            // العلاقات (foreign keys)
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
