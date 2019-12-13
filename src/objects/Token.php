<?php


    namespace blackpanda\tron\objects;


    use App\tron\TrxToken;

    class Token
    {
        public $id;
        public $name;
        public $symbol;
        public $tokenID;
        public $type;
        public $url;
        public $logo;
        public $description;
        public $contractAddress;
        public $supported;
        public $token;
        private $tronScan;

        /**
         * token constructor.
         * @param int $id
         * @param string $name
         * @param string $symbol
         * @param int $tokenID
         */
        public function __construct(int $id = null,int $tokenID = 0,string $name = null,string $symbol = null)
        {
            $this->id = $id ?? 0;
            $this->name = $name;
            $this->symbol = $symbol;
            $this->tokenID = $tokenID;
            $this->tronScan = new TronScan();

            if( $this->tokenID != 0 ){
                $this->getTokenByTokenId($this->tokenID);
            }
        }

        public function findToken(array $arg)
        {
            return TrxToken::where($arg[0],$arg[1])->first();
        }

        public function getTokenByTokenId(int $tokenId)
        {
            $token = $this->findToken(['tokenID',$tokenId]);

            if(!$token) return false;

            $this->token = $token;

            return $this->token;
        }

        public function getTokenByContactAddress(string $contactAddress){
            $token = $this->findToken(['contactAddress',$contactAddress]);

            if(!$token) return false;

            $this->token = $token;

            return $this->token;
        }

        public function store()
        {
            return TrxToken::updateOrCreate([
                'name' => $this->name,
                'symbol' => $this->symbol,
                'tokenID' => $this->tokenID ?? 0,
                'type' => $this->type,
                'url' => $this->url,
                'logo' => $this->logo,
                'description' => $this->description,
                'contractAddress' => $this->contractAddress ?? null,
            ]);
        }

        public function dispatchTRC10Token(int $tokenID)
        {
            if($this->getTokenByTokenId($tokenID)) return $this->getTokenByTokenId($tokenID);
            $token = $this->tronScan->getTRC10tokenByID($tokenID);
            if(isset($token['data'])){
                $theToken = $token['data'][0];
                $this->tokenID = $theToken['id'];
                $this->name = $theToken['name'];
                $this->symbol = $theToken['abbr'];
                $this->type = 'trc10';
                $this->url = $theToken['url'] ?? null;
                $this->logo = $theToken['imgUrl'] ?? null;
                $this->description = $theToken['description'] ?? null;
                $this->contractAddress = null;

                return $this;
            }

            return false;
        }

        public function dispatchTRC20Token(string $contractAddress)
        {
            if($this->getTokenByContactAddress($contractAddress)) return $this->getTokenByContactAddress($tokenID);
            $token = $this->tronScan->getTRC20TokenByContractAddress($contractAddress);
            if(isset($token['trc20_tokens'])){
                $theToken = $token['trc20_tokens'][0];
                $this->tokenID = 0;
                $this->name = $theToken['name'];
                $this->symbol = $theToken['symbol'];
                $this->type = 'trc20';
                $this->url = $theToken['home_page'] ?? null;
                $this->logo = $theToken['icon_url'] ?? null;
                $this->description = $theToken['token_desc'] ?? null;
                $this->contractAddress = $theToken['contract_address'] ;

                return $this;
            }

            return false;
        }


        private function setProperties(TrxToken $token)
        {
            foreach ($token as $key => $val)
            {
                $this->{$key} = $val;
            }

            $this->token = $token;
        }


    }
