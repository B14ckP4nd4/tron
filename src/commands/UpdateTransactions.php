<?php

    namespace blackpanda\tron\commands;

    use App\tron\TrxAccounts;
    use App\tron\TrxToken;
    use App\tron\TrxTransaction;
    use blackpanda\tron\objects\Token;
    use blackpanda\tron\objects\Transaction;
    use blackpanda\tron\objects\tronScan;
    use blackpanda\tron\Tron;
    use Illuminate\Console\Command;

    class UpdateTransactions extends Command
    {
        public $accounts;
        public $api;
        public $commandLogPrefix;


        Const CHECK_MINUTES = 5;
        Const START_TIMESTAMP = '1546300800'; // 01/01/2019 @ 12:00am (UTC)
        /**
         * The name and signature of the console command.
         *
         * @var string
         */
        protected $signature = 'trx:update-transactions';

        /**
         * The console command description.
         *
         * @var string
         */
        protected $description = 'Update All Accounts Transactions';
        /**
         * @var float|int
         */
        public $goMinutesBackForNewCheck;
        /**
         * @var tronScan
         */
        private $tronScan;

        /**
         * Create a new command instance.
         *
         * @return void
         */
        public function __construct()
        {
            parent::__construct();
            $this->accounts = TrxAccounts::all();
            $this->supportedTokens = TrxToken::where('supported', true)->get();
            $this->api = new Tron();
            $this->tronScan = new tronScan();
            $this->commandLogPrefix = "[TRX Transaction Tracker]";

            $this->goMinutesBackForNewCheck = self::CHECK_MINUTES * 60 * 1000;
        }

        /**
         * Execute the console command.
         *
         * @return mixed
         */
        public function handle()
        {
            $this->info("{$this->commandLogPrefix} starting...");

            $supportedTRC10 = TrxToken::where([
                ['type', '=', 'trc10'],
                ['supported', '=', 1],
            ])->get();

            $supportedTRC20 = TrxToken::where([
                ['type', '=', 'trc20'],
                ['supported', '=', 1],
            ])->get();


            foreach ($this->accounts as $account) {
                $this->tronScan->setAddress($account->address);
                $this->info("{$this->commandLogPrefix} get Address '{$account->address}'");

                $this->updateTronTransactions($account);

                $this->updateTokensTransactions($account);

                foreach ($supportedTRC20 as $trc20){
                    $this->updateContractsEvents($account,$trc20);
                }

            }

        }

        private function updateTronTransactions(TrxAccounts $account)
        {

            $token = new TrxToken;
            $token->tokenID = 0;
            $token->name = 'TRON';
            $token->symbol = 'TRX';

            $this->info("{$this->commandLogPrefix} Start checking '{$token}' Transfers...");

            $transactions = TrxTransaction::where([
                ['account', '=', $account->id],
                ['tokenName', '=', $token->name],
                ['tokenID', '=', $token->tokenID],
            ])->orderBy('timestamp', 'desc')->first();

            $startPoint = (empty($transactions)) ? self::START_TIMESTAMP * 1000 :
                $transactions->timestamp;
            $startPoint = $startPoint - $this->goMinutesBackForNewCheck;
            $endPoint = time() * 1000;

            $transactionCounter = 0;
            $totalTransactions = 0;

            $tronTransfers = $this->tronScan->transfers($token, '-timestamp', $transactionCounter, 50, $startPoint, $endPoint, true);

            if ($tronTransfers && isset($tronTransfers['data'])) {
                $totalTransactions = $tronTransfers['total'];
                $this->info("{$this->commandLogPrefix} {$totalTransactions} Tron Transfer found ...");
                while ($transactionCounter < $totalTransactions):
                    if (!$tronTransfers && !isset($tronTransfers['data'])) continue;

                    foreach ($tronTransfers['data'] as $data):
                        TrxTransaction::updateOrCreate([
                            'account' => $account->id,
                            'block' => $data['block'],
                            'hash' => $data['transactionHash'],
                            'timestamp' => $data['timestamp'],
                            'ownerAddress' => $data['transferFromAddress'],
                            'toAddress' => $data['transferToAddress'],
                            'contractType' => 1,
                            'confirmed' => $data['confirmed'],
                            'amount' => $data['amount'],
                            'tokenID' => 0,
                            'tokenName' => 'Tron',
                            'symbol' => 'TRX',
                            'contractAddress' => null,
                        ]);
                    endforeach;

                    $transactionCounter += count($tronTransfers['data']);
                    $tronTransfers = $this->tronScan->transfers($token, '-timestamp', $transactionCounter, 50, $startPoint, $endPoint, true);
                endwhile;
            }


        }

        private function updateTokensTransactions(TrxAccounts $account){
            $transactions = TrxTransaction::where([
                ['account' , '=', $account->id],
                ['contractType' , '=', 2],
            ])->orderBy('timestamp','desc')->first();

            $startPoint = (empty($transactions)) ? self::START_TIMESTAMP * 1000 :
                $transactions->timestamp;
            $startPoint = $startPoint - $this->goMinutesBackForNewCheck;
            $endPoint = time() * 1000;

            $transactionCounter = 0;
            $totalTransactions = 0;

            $transactionsList = $this->tronScan->transactions(true, '-timestamp', 50, $transactionCounter, $startPoint, $endPoint, true);

            $token = new Token();

            if ($transactionsList && isset($transactionsList['data'])) {
                $totalTransactions = $transactionsList['total'];
                $this->info("{$this->commandLogPrefix} {$totalTransactions} Transaction found ...");
                while ($transactionCounter < $totalTransactions):
                    if (!$transactionsList && !isset($transactionsList['data'])) continue;

                    foreach ($transactionsList['data'] as $data):

                        if($data['contractType'] !== 2) continue;

                        $token->getTokenByTokenId( (int) $data['contractData']['asset_name']);
                        TrxTransaction::updateOrCreate([
                            'account' => $account->id,
                            'block' => $data['block'],
                            'hash' => $data['hash'],
                            'timestamp' => $data['timestamp'],
                            'ownerAddress' => $data['ownerAddress'],
                            'toAddress' => $data['toAddress'],
                            'contractType' => 2,
                            'confirmed' => $data['confirmed'],
                            'amount' => $data['contractData']['amount'],
                            'tokenID' => $token->tokenID,
                            'tokenName' => $token->name,
                            'symbol' => $token->symbol,
                            'contractAddress' => null,
                        ]);

                    endforeach;

                    $transactionCounter += count($transactionsList['data']);

                    $this->info("{$this->commandLogPrefix} {$transactionCounter} Transaction has been checked ...");

                    $transactionsList = $this->tronScan->transactions(true, '-timestamp', 50, $transactionCounter, $startPoint, $endPoint, true);
                endwhile;
            }


        }

        private function updateContractsEvents(TrxAccounts $account,TrxToken $token){
            $this->info("{$this->commandLogPrefix} Start checking '{$token}' Transfers...");

            $transactions = TrxTransaction::where([
                ['account', '=', $account->id],
                ['contractType', '=', 3],
                ['contractAddress', '=', $token->contractAddress],
            ])->orderBy('timestamp', 'desc')->first();

            $startPoint = (empty($transactions)) ? self::START_TIMESTAMP * 1000 :
                $transactions->timestamp;
            $startPoint = $startPoint - $this->goMinutesBackForNewCheck;
            $endPoint = time() * 1000;

            $transactionCounter = 0;
            $totalTransactions = 0;

            $events = $this->tronScan->contractEvents($token->contractAddress, 50,$transactionCounter,$startPoint,$endPoint);

            if ($events && isset($events['data'])) {
                $totalTransactions = $events['total'];
                $this->info("{$this->commandLogPrefix} {$totalTransactions} Contract event found ...");
                while ($transactionCounter < $totalTransactions):
                    if (!$events && !isset($events['data'])) continue;

                    foreach ($events['data'] as $data):
                        TrxTransaction::updateOrCreate([
                            'account' => $account->id,
                            'block' => $data['block'],
                            'hash' => $data['transactionHash'],
                            'timestamp' => $data['timestamp'],
                            'ownerAddress' => $data['transferFromAddress'],
                            'toAddress' => $data['transferToAddress'],
                            'contractType' => 31,
                            'confirmed' => $data['confirmed'],
                            'amount' => $data['amount'],
                            'tokenID' => 0,
                            'tokenName' => $token->tokenName,
                            'symbol' => $token->symbol,
                            'contractAddress' => $token->contractAddress,
                        ]);
                    endforeach;

                    $transactionCounter += count($events['data']);
                    $events = $this->tronScan->contractEvents($token->contractAddress, 50, $transactionCounter, $startPoint, $endPoint);
                endwhile;
            }
        }



    }
