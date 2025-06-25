<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEmployeeReservationTable extends Migration
{
    public function up()
    {
        Schema::create('employee_reservation', function (Blueprint $table) {
            $table->unsignedBigInteger('reservation_id');
            $table->unsignedBigInteger('employee_id');

            $table->foreign('reservation_id')->references('id')->on('reservations')->onDelete('cascade');
            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');

            $table->primary(['reservation_id', 'employee_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_reservation');
    }
}
