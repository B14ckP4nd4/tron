<?php


    namespace blackpanda\tron;


    use Illuminate\Support\Facades\Facade;

    class TronFacade extends Facade
    {
        protected static function getFacadeAccessor()
        {
            return Tron::class;
        }
    }
