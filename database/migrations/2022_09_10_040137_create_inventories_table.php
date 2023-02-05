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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quantity');
            $table->double('price', 10, 2)->default(0);
            $table->bigInteger('batch')->default(1);
            $table->date('expired_at')->nullable();
            $table->text('description')->nullable();
            $table->string('action')->default('auto');
            $table->boolean('sellable')->default(true);
            $table->foreignId('from_branch_id')->nullable();
            $table->foreignId('from_supplier_id')->nullable();
            $table->foreignId('from_request_id')->nullable();
            $table->foreignId('receive_id')->nullable();
            $table->foreignId('product_id')->constrained();
            $table->foreignId('inventory_location_id');
            $table->string('account_code')->nullable();
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
        Schema::dropIfExists('inventories');
    }
};
