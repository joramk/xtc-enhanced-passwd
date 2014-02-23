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

// secure hashing of passwords using bcrypt with phpass for portability
// see http://codahale.com/how-to-safely-store-a-password/
// and http://www.openwall.com/phpass/

require_once 'PasswordHash.php';

class xtc_bcrypt implements xtc_encryption_algorithm {

	public static $ALGORITHM_ITERATION_COUNT     = 8;     // valid values are 4 to 31
	public static $ALGORITHM_ALLOW_WEAK_FALLBACK = false;
	
	public static function createHash($password) {
		$hasher = new PasswordHash(self::$ALGORITHM_ITERATION_COUNT,
				self::$ALGORITHM_ALLOW_WEAK_FALLBACK);
		return $hasher->HashPassword($password);
	}
	
	public static function validatePassword($password, $hash) {
		$hasher = new PasswordHash(self::$ALGORITHM_ITERATION_COUNT,
				self::$ALGORITHM_ALLOW_WEAK_FALLBACK);
		return $hasher->CheckPassword($password, $hash);
	}
	
	public static function getParameters($hash = null) {
		if (empty($hash)) {
			return self::$ALGORITHM_ITERATION_COUNT;
		} else {
			list ($P, $r) = explode('$', $hash);
			return $P . '$' . $r;
		}
	}
}
