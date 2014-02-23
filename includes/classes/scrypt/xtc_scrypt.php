<?php
/*------------------------------------------------------------------------------
   $Id: class.xtc_scrypt.php,v 1.0 

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

require_once 'scrypt.php';

class xtc_scrypt extends Password implements xtc_encryption_algorithm {

	public static $CPU_DIFFICULTY      = 65536;
	public static $MEMORY_DIFFICULTY   = 12;
	public static $PARALLEL_DIFFICULTY = 2;
	public static $SALT_LENGTH         = 12;

    public static function createHash($password) {
		return parent::hash($password,
				parent::generateSalt(self::$SALT_LENGTH),
				self::$CPU_DIFFICULTY,
				self::$MEMORY_DIFFICULTY,
				self::$PARALLEL_DIFFICULTY);
	}
	
	public static function validatePassword($password, $hash) {
		return parent::check($password, $hash);
	}
	
	public static function getParameters($hash = null) {
		if (!empty($hash)) {
			$N = self::$CPU_DIFFICULTY;
			$r = self::$MEMORY_DIFFICULTY;
			$p = self::$PARALLEL_DIFFICULTY;
		} else {
			list ($N, $r, $p) = explode('$', $hash);
		}
		return $N . '$' . $r . '$' . $p;
	}
}
