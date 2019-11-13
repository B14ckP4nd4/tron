<?php


    namespace blackpanda\tron\objects;


    class Account
    {
        private $address;

        public function __construct(string $address)
        {
            $this->address = $address;
        }

        public function getAddress()
        {
            return $this->address;
        }

    }
