<?php


    namespace blackpanda\tron\objects;


    use blackpanda\tron\support\Utils;

    class tronScan
    {
        public $address;

        CONST API_URL = 'https://apilist.tronscan.org/api/';

        /**
         * tronScan constructor.
         * @param $address
         */
        public function __construct(string $address = null)
        {
            $this->address = $address;
        }

        /**
         * return Account Details
         * @return bool|mixed|string
         */
        public function account()
        {
            $request = $this->request('account', [
                'address' => $this->address,
            ]);

            return $request;
        }

        // Tokens and Balances

        /**
         * return Tron Balance
         * @param bool $fromTron
         * @return bool|float|mixed
         */
        public function balance(bool $fromTron = true)
        {
            $account = $this->account();
            if ($account && isset($account['balances'])) {
                foreach ($account['balances'] as $balance) {
                    if ($balance['name'] == '_') {

                        if ($fromTron) return Utils::fromTron($balance['balance']);

                        return $balance['balance'];
                    }
                }
            }
            return false;
        }


        /**
         * get TRC10 Tokens Balance
         * @param bool $fromTron
         * @return array
         */
        public function TRC10Balance(bool $fromTron = true)
        {
            $tokens = [];
            $balance = $this->account();
            if ($balance && isset($balance['balances'])) {
                foreach ($balance['balances'] as $token) {
                    if ($token['name'] == '_') continue;

                    $findToken = new Token();
                    $find = $findToken->getTokenByTokenId($token['name']);
                    if (!$find) {
                        $findToken->dispatchTRC10Token($token['name'])->store();
                        $find = $findToken->getTokenByTokenId($token['name']);
                    }
                    if (isset($find->id) && $find->id != 0) {
                        $token['name'] = $find->name;
                        $token['tokenID'] = $find->tokenID;
                        $token['type'] = $find->type;
                        $token['symbol'] = $find->symbol;
                        if ($fromTron)
                            $token['balance'] = Utils::fromTron($token['balance']);
                    }
                    $tokens[] = $token;
                }

            }

            return $tokens;
        }


        /**
         * get TRC20 Tokens Balance
         * @param bool $fromTron
         * @return array
         */
        public function TRC20Balance(bool $fromTron = true)
        {
            $tokens = [];
            $balance = $this->account();
            if ($balance && isset($balance['trc20token_balances'])) {
                foreach ($balance['trc20token_balances'] as $token) {
                    $findToken = new Token();
                    $find = $findToken->findToken([
                        'name' => $token['name'],
                        'symbol' => $token['symbol'],
                        'type' => 'trc20',
                        'contractAddress' => $token['contract_address'],
                    ]);
                    if (!$find) {
                        $findToken->dispatchTRC20Token($token['contract_address'])->store();
                        $find = $findToken->findToken([
                            'name' => $token['name'],
                            'symbol' => $token['symbol'],
                            'type' => 'trc20',
                            'contractAddress' => $token['contract_address'],
                        ]);
                    }
                    if (isset($find->id) && $find->id != 0) {
                        $token['name'] = $find->name;
                        $token['tokenID'] = $find->tokenID;
                        $token['type'] = $find->type;
                        $token['symbol'] = $find->symbol;
                        if ($fromTron)
                            $token['balance'] = Utils::fromTron($token['balance']);
                    }
                    $tokens[] = $token;
                }
            }

            return $tokens;
        }


        /**
         * get All Tokens Balances
         * @return array
         */
        public function tokensBalance()
        {
            $trc10 = $this->TRC10Balance();
            $trc20 = $this->TRC20Balance();
            return array_merge($trc10, $trc20);
        }


        /**
         * get TRC10 Token Details Based on Token ID
         * @param int $tokenID
         * @return bool|mixed|string
         */
        public function getTRC10tokenByID(int $tokenID)
        {
            $token = $this->request("token", [
                'id' => $tokenID,
                'all' => 1,
            ]);

            return $token;
        }

        /**
         * get TRC20 Token Details based on Contract Address
         * @param string $contractAddress
         * @return bool|mixed|string
         */
        public function getTRC20TokenByContractAddress(string $contractAddress)
        {
            $token = $this->request('token_trc20', [
                'contract' => $contractAddress,
            ]);

            return $token;
        }


        /**
         * return List of Tokens
         * @param int $limit
         * @param string $filter
         * @param int $start
         * @param string $order
         * @param string $sort
         * @param string $order_current
         * @return array|mixed
         */
        public function tokensList(int $limit = 100, string $filter = 'all', int $start = 0, string $order = 'desc', string $sort = 'volume24hInTrx', string $order_current =
        'descend')
        {
            $tokens = [];
            $request = $this->request('tokens/overview', [
                'start' => $start,
                'limit' => $limit,
                'order' => $order,
                'filter' => $filter,
                'sort' => $sort,
                'order_current' => $order_current
            ]);

            if ($request && isset($request['tokens'])) {
                $tokens = $request['tokens'];
            }

            return $tokens;
        }

        // Transactions and Transfers

        /**
         * Return all Account Transactions
         * @param bool $onlyConfirmed
         * @param string $sort
         * @param int $limit
         * @param int $start
         * @param int|null $start_timestamp
         * @param int|null $end_timestamp
         * @param bool $count
         * @return bool|mixed|string
         */
        public function transactions(bool $onlyConfirmed = true, string $sort = '-timestamp', int $limit = 999999999, int $start = 0, int $start_timestamp = null, int
        $end_timestamp = null, bool $count = true)
        {
            $transactions = $this->request('transaction', [
                'address' => $this->address,
                'limit' => $limit,
                'sort' => $sort,
                'count' => $count,
                'start' => $start,
                'start_timestamp' => (strlen($start_timestamp) == 10)? $start_timestamp * 1000 : $start_timestamp,
                'end_timestamp' => (strlen($end_timestamp) == 10)? $end_timestamp * 1000 : $end_timestamp,
            ]);

            if (!is_array($transactions) || !isset($transactions['data'])) return false;

            return $transactions;
        }


        /**
         * Return Tron Transfers
         * @param string $token
         * @param string $sort
         * @param int $start
         * @param int $limit
         * @param string|null $start_timestamp
         * @param string|null $end_timestamp
         * @param bool $count
         * @return bool|mixed|string
         */
        public function transfers(string $token = '_' , string $sort = '-timestamp' , int $start = 0, int $limit = 50 , string $start_timestamp = null, string
        $end_timestamp = null, bool $count = true)
        {
            $transfers = $this->request('transfer',[
                'address' => $this->address,
                'sort' => $sort,
                'start' => $start,
                'limit' => $limit,
                'start_timestamp' => (strlen($start_timestamp) == 10)? $start_timestamp * 1000 : $start_timestamp,
                'end_timestamp' => (strlen($end_timestamp) == 10)? $end_timestamp * 1000 : $end_timestamp,
                'count' => $count,
            ]);

            if (!is_array($transfers) || !isset($transfers['data'])) return false;

            return $transfers;
        }


        /**
         * return TRC20 Transfers with Specific Contract Address
         * @param string $contract
         * @param int $limit
         * @param int $start
         * @param int|null $start_timestamp
         * @param int|null $end_timestamp
         * @return bool|mixed|string
         */
        public function contractEvents(string $contract , int $limit = 50 , int $start = 0 , int $start_timestamp = null , int $end_timestamp = null )
        {
            $events = $this->request('contract/events',[
                'address' => $this->address,
                'contract' => $contract,
                'limit' => $limit,
                'start' => $start,
                'start_timestamp' => (strlen($start_timestamp) == 10)? $start_timestamp * 1000 : $start_timestamp,
                'end_timestamp' => (strlen($end_timestamp) == 10)? $end_timestamp * 1000 : $end_timestamp,
            ]);

            if (!is_array($events) || !isset($events['data'])) return false;

            return $events;
        }


        /**
         * Handle Request To API
         * @param string|null $endpoint
         * @param array $GET
         * @param array $params
         * @return bool|mixed|string
         */
        private function request(string $endpoint = null, array $GET = [], array $params = [])
        {
            $ch = curl_init();

            // Set URL
            $url = self::API_URL;
            if (!is_null($endpoint)) $url .= $endpoint;

            // GET Params
            if (!empty($GET)) {
                curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($GET));
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }

            // Time OUT
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

            // Turn off the server and peer verification (TrustManager Concept).
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 8000);

            // Params
            if (!empty($params)) {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            }

            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            // Get Response

            $response = curl_exec($ch);

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($http_code !== 200) return false;


            return Utils::jsonDecode($response, true);
        }

        // Getter And Setters

        /**
         * @return string
         */
        public function getAddress(): string
        {
            return $this->address;
        }

        /**
         * @param string $address
         */
        public function setAddress(string $address): void
        {
            $this->address = $address;
        }


    }
