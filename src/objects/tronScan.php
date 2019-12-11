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

        public function account()
        {
            $request = $this->request('account', [
                'address' => $this->address,
            ]);

            return $request;
        }

        // Tokens and Balances

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

        public function TRC10Balance(bool $fromTron = true)
        {
            $tokens = [];
            $balance = $this->account();
            if ($balance && isset($balance['balances'])) {
                foreach ($balance['balances'] as $token) {
                    if ($token['name'] == '_') continue;

                    $findToken = new token();
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

        public function TRC20Balance(bool $fromTron = true)
        {
            $tokens = [];
            $balance = $this->account();
            if ($balance && isset($balance['trc20token_balances'])) {
                foreach ($balance['trc20token_balances'] as $token) {
                    $findToken = new token();
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

        public function tokensBalance()
        {
            $trc10 = $this->TRC10Balance();
            $trc20 = $this->TRC20Balance();
            return array_merge($trc10, $trc20);
        }

        public function getTRC10tokenByID(int $tokenID)
        {
            $token = $this->request("token", [
                'id' => $tokenID,
                'all' => 1,
            ]);

            return $token;
        }

        public function getTRC20TokenByContractAddress(string $contractAddress)
        {
            $token = $this->request('token_trc20', [
                'contract' => $contractAddress,
            ]);

            return $token;
        }


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

        // Transactions

        public function transactions(bool $onlyConfirmed = true, string $sort = '-timestamp', int $limit = 999999999, int $start = 0, string $start_timestamp = null, string
        $end_timestamp = null, bool $count = true)
        {
            $transactions = $this->request('transaction', [
                'address' => $this->address,
                'limit' => $limit,
                'sort' => $sort,
                'count' => $count,
                'start' => $start,
                'start_timestamp' => $start_timestamp,
                'end_timestamp' => '1575229522000',
            ]);

            if (!is_array($transactions) || !isset($transactions['data'])) return false;

            return $transactions;


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
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            // Turn off the server and peer verification (TrustManager Concept).
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, 2000);

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
