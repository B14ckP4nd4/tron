<?php


    namespace blackpanda\tron;


    use blackpanda\tron\objects\TronScan;
    use blackpanda\tron\objects\walletGenerator;
    use IEXBase\TronAPI\Provider\HttpProvider;

    class Tron
    {
        private $address;
        private $privateKey;
        private $hexAddress;


        private $apiProvider;
        private $tronScan;
        private $api;
        private $walletGenerator;

        /**
         * Tron constructor.
         * @param $address
         * @param $privateKey
         * @param $hexAddress
         * @param $apiProvider
         */
        public function __construct(string $address = null,string $privateKey = null,string $hexAddress = null,string $apiProvider = null)
        {
            $this->address = $address;
            $this->privateKey = $privateKey;
            $this->hexAddress = $hexAddress;
            $this->apiProvider = $apiProvider ?? config('tron.serviceProvider');



            $this->api = $this->setApi();

            if($address != null){
                $this->api->setAddress($this->address);
                $this->tronScan = new TronScan($this->address);
            }

            $this->walletGenerator = new walletGenerator();

            return $this;

        }


        public function newAccount()
        {
            return $this->api->createAccount();
        }

        public function generateAddress()
        {
            return $this->walletGenerator->generateWallet();
        }

        public function validateAddress()
        {
            $validate = $this->api->validateAddress($this->address);
            if(!isset($validate['result']) && $validate['result'] !== true)
                return false;

            return true;
        }

        public function generateEncryptedWallet()
        {
            $wallet = $this->walletGenerator->generateWallet();

            if($wallet)
            {
                $encrypt = SSLEncryption::publicEncrypt($wallet->privateKey);
                $wallet->privateKey = $encrypt;

                return $wallet;
            }

            return false;
        }




        private function setApi(){
            $fullNode = new HttpProvider($this->apiProvider);
            $solidityNode = new HttpProvider($this->apiProvider);
            $eventServer = new HttpProvider($this->apiProvider);

            return new \IEXBase\TronAPI\Tron($fullNode,$solidityNode,$eventServer);
        }




    }
