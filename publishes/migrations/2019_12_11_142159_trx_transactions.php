<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TrxTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trx_transactions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('account')->nullable();
            $table->unsignedBigInteger('block');
            $table->longText('hash');
            $table->timestamp('timestamp');
            $table->longText('ownerAddress');
            $table->longText('toAddress');
            $table->unsignedInteger('contractType');
            $table->boolean('confirmed')->default(false);
            $table->unsignedBigInteger('amount');
            $table->unsignedBigInteger('tokenID')->default(0);
            $table->string('tokenName')->nullable();
            $table->string('symbol')->nullable();
            $table->text('contractAddress')->nullable();
            $table->timestamps();

            $table->foreign('account')->references('id')->on('trx_accounts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trx_transactions');
    }
}
