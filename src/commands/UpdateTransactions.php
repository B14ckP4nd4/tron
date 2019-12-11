<?php

    namespace blackpanda\tron\commands;

    use Illuminate\Console\Command;

    class UpdateTransactions extends Command
    {
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
            //
        }
    }
