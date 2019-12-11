<?php


    namespace blackpanda\tron\objects;


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
        public $token = null;

        /**
         * Transaction constructor.
         * @param $hash
         */
        public function __construct(string $hash = null)
        {
            $this->hash = $hash;

            if(!is_null($this->hash))
            {
                $this->dispatchTransaction($this->hash);
            }
        }

        private function dispatchTransaction($hash){

        }




    }
