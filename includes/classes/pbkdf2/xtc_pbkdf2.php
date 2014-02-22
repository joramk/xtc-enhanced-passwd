<?php
/*------------------------------------------------------------------------------
   $Id: encryption_wrapper.php,v 1.0 

   Contribution for XT-Commerce http://www.xt-commerce.com
   by Tenretni Marketing GmbH http://www.tenretni-marketing.de

   Copyright 2014 Tenretni Marketing GmbH
   -----------------------------------------------------------------------------------------
   based on: 
   (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
   (c) 2002-2003 osCommerce www.oscommerce.com 
   (c) 2003  nextcommerce www.nextcommerce.org
   (c) 2003 XT-Commerce

   Released under the GNU General Public License 
   ---------------------------------------------------------------------------------------*/

require_once 'PasswordHashClass.php';

abstract class xtc_pbkdf2 extends PasswordHashClass implements xtc_encryption_algorithm {

	static public $PBKDF2_HASH_ALGORITHM = "sha256";
	static public $PBKDF2_ITERATIONS     = 262144;
	static public $PBKDF2_SALT_BYTES     = 24;
	static public $PBKDF2_HASH_BYTES     = 24;

	public static function createHash($password) {
		$salt = base64_encode(mcrypt_create_iv(self::$PBKDF2_SALT_BYTES, MCRYPT_DEV_URANDOM));
		return self::$PBKDF2_HASH_ALGORITHM . ":" . self::$PBKDF2_ITERATIONS . ":" .  $salt . ":" . 
				base64_encode(parent::pbkdf2(
				self::$PBKDF2_HASH_ALGORITHM,
				$password,
				$salt,
				self::$PBKDF2_ITERATIONS,
				self::$PBKDF2_HASH_BYTES,
				true));
	}
	
	public static function validatePassword($password, $hash) {
		return parent::validate_password($password, $hash);
	}
	
	public static function getIterations($hash = null) {
		if (empty($hash)) {
			return self::$PBKDF2_ITERATIONS;
		} else {
	        $params = explode(":", $hash);
	        return $params[HASH_ITERATION_INDEX];
		}
    }
}
