<?php


    namespace blackpanda\tron;


    use Illuminate\Foundation\AliasLoader;
    use Illuminate\Support\ServiceProvider;

    class TronServiceProvider extends ServiceProvider
    {
        public function register()
        {
            // Register Package Service Provider
            $this->app->bind('Tron',function (){
                return new Tron();
            });


            // register Facades
            $loader = AliasLoader::getInstance();
            $loader->alias('TRON', 'blackpanda\tron\TronFacade');

            // Register
            $this->app->register(TronEventServiceProvider::class);
        }

        public function boot()
        {
            // Register Publishes
            $this->publishes([
                __DIR__ . '/../publishes/configs' => config_path(''),
            ], 'configs');
        }

    }
