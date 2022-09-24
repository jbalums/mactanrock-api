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
        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('quantity')->default(0);
            $table->foreignId('branch_id');
            $table->foreignId('transacted_by_id');
            $table->foreignId('accepted_by_id');
            $table->string('movement');
            $table->foreignId('to_branch_id')->nullable();
            $table->foreignId('to_client_id')->nullable();
            $table->foreignId('to_assembly_id')->nullable();
            $table->foreignId('from_branch_id')->nullable();
            $table->foreignId('from_supplier_id')->nullable();
            $table->foreignId('from_request_id')->nullable();
            $table->foreignId('receive_id')->nullable();
            $table->text('details');
            $table->string('action')->default('auto');
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
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
        Schema::dropIfExists('inventory_transactions');
    }
};
