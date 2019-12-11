<?php


    namespace blackpanda\tron\objects;


    use App\tron\TrxAccounts;
    use blackpanda\tron\Tron;
    use function GuzzleHttp\Promise\promise_for;

    class Account
    {
        public $id;
        public $address;
        public $hexAddress;
        public $privateKey;
        public $created_at;
        public $updated_at;
        public $active = 0;
        protected $account;
        protected $tron;

        /**
         * accounts constructor.
         * @param $address
         * @param $hexAddress
         * @param $privateKey
         * @param $created_at
         * @param $updated_at
         * @throws \IEXBase\TronAPI\Exception\TronException
         */
        public function __construct(string $address,string $hexAddress = null,string $privateKey = null,$created_at, $updated_at)
        {
            $this->address = $address;
            $this->hexAddress = $hexAddress ?? $this->getAccountHex();
            $this->privateKey = $privateKey;
            $this->created_at = $created_at;
            $this->updated_at = $updated_at;
            $this->dispatchAccount();
            $this->tron = new Tron($this->address);

        }

        public function saveAccount()
        {
            $this->account->address = $this->address;
            $this->account->hexAddress = $this->hexAddress;
            $this->account->privateKey = $this->privateKey;
            $this->account->active = $this->active;
            $this->account->last_use = $this->last_use;

        }

        public function isActive()
        {
            return ($this->active != false) ? true : false;
        }

        private function dispatchAccount(){
            $this->account = TrxAccounts::firstOrNew([
                'address' => $this->address,
            ]);

            if($this->account->id) {
                $this->address = $this->account->address;
                $this->hexAddress = $this->account->hexAddress;
                $this->active = $this->account->active;
                $this->last_use = $this->account->last_use;
            }
        }

        private function getAccountHex(){
            return $this->tron->getHexAddress();
        }




    }
