<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TrxTokens extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trx_tokens', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('symbol');
            $table->unsignedBigInteger('tokenID')->default(0);
            $table->string('type');
            $table->text('url')->nullable();
            $table->text('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('contractAddress')->nullable();
            $table->boolean('supported')->default(false);
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
        Schema::dropIfExists('trx_tokens');
    }
}
