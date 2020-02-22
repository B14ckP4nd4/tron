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
        public function __construct(Address $address)
        {
            $this->address = $address->address;
            $this->hexAddress = $address->hexAddress ?? $this->getAccountHex();
            $this->privateKey = $address->privateKey;
            $this->dispatchAccount();
            $this->tron = new Tron($this->address);

        }

        public function saveAccount()
        {
            return $this->account->save();

        }

        public function isActive()
        {
            return ($this->active != false) ? true : false;
        }

        private function dispatchAccount(){
            $this->account = TrxAccounts::firstOrNew([
                'address' => $this->address,
            ]);

            if(!$this->account->id) {
                $this->account->address = $this->address;
                $this->account->hexAddress = $this->hexAddress;
                $this->account->privateKey = $this->privateKey;
                $this->account->active = $this->active;
                $this->account->last_use = null;
            }

            $this->address = $this->account->address ?? $this->address;
            $this->hexAddress = $this->account->hexAddress ?? $this->hexAddress;
            $this->active = $this->account->active ?? $this->active;
            $this->last_use = $this->account->last_use ?? null;
        }

        private function getAccountHex(){
            if($this->hexAddress) return $this->hexAddress;
            return $this->tron->getHexAddress();
        }




    }
