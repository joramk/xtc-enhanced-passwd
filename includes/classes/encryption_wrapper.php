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

require_once DIR_FS_INC . 'xtc_create_password.inc.php';
require_once DIR_FS_INC . 'xtc_encrypt_password.inc.php';
require_once DIR_FS_INC . 'xtc_validate_password.inc.php';
require_once              'pbkdf2/class.xtc_pbkdf2.php';
require_once              'scrypt/class.xtc_scrypt.php';

class xtc_encryption_wrapper {
	
	const ALGORITHM_MD5     = 0;
	const ALGORITHM_PBKDF2  = 1;
	const ALGORITHM_SCRYPT  = 2;
	
 	public static $ALGORITHM_DEFAULT = self::ALGORITHM_PBKDF2;
	
	public static function createHash($password, $algorithm = null) {
		$algorithm = self::checkAlgorithm($algorithm);
		if ($algorithm == self::ALGORITHM_PBKDF2) {
			return xtc_pbkdf2::create_hash($password);
		} elseif ($algorithm == self::ALGORITHM_SCRYPT) {
			return xtc_scrypt::hash($password);
		} elseif ($algorithm == self::ALGORITHM_MD5) {
			return xtc_encrypt_password($password);
		}
	}
	
	public static function validatePassword($password, $hash, $algorithm = null) {
		$algorithm = self::checkAlgorithm($algorithm, $hash);
		if ($algorithm == self::ALGORITHM_PBKDF2) {
			return xtc_pbkdf2::validate_password($password, $hash);
		} elseif ($algorithm == self::ALGORITHM_SCRYPT) {
			return xtc_scrypt::check($password, $hash);
		} elseif ($algorithm == self::ALGORITHM_MD5) {
			return xtc_validate_password($password, $hash);
		}
	}
	
	public static function getIterationCount($hash, $algorithm = null) {
		$algorithm = self::checkAlgorithm($algorithm, $hash);
		if ($algorithm == self::ALGORITHM_PBKDF2) {
			return xtc_pbkdf2::getIterationCount($hash);
		} elseif ($algorithm == self::ALGORITHM_SCRYPT) {
			return xtc_scrypt::getIterationCount($hash);
		} elseif ($algorithm == self::ALGORITHM_MD5) {
			return 0;
		}
	}
	
	public static function getIterations($algorithm = null) {
		$algorithm = self::checkAlgorithm($algorithm);
		if ($algorithm == self::ALGORITHM_PBKDF2) {
			return xtc_pbkdf2::$PBKDF2_ITERATIONS;
		} elseif ($algorithm == self::ALGORITHM_SCRYPT) {
			return sha1(xtc_scrypt::$CPU_DIFFICULTY . '$' .
					xtc_scrypt::$MEMORY_DIFFICULTY . '$' .
					xtc_scrypt::$PARALLEL_DIFFICULTY);
		} elseif ($algorithm == self::ALGORITHM_MD5) {
			return 0;
		}
	}
	
	public static function getAlgorithm($hash) {
		if (preg_match('/^.+:\d+:.+:.+$/', $hash)) {
			return self::ALGORITHM_PBKDF2;
		} elseif (preg_match('/^\d+\$\d+\$\d+\$.+\$.+$/', $hash)) {
			return self::ALGORITHM_SCRYPT;
		} elseif (preg_match('/^[a-f0-9]{32}$/i', $hash)) {
			return self::ALGORITHM_MD5;
		} else {
			trigger_error(__CLASS__ . ':' . __FUNCTION__ .
					':Unknown encryption algorithm detected.', E_USER_ERROR);
		}
	}
	
	private static function checkAlgorithm($algorithm, $hash = null) {
		if (empty($algorithm) && empty($hash)) {
			return self::$ALGORITHM_DEFAULT;
		} elseif (empty($algorithm) && !empty($hash)) {
			return self::getAlgorithm($hash);
		} else {
			if ($algorithm != self::ALGORITHM_MD5 && $algorithm != self::ALGORITHM_PBKDF2
					&& $algorithm != self::ALGORITHM_SCRYPT) {
				trigger_error(__CLASS__ . ':' . __FUNCTION__ .
						':Invalid encryption algorithm defined.', E_USER_ERROR);
			}
			return $algorithm;
		}
	}
}
