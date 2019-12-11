<?php


    namespace App\tron;


    use Illuminate\Database\Eloquent\Model;

    class TrxAccounts extends Model
    {
        protected $guarded = ['id'];
        protected $table = 'trx_accounts';
    }
