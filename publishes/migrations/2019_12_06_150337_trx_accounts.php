<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TrxAccounts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trx_accounts', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->text('address')->unique();
            $table->text('hexAddress')->unique();
            $table->longText('privateKey');
            $table->boolean('active')->default(0);
            $table->timestamp('last_use')->nullable();
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
        Schema::dropIfExists('trx_accounts');
    }
}
