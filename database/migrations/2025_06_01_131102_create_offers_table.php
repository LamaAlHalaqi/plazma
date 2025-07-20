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
    {Schema::create('offers', function (Blueprint $table) {
        $table->id();
        $table->string('name'); // اسم العرض
        $table->decimal('discount_percentage', 5, 2); // نسبة الحسم
        $table->dateTime('start_datetime'); // تاريخ ووقت بداية العرض
        $table->dateTime('end_datetime');   // تاريخ ووقت نهاية العرض
        $table->text('description')->nullable(); // وصف العرض
        $table->string('image')->nullable()->after('description');
        $table->integer('points')->default(0); // النقاط المكتسبة عند الحجز
        $table->timestamps();
    });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('offers');
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn('image');
        });



    }
};
