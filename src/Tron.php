<?php


    namespace blackpanda\tron;


    use blackpanda\tron\objects\TronScan;
    use blackpanda\tron\support\Utils;
    use IEXBase\TronAPI\Provider\HttpProvider;

    class Tron
    {
        protected $tron;
        protected $address;
        protected $tronScan;

        CONST HTTP_PROVIDER = 'https://api.trongrid.io';

        public function __construct(string $address = null)
        {
            $fullNode = new HttpProvider(self::HTTP_PROVIDER);
            $solidityNode = new HttpProvider(self::HTTP_PROVIDER);
            $eventServer = new HttpProvider(self::HTTP_PROVIDER);

            try {
                $this->tron = new \IEXBase\TronAPI\Tron($fullNode, $solidityNode, $eventServer);
            } catch (\IEXBase\TronAPI\Exception\TronException $e) {
                exit($e->getMessage());
            }

            if(!is_null($address))
            {
                $this->setAddress($address);
                $this->tronScan = new TronScan($address);
            }

        }

        /**
         * return TRX Main Balance
         * @return float
         */
        public function getBalance() : float
        {
            $accountDetails = $this->tronScan->accountDetails();

            if(!$accountDetails && !isset($accountDetails['balance']))
                return false;


            return Utils::fromTron($accountDetails['balance']);
        }


        /**
         * getTokensBalance() return Supported TRC20 Tokens
         * @return array|bool
         */
        public function getTokensBalance()
        {
            $accountDetails = $this->tronScan->accountDetails();

            if(!$accountDetails && !isset($accountDetails['trc20token_balances']))
                return false;

            $tokens = [];

            foreach ($accountDetails['trc20token_balances'] as $token)
            {
                $token['balance'] = ( $token['balance'] >= 1000000 ) ? Utils::fromTron($token['balance']) : $token['balance'];
                $token['type'] =  'TRC20';
                $token['supported'] = Utils::isSupportedToken($token['symbol'] , null , $token['contract_address'] , 'TRC20' );
                $tokens[$token['symbol']] = $token;
            }

            return $tokens;

        }


        /**
         * return Address Transactions
         * @param bool $onlyConfirmed
         * @return array|bool|mixed|string
         */
        public function getTransactions(bool $onlyConfirmed = true)
        {
            return $this->tronScan->transactions($onlyConfirmed);
        }

        /*
         * return Address Transfers
         */
        public function getTransfers(bool $onlyConfirmed = true)
        {
            return $this->tronScan->transactions($onlyConfirmed);
        }


        public function totalTransactions()
        {
            return $this->tronScan->totalTransactions();
        }

        /**
         * @param mixed $address
         * @throws \Exception
         */
        public function setAddress(string $address): void
        {
            $validate = $this->tron->validateAddress($address);
            if(!isset($validate['result']) && $validate['result'] !== true)
                throw new \Exception('invalid Address !');
            $this->address = $address;
        }

        /**
         * @return mixed
         */
        public function getAddress() : string
        {
            return $this->address;
        }



    }
