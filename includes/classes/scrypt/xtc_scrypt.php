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

	static public $CPU_DIFFICULTY      = 16384;
	static public $MEMORY_DIFFICULTY   = 8;
	static public $PARALLEL_DIFFICULTY = 1;
	static public $SALT_LENGTH         = 8;

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
	
	public static function getIterations($hash = null) {
		if (!empty($hash)) {
			$N = self::$CPU_DIFFICULTY;
			$r = self::$MEMORY_DIFFICULTY;
			$p = self::$PARALLEL_DIFFICULTY;
		} else {
	        list ($N, $r, $p) = explode('$', $hash);
		}
		return sha1($N . '$' . $r . '$' . $p);
	}
}
