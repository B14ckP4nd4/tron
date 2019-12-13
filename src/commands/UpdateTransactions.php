<?php

    namespace blackpanda\tron\commands;

    use App\tron\TrxAccounts;
    use App\tron\TrxToken;
    use App\tron\TrxTransaction;
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
                ['type','=','trc10'],
                ['supported','=',1],
            ])->get();

            $supportedTRC20 = TrxToken::where([
                ['type','=','trc20'],
                ['supported','=',1],
            ])->get();


            foreach ($this->accounts as $account) {
                $this->info("{$this->commandLogPrefix} get Address '{$account->address}'");

                $this->getAllTransactions($account);

            }

        }

        private function getAllTransactions($account){
            $transactions = TrxTransaction::where([
                ['account', '=', $account->id],
            ])->orderBy('timestamp', 'desc')->first();

            $startPoint = (empty($transactions)) ? self::START_TIMESTAMP * 1000 :
                $transactions->timestamp;
            $startPoint = $startPoint - $this->goMinutesBackForNewCheck;
            $endPoint = strtotime('2019-11-16') * 1000;

            $this->tronScan->setAddress($account->address);

            $counter = 0;
            $total = 0;

            $transactions = $this->tronScan->transactions(true, '-timestamp', 50, $counter, $startPoint, $endPoint, true);
            sleep(5);

            if ($transactions && isset($transactions['data'])) {
                $total = $transactions['total'];
                $this->info("{$this->commandLogPrefix} {$total} transfers found ...");
                while ($counter < $total) {
                    if (!$transactions && !isset($transactions['data'])) continue;
                    foreach ($transactions['data'] as $transaction) {
                        $TransactionObject = new Transaction();
                        $TransactionObject->parseData($transaction);

                        if(!$TransactionObject->isInternal($transaction)){
                            $this->info("https://apilist.tronscan.org/api/transaction-info?hash=".$TransactionObject->hash);
                            $this->info($TransactionObject->isInternal($transaction));
                            if($TransactionObject->isSupported || $TransactionObject->tokenID == 0)
                            {
                                $TransactionObject->account = $account;
                                dump($TransactionObject->storeTransaction());
                            }
                        }
                    }
                    $counter += count($transactions['data']);
                    $this->info("{$this->commandLogPrefix} {$counter} has been Checked...");
                    $transactions = $this->tronScan->transactions(true, '-timestamp', 50, $counter, $startPoint, $endPoint, true);
                }
            }



            $this->info("{$this->commandLogPrefix} save & exit from Account ...");

        }

        private function getAllTransfers(TrxAccounts $account, TrxToken $token = null ,bool $trx = false)
        {
            if($trx)
            {
                $token = new TrxToken;
                $token->tokenID = 0;
                $token->name = 'TRON';
                $token->symbol = 'TRX';
            }
            $this->info("{$this->commandLogPrefix} Start checking '{$token}' Transfers...");

            $transactions = TrxTransaction::where([
                ['account', '=', $account->id],
                ['tokenName', '=', $token->tokenID],
            ])->orderBy('timestamp', 'desc')->first();

            $startPoint = (empty($transactions)) ? self::START_TIMESTAMP * 1000 :
                $transactions->timestamp;
            $startPoint = $startPoint - $this->goMinutesBackForNewCheck;
            $endPoint = time() * 1000;

            $this->tronScan->setAddress($account->address);

            $counter = 0;
            $total = 0;

            while ($counter <= $total) {
                $transfers = $this->tronScan->transfers($token, '-timestamp', $counter, 50, $startPoint, $endPoint, true);
                dump($transfers);
                if ($transfers && isset($transfers['data'])) {
                    $total = $transfers['total'];
                    $this->info("{$this->commandLogPrefix} {$total} transfers found ...");
                    foreach ($transfers['data'] as $transfer) {
                        TrxTransaction::updateOrCreate([
                            'account' => $account->id,
                            'block' => $transfer['block'],
                            'hash' => $transfer['transactionHash'],
                            'timestamp' => $transfer['timestamp'],
                            'ownerAddress' => $transfer['transferFromAddress'],
                            'toAddress' => $transfer['transferToAddress'],
                            'contractType' => ($token->tokenID == 0)? 1 : 2,
                            'confirmed' => $transfer['confirmed'],
                            'amount' => $transfer['amount'],
                            'tokenID' => $token->tokenID,
                            'tokenName' => $token->name,
                            'symbol' => $token->symbol,
                            'contractAddress' => null,
                        ]);
                    }
                }
                $counter += count($transfers['data']);
            }

            $this->info("{$this->commandLogPrefix} save & exit from Account ...");
        }
    }
