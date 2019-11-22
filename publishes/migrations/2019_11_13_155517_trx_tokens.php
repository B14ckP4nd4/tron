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
            $table->string('abbr');
            $table->string('name');
            $table->unsignedBigInteger('pairId')->nullable();
            $table->string('contractAddress')->nullable();
            $table->integer('decimal');
            $table->text('description')->nullable();
            $table->boolean('isTop')->default(false);
            $table->string('projectSite')->nullable();
            $table->unsignedBigInteger('supply')->nullable();
            $table->string('tokenType');
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
