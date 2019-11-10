<?php


    namespace blackpanda\tron;


    use Illuminate\Support\ServiceProvider;

    class TronServiceProvider extends ServiceProvider
    {
        public function register()
        {
            // Register Package Service Provider
            $this->app->bind('Tron',function (){
                return new Tron();
            });


        }

    }
