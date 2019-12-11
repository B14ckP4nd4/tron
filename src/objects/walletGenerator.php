<?php


    namespace blackpanda\tron\objects;


    use blackpanda\tron\support\Base58;
    use blackpanda\tron\support\Base58Check;
    use blackpanda\tron\support\Crypto;
    use blackpanda\tron\support\Hash;
    use blackpanda\tron\support\Keccak;
    use Phactor\Key;

    class walletGenerator
    {
        public function __construct()
        {

        }

        public function generateWallet()
        {
            $key = new Key();

            $odd = false;

            while (!$odd)
            {
                $keyPair = $key->GenerateKeypair();
                if(strlen($keyPair['public_key']) % 2 == 0) $odd = true;
            }

            $privateKeyHex = $keyPair['private_key_hex'];
            $pubKeyHex = $keyPair['public_key'];

            $pubKeyBin = hex2bin($pubKeyHex);
            $addressHex = $this->getAddressHex($pubKeyBin);
            $addressBin = hex2bin($addressHex);
            $addressBase58 = $this->getBase58CheckAddress($addressBin);
            $address = new Address($addressBase58,$privateKeyHex,$addressHex);
            if($address->isValid())
                return $address;

            return false;

        }

        private function getAddressHex($pubKeyBin)
        {
            if (strlen($pubKeyBin) == 65) {
                $pubKeyBin = substr($pubKeyBin, 1);
            }

            $hash = Keccak::hash($pubKeyBin , 256);
            $hash = substr($hash, 24);

            return Address::ADDRESS_PREFIX . $hash;
        }

        private function getBase58CheckAddress($addressBin){
            $hash0 = Hash::SHA256($addressBin);
            $hash1 = Hash::SHA256($hash0);
            $checksum = substr($hash1, 0, 4);
            $checksum = $addressBin . $checksum;
            $result = Base58::encode(Crypto::bin2bc($checksum));

            return $result;
        }


    }
