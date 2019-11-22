<?php


    namespace App\tron;


    use Illuminate\Database\Eloquent\Model;

    class TrxToken extends Model
    {
        protected $guarded = ['id'];
        protected $table = 'trx_tokens';
    }
