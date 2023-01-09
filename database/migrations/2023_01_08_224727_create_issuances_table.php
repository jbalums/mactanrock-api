<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('issuances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requisition_detail_id');
            $table->foreignId('send_to_id');
            $table->foreignId('location_id');
            $table->string('status')->default('pending');
            $table->foreignId('approved_by_id')->nullable();
            $table->timestamp('date_approved')->nullable();
            $table->foreignId('accepted_by')->nullable();
            $table->timestamp('date_accepted')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('issuances');
    }
};
