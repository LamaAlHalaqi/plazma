<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOfferServiceTable extends Migration
{
    public function up()
    {

        // تفاصيل العرض
        Schema::create('offer_service', function (Blueprint $table) {
            $table->unsignedBigInteger('offer_id');
            $table->unsignedBigInteger('service_id');

            $table->foreign('offer_id')->references('id')->on('offers')->onDelete('cascade');
            $table->foreign('service_id')->references('id')->on('services')->onDelete('cascade');

            $table->primary(['offer_id', 'service_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('offer_service');
    }
}
