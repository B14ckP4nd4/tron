<?php


    namespace blackpanda\tron\objects;


    use App\tron\TrxAccounts;
    use App\tron\TrxToken;
    use App\tron\TrxTransaction;
    use blackpanda\tron\support\Utils;

    class Transaction
    {
        public $id;
        public $account;
        public $block;
        public $hash;
        public $timestamp;
        public $ownerAddress;
        public $toAddress;
        public $contractType;
        public $confirmed;
        public $amount;
        public $tokenID;
        public $tokenName;
        public $symbol;
        public $contractAddress;

        public $isToken = false;
        public $isSupported;
        public $token = null;
        private $transaction;

        /**
         * Transaction constructor.
         * @param $hash
         */
        public function __construct(string $hash)
        {
            $this->hash = $hash;
            $this->transaction = $this->getTransaction();

            foreach ($this->transaction as $k => $v)
            {
                $this->{$k} = $v;
            }

            $this->isToken = ($this->contractType != 1) ? true : false;
        }

        private function getTransaction(){
            return TrxTransaction::where('hash',$this->hash)->firstOrFail();
        }

        /**
         * @return mixed
         */
        public function getAmount(bool $fromTron)
        {
            if($fromTron) return Utils::fromTron($this->amount);

            return $this->amount;
        }






    }
