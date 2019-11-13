<?php
namespace blackpanda\tron\support;

use Exception;
use InvalidArgumentException;

class Utils
{
    /**
     * Link verification
     *
     * @param $url
     * @return bool
     */
    public static function isValidUrl($url) :bool {
        return (bool)parse_url($url);
    }

    /**
     * Check whether the passed parameter is an array
     *
     * @param $array
     * @return bool
     */
    public static function isArray($array) : bool {
        return is_array($array);
    }

    /**
     * isZeroPrefixed
     *
     * @param string
     * @return bool
     */
    public static function isZeroPrefixed($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to isZeroPrefixed function must be string.');
        }
        return (strpos($value, '0x') === 0);
    }

    /**
     * stripZero
     *
     * @param string $value
     * @return string
     */
    public static function stripZero($value)
    {
        if (self::isZeroPrefixed($value)) {
            $count = 1;
            return str_replace('0x', '', $value, $count);
        }
        return $value;
    }

    /**
     * isNegative
     *
     * @param string
     * @return bool
     */
    public static function isNegative($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to isNegative function must be string.');
        }
        return (strpos($value, '-') === 0);
    }

    /**
     * Check if the string is a 16th notation
     *
     * @param $str
     * @return bool
     */
    public static function isHex($str) : bool {
        return is_string($str) and ctype_xdigit($str);
    }

    /**
     * hexToBin
     *
     * @param string
     * @return string
     */
    public static function hexToBin($value)
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('The value to hexToBin function must be string.');
        }
        if (self::isZeroPrefixed($value)) {
            $count = 1;
            $value = str_replace('0x', '', $value, $count);
        }
        return pack('H*', $value);
    }

    /**
     * @param $address
     * @return bool
     * @throws Exception
     */
    public static function validate($address)
    {
        $decoded = Base58::decode($address);

        $d1 = hash("sha256", substr($decoded,0,21), true);
        $d2 = hash("sha256", $d1, true);

        if(substr_compare($decoded, $d2, 21, 4)){
            throw new \Exception("bad digest");
        }
        return true;
    }

    /**
     * @throws Exception
     */
    public static function decodeBase58($input)
    {
        $alphabet = "123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz";

        $out = array_fill(0, 25, 0);
        for($i=0;$i<strlen($input);$i++){
            if(($p=strpos($alphabet, $input[$i]))===false){
                throw new Exception("invalid character found");
            }
            $c = $p;
            for ($j = 25; $j--; ) {
                $c += (int)(58 * $out[$j]);
                $out[$j] = (int)($c % 256);
                $c /= 256;
                $c = (int)$c;
            }
            if($c != 0){
                throw new Exception("address too long");
            }
        }

        $result = "";
        foreach($out as $val){
            $result .= chr($val);
        }

        return $result;
    }

    /**
     *
     * @throws Exception
     */
    public static function pubKeyToAddress($pubkey) {
        return '41'. substr(Keccak::hash(substr(hex2bin($pubkey), 1), 256), 24);
    }

    /**
     * Test if a string is prefixed with "0x".
     *
     * @param string $str
     *   String to test prefix.
     *
     * @return bool
     *   TRUE if string has "0x" prefix or FALSE.
     */
    public static function hasHexPrefix($str)
    {
        return substr($str, 0, 2) === '0x';
    }

    /**
     * Remove Hex Prefix "0x".
     *
     * @param string $str
     * @return string
     */
    public static function removeHexPrefix($str)
    {
        if (!self::hasHexPrefix($str)) {
            return $str;
        }
        return substr($str, 2);
    }


    public static function jsonDecode(string $json , bool $assoc = false)
    {
        $json = json_decode($json , $assoc);
        if(json_last_error() == JSON_ERROR_NONE)
        {
            return $json;
        }
        return false;
    }

    /**
     * Convert float to trx format
     *
     * @param $double
     * @return int
     */
    public static function toTron($double): int {
        return (int) bcmul((string)$double, (string)1e6,0);
    }

    /**
     * Convert trx to float
     *
     * @param $amount
     * @return float
     */
    public static function fromTron($amount): float {
        return (float) bcdiv((string)$amount, (string)1e6, 8);
    }

    public static function isSupportedToken(string $name = null ,int $id = null ,string $contractAddress = null ,string $type = null)
    {
        if(is_null($name) && is_null($id) && is_null($contractAddress) && is_null($type)) return false;
        $valid = true;

        $token = [
            'name' => $name,
            'ContractAddress' => $contractAddress,
            'type' => $type,
            'id' => $id,
        ];

        $supportedTokens = config('tron.SupportedTokens');

        if(!isset($supportedTokens[$token['name']])) return false;

        foreach ($supportedTokens[$token['name']] as $key => $val){
            if(!is_null($token[$key]) && $token[$key] !== $val) $valid = $key;
        }

        return $valid;


    }
}
