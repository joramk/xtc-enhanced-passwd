<?php
/*------------------------------------------------------------------------------
   $Id: class.xtc_bcrypt.php,v 1.0 

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

// secure hashing of passwords using bcrypt, needs PHP 5.3+
// see http://codahale.com/how-to-safely-store-a-password/

class xtc_bcrypt implements xtc_encryption_algorithm {

	public static $ALGORITHM_SELECTOR = CRYPT_BLOWFISH;
	public static $ALGORITHM_WORKLOAD = 12;
	
	public static function createHash($password) {
		$salt = substr(str_replace('+', '.',
				base64_encode(sha1(microtime(true), true))), 0, 22);
		return crypt($password, '$'. self::$ALGORITHM_SELECTOR .
				'$'. self::$ALGORITHM_WORKLOAD . '$' . $salt);
	}
	
	public static function validatePassword($password, $hash) {
		return $hash == crypt($password, $hash);
	}
	
	public static function getIterations($hash = null) {
		if (empty($hash)) {
		return self::$ALGORITHM_SELECTOR .
				'$'. self::$ALGORITHM_WORKLOAD;
		} else {
			list( , $s, $w) = explode('$', $hash);
			return $s . '$' . $w;
		}
	}
}
