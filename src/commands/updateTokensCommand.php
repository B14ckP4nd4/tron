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
        $TokensList = new TronScan();
        $TokensList = $TokensList->getTokensList(0,$limit);

        foreach ($TokensList['tokens'] as $token){
            \App\tron\TrxToken::firstOrCreate([
                'abbr' => $token['abbr'],
                'name' => $token['name'],
                'pairId' => $token['pairId'] ?? null,
                'contractAddress' => $token['contractAddress'] ?? null,
                'decimal' => $token['decimal'],
                'description' => $token['description'],
                'isTop' => $token['isTop'],
                'projectSite' => $token['projectSite'],
                'supply' => $token['supply'],
                'tokenType' => $token['tokenType'],
            ]);
        }

        $this->info('Tokens Updated Successfully');

        return true;
    }
}
