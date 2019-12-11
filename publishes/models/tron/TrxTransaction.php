<?php


    namespace App\tron;


    use Illuminate\Database\Eloquent\Model;

    class TrxTransaction extends Model
    {
        protected $guarded = ['id'];
        protected $table = 'trx_transactions';


        public function account()
        {
            return $this->belongsTo('App\tron\TrxAccounts');
        }
    }
