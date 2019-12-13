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
        public function __construct(string $hash = null)
        {
            $this->hash = $hash;
            $this->token = new Token();

            if (!is_null($this->hash)) {
                $this->transaction = $this->dispatchTransaction($this->hash);
            }
        }

        private function dispatchTransaction($hash)
        {
            return TrxTransaction::where('hash', $hash)->firstOrFail();
        }


        public function isToken(): bool
        {
            return (in_array($this->transaction->contractType,[2,31])) ? true : false;
        }

        public function isTRC20(): bool
        {
            return ($this->isToken() && !is_null($this->transaction->contractAddress)) ? true : false;
        }

        public function isTRC10(): bool
        {
            return ($this->isToken() && $this->transaction->tokenID > 0) ? true : false;
        }

        public function isConfirmed(): bool
        {
            return $this->transaction->confirmed;
        }

        public function getToken()
        {
            if (!$this->isToken()) return false;

            $token = [];
            $token['type'] = ($this->isTRC10()) ? 'TRC10' : 'TRC20';
            if ($token['type'] == 'TRC10') {
                $token['tokenID'] = $this->transaction->tokenID;
            } else {
                $token['contractAddress'] = $this->transaction->contractAddress;
            }
        }

        public function getAmount(bool $fromTron = true)
        {
            return ($fromTron) ? Utils::fromTron($this->transaction->amount) : $this->transaction->amount;
        }

        public function __get(string $name)
        {
            $this->transaction->{$name};
        }

        public function parseData($data)
        {
            if (empty($data) || !is_array($data)) return false;

            $this->block = $data['block'];
            $this->hash = $data['hash'];
            $this->timestamp = $data['timestamp'];
            $this->ownerAddress = $data['ownerAddress'];
            $this->contractType = $data['contractType'];
            $this->confirmed = $data['confirmed'];

            $checkUntrustedTransactions = true;

            switch ($this->contractType):
                case 1:
                    $this->parseNormalTransaction($data);
                    $this->tokenID = 0;
                    $this->contractAddress = null;
                    $this->tokenName = null;
                    $this->symbol = 'TRX';
                    $this->isToken = false;
                    $this->toAddress = $data['toAddress'];
                    break;
                case 2:
                    $this->parseTRC10Transaction($data);
                    $this->isToken = true;
                    $this->toAddress = $data['toAddress'];
                    break;
                case 31:
                    $check = $this->parseSmartContractTransaction($data); // TRC20 Transaction
                break;
            endswitch;

            if(!$checkUntrustedTransactions) {
                $this->isSupported = false;
            }
            return true;
        }

        public function isInternal($data)
        {
            return false;
            return empty($data['internal_transactions']);
        }

        private function parseNormalTransaction(array $data){

            $this->amount = $data['contractData']['amount'];
            $this->toAddress = $data['contractData']['to_address'];
            $this->contractAddress = null;
        }

        private function parseTRC10Transaction(array $data){

            $this->tokenID = $data['contractData']['asset_name'];
            $this->token->dispatchTRC10Token($this->tokenID);
            $this->symbol = $this->token->symbol;
            $this->tokenName = $this->token->name;
            $this->isSupported = $this->token->supported;

        }

        private function parseSmartContractTransaction(array $data){
            if(isset($data['tokenTransferInfo']) && !empty($data['tokenTransferInfo']))
            {
                if(isset($data['tokenTransferInfo']['contract_address']))
                {
                    $this->contractAddress = $data['tokenTransferInfo']['contract_address'];
                    $this->token->dispatchTRC20Token($this->contractAddress);
                    $this->ownerAddress = $data['tokenTransferInfo']['from_address'];
                    $this->toAddress = $data['tokenTransferInfo']['to_address'];
                    $this->amount = $data['tokenTransferInfo']['amount_str'];
                    $this->symbol = $this->token->symbol;
                    $this->isToken = true;
                    $this->isSupported = $this->token->supported;

                    return true;
                }
            }

            return false;
        }

        public function storeTransaction(){
            return TrxTransaction::updateOrCreate([
                'account' => $this->account->id,
                'block' => $this->block,
                'hash' => $this->hash,
                'timestamp' => $this->timestamp,
                'ownerAddress' => $this->ownerAddress,
                'toAddress' => $this->toAddress,
                'contractType' => $this->contractType,
                'confirmed' => $this->confirmed,
                'amount' => $this->amount,
                'tokenID' => $this->tokenID,
                'tokenName' => $this->tokenName,
                'symbol' => $this->symbol,
                'contractAddress' => $this->contractAddress,
            ]);
        }


    }
