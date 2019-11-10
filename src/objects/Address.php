<?php


    namespace blackpanda\tron\objects;


    use blackpanda\tron\support\Base58Check;
    use blackpanda\tron\support\Hash;

    class Address
    {
        protected $address;
        protected $privateKey;
        protected $hexAddress;

        CONST ADDRESS_SIZE = 34;
        CONST ADDRESS_PREFIX = "41";
        CONST ADDRESS_PREFIX_BYTE = 0x41;

        public function __construct(string $address,string $privateKey = null,string $hexAddress = null)
        {
            $this->address = $address;
            $this->privateKey = $privateKey;
            $this->hexAddress = $hexAddress;
        }

        public function __get($name)
        {
            return $this->{$name};
        }

        public function validateAddress() : bool
        {
            // Check Address Size
            if(strlen($this->address) !== self::ADDRESS_SIZE ) return false;

            // Check UTF8
            $address = Base58Check::decode($this->address, false , 0 , false);
            $utf8 = hex2bin($address);
            if(strlen($utf8) !== 25) return false;
            //if(strpos($utf8 , self::ADDRESS_PREFIX_BYTE) !== 0) return false;

            // Validate CheckSum
            $checkSum = substr($utf8, 21);
            $address = substr($utf8, 0, 21);
            $hash0 = Hash::SHA256($address);
            $hash1 = Hash::SHA256($hash0);
            $checkSum1 = substr($hash1, 0, 4);

            if($checkSum !== $checkSum1) return false;

            // Check First Letter of Address
            if(substr($this->address , 0,1) !== 'T') return false;

            return true;
        }




    }
