<?php
/**
 * Password hashing with PBKDF2.
 * @author: havoc AT defuse.ca
 * www: https://defuse.ca/php-pbkdf2.htm
 */
abstract class Pbkdf2Abstract
{

    static protected $PBKDF2_HASH_ALGORITHM = "sha256";
    static protected $PBKDF2_SALT_BYTES     = 24;
    static protected $PBKDF2_HASH_BYTES     = 24;
    static protected $HASH_SECTIONS         = 4;
    static protected $HASH_ALGORITHM_INDEX  = 0;
    static protected $HASH_ITERATION_INDEX  = 1;
    static protected $HASH_SALT_INDEX       = 2;
    static protected $HASH_PBKDF2_INDEX     = 3;

    public static function create_hash($password, $iterations)
    {
        // format: algorithm:iterations:salt:hash
        $salt = base64_encode(mcrypt_create_iv(self::$PBKDF2_SALT_BYTES, MCRYPT_DEV_URANDOM));
        return self::$PBKDF2_HASH_ALGORITHM . ":" . $iterations . ":" . $salt . ":" .
            base64_encode(self::pbkdf2(
                    self::$PBKDF2_HASH_ALGORITHM, $password, $salt, $iterations, self::$PBKDF2_HASH_BYTES, true
        ));
    }

    public static function validate_password($password, $good_hash)
    {
        $params = explode(":", $good_hash);
        if (count($params) < self::$HASH_SECTIONS) return false;
        $pbkdf2 = base64_decode($params[self::$HASH_PBKDF2_INDEX]);
        return self::slow_equals(
                $pbkdf2, self::pbkdf2(
                    $params[self::$HASH_ALGORITHM_INDEX], $password, $params[self::$HASH_SALT_INDEX], (int) $params[self::$HASH_ITERATION_INDEX], strlen($pbkdf2), true
                )
        );
    }

    /**
     * Compares two strings $a and $b in length-constant time.
     * @param type $a
     * @param type $b
     * @return type
     */
    protected static function slow_equals($a, $b)
    {
        $diff = strlen($a) ^ strlen($b);
        for ($i = 0; $i < strlen($a) && $i < strlen($b); $i++) {
            $diff |= ord($a[$i]) ^ ord($b[$i]);
        }
        return $diff === 0;
    }

    /**
     * PBKDF2 key derivation function as defined by RSA's PKCS #5: https://www.ietf.org/rfc/rfc2898.txt
     * $algorithm - The hash algorithm to use. Recommended: SHA256
     * $password - The password.
     * $salt - A salt that is unique to the password.
     * $count - Iteration count. Higher is better, but slower. Recommended: At least 1000.
     * $key_length - The length of the derived key in bytes.
     * $raw_output - If true, the key is returned in raw binary format. Hex encoded otherwise.
     * Returns: A $key_length-byte key derived from the password and salt.
     *
     * Test vectors can be found here: https://www.ietf.org/rfc/rfc6070.txt
     *
     * This implementation of PBKDF2 was originally created by https://defuse.ca
     * With improvements by http://www.variations-of-shadow.com
     */
    protected static function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
    {
        $algorithm = strtolower($algorithm);
        if (!in_array($algorithm, hash_algos(), true))
                die('PBKDF2 ERROR: Invalid hash algorithm.');
        if ($count <= 0 || $key_length <= 0)
                die('PBKDF2 ERROR: Invalid parameters.');

        $hash_length = strlen(hash($algorithm, "", true));
        $block_count = ceil($key_length / $hash_length);

        $output = "";
        for ($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last   = $salt . pack("N", $i);
            // first iteration
            $last   = $xorsum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
            }
            $output .= $xorsum;
        }

        if ($raw_output) return substr($output, 0, $key_length);
        else return bin2hex(substr($output, 0, $key_length));
    }
}

?>
