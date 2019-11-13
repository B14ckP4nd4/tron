<?php


    namespace blackpanda\tron\objects;


    use blackpanda\tron\support\Utils;
    use Illuminate\Support\Collection;

    class TronScan
    {
        private $address;

        CONST API_URL = 'https://apilist.tronscan.org/api/';

        CONST TransactionsSort = '-timestamp';
        CONST TransactionsLimit = 999999;
        CONST TransactionsCount = true;
        CONST TransactionsStart = 0;

        CONST TransfersSort = '-timestamp';
        CONST TransfersCount = true;
        CONST TransfersLimit = 999999;
        CONST TransfersToken = null;
        CONST TransfersStart = 0;

        /**
         * TronScan constructor.
         * @param $address
         */
        public function __construct($address)
        {
            $this->address = $address;
        }


        public function accountDetails()
        {
            $request = $this->request('account', [
                'address' => $this->address,
            ]);

            return $request;
        }

        public function transactions(bool $onlyConfirmed = true , string $endpoint = 'transaction')
        {
            $transactions = $this->request($endpoint, [
                'address' => $this->address,
                'sort' => self::TransactionsSort,
                'count' => self::TransactionsCount,
                'start' => self::TransactionsStart,
                'limit' => self::TransactionsLimit,
            ]);

            $contracts = $this->request('contract/events', [
                'address' => $this->address,
                'sort' => self::TransactionsSort,
                'count' => self::TransactionsCount,
                'start' => self::TransactionsStart,
                'limit' => self::TransactionsLimit,
            ]);

            $contracts = $this->arrangeContracts($contracts ,$endpoint);

            $mergeTransfers = $this->mergeTransfers($transactions , $contracts);

            if (!$transactions && !$contracts) return false;


            if (!$onlyConfirmed) return $mergeTransfers;

            $transactionsData = Collection::make($mergeTransfers['data']);
            $transactionsData = $transactionsData->filter(function ($data) {
                return $data['confirmed'];
            });
            return $transactionsData->toArray();
        }

        /**
         * get a Transaction Info
         * @param string $hash
         * @return bool|mixed|string
         */
        public function transactionInfo(string $hash)
        {
            $transaction = $this->request('transaction-info',[
                'hash' => $hash,
            ]);

            return $transaction;
        }

        /**
         * get All Transfers
         * @param bool $onlyConfirmed
         * @return array|bool
         */
        public function transfers(bool $onlyConfirmed = true)
        {
            return $this->transactions($onlyConfirmed , 'transfers');
        }

        /**
         * get All Transactions
         * @return int
         */
        public function totalTransactions() : int
        {
            $transactions = $this->request('transfer', [
                'address' => $this->address,
                'sort' => self::TransactionsSort,
                'count' => self::TransactionsCount,
                'start' => self::TransactionsStart,
                'limit' => self::TransactionsLimit,
            ]);

            $contracts = $this->request('contract/events', [
                'address' => $this->address,
                'sort' => self::TransactionsSort,
                'count' => self::TransactionsCount,
                'start' => self::TransactionsStart,
                'limit' => self::TransactionsLimit,
            ]);

            return $contracts['total'] + $transactions['total'];
        }

        /**
         * return Total of Transfers
         * @return int
         */
        public function totalTransfers() : int
        {
            return $this->totalTransactions();
        }

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

        /**
         * @param mixed $address
         */
        public function setAddress($address): void
        {
            $this->address = $address;
        }

        private function mergeTransfers(array $transfer1 ,array $transfer2){
            if(!isset($transfer1['data']) || !isset($transfer2)) return false;

            $merged = [
                'total' => 0,
                'data' => [],
                'rangeTotal' => 0,
            ];
            $merged['data'] = array_merge($transfer1['data'],$transfer2['data']);
            $merged['total'] = count($merged['data']);
            $merged['rangeTotal'] = $merged['total'];

            return $merged;
        }

        private function arrangeContracts(array $contracts , $format = 'transfer'){
            $data = [];

            foreach ($contracts['data'] as $items)
            {
                if($format == 'transaction')
                {
                    $data[] = $this->transactionInfo($items['transactionHash']);
                    continue;
                }

                $data[] = [
                    'block' => (isset($items['block'])) ? $items['block'] : null,
                    'hash' => (isset($items['transactionHash'])) ? $items['transactionHash'] : null,
                    'timestamp' => (isset($items['timestamp'])) ? $items['timestamp'] : null,
                    'ownerAddress' => (isset($items['transferFromAddress'])) ? $items['transferFromAddress'] : null,
                    'toAddress' => (isset($items['transferToAddress'])) ? $items['transferToAddress'] : null,
                    'confirmed' => (isset($items['confirmed'])) ? $items['confirmed'] : false,
                    'contractData' => [
                        'amount' => (isset($items['amount'])) ? $items['amount'] : false,
                        'decimals' => (isset($items['decimals'])) ? $items['decimals'] : false,
                        'asset_name' => (isset($items['tokenName'])) ? $items['tokenName'] : false,
                        'owner_address' => (isset($items['transferFromAddress'])) ? $items['transferFromAddress'] : null,
                        'to_address' => (isset($items['transferToAddress'])) ? $items['transferToAddress'] : null,
                    ],
                    'cost' => null,
                ];
            }

            $contracts['data'] = $data;

            return $contracts;
        }

    }
