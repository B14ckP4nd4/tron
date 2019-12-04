<?php

namespace blackpanda\tron\commands;

use blackpanda\tron\objects\TronScan;
use Illuminate\Console\Command;

class updateTokensCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'trx:update-tokens {limit}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Tron TRC10 AND TRC20 Tokens List';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $limit = $this->argument('limit') ?? 100 ;
        $tokensList = new tronScan();
        $trc10 = $tokensList->tokensList($limit,'trc10');
        $trc20 = $tokensList->tokensList($limit,'trc20');

        foreach ($trc10 as $token){
            \App\tron\TrxToken::updateOrCreate([
                'name' => $token['name'],
                'symbol' => $token['abbr'],
                'tokenID' => $token['tokenId'],
                'type' => $token['tokenType'],
                'url' => $token['projectSite'],
                'logo' => $token['imgUrl'] ?? null,
                'description' => $token['description'],
                'contractAddress' => $token['contractAddress'] ?? null,
            ]);
        }

        foreach ($trc20 as $trc20token){
            \App\tron\TrxToken::updateOrCreate([
                'name' => $trc20token['name'],
                'symbol' => $trc20token['abbr'],
                'tokenID' => $trc20token['tokenId'] ?? 0,
                'type' => $trc20token['tokenType'],
                'url' => $trc20token['projectSite'],
                'logo' => $trc20token['imgUrl'] ?? null,
                'description' => $trc20token['description'],
                'contractAddress' => $trc20token['contractAddress'] ?? null,
            ]);
        }

        $this->info('Tokens Updated Successfully');

        return true;
    }
}
