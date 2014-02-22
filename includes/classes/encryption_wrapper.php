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

abstract class xtc_encryption_algorithm {
	
	public function createHash();
	public function validatePassword();
	public function getIterations();
}

require_once 'pbkdf2/class.xtc_pbkdf2.php';
require_once 'scrypt/class.xtc_scrypt.php';

class xtc_encryption_wrapper {
	
	const ALGORITHM_MD5     = 0;
	const ALGORITHM_PBKDF2  = 1;
	const ALGORITHM_SCRYPT  = 2;
	
 	public static $ALGORITHM_DEFAULT = self::ALGORITHM_PBKDF2;
	
	public static function createHash($password, $algorithm = null) {
		$algorithm = self::checkAlgorithm($algorithm);
		if ($algorithm == self::ALGORITHM_PBKDF2) {
			return xtc_pbkdf2::createHash($password);
		} elseif ($algorithm == self::ALGORITHM_SCRYPT) {
			return xtc_scrypt::createHash($password);
		} elseif ($algorithm == self::ALGORITHM_MD5) {
			return md5($password);
		}
	}
	
	public static function validatePassword($password, $hash, $algorithm = null) {
		$algorithm = self::checkAlgorithm($algorithm, $hash);
		if ($algorithm == self::ALGORITHM_PBKDF2) {
			return xtc_pbkdf2::validatePassword($password, $hash);
		} elseif ($algorithm == self::ALGORITHM_SCRYPT) {
			return xtc_scrypt::validatePassword($password, $hash);
		} elseif ($algorithm == self::ALGORITHM_MD5) {
			return md5($password) == $hash;
		}
	}
	
	public static function needsAlgorithmUpdate($hash) {
		return self::getAlgorithm($hash) != self::$ALGORITHM_DEFAULT
				|| self::getIterations($hash) != self::getIterations();
	}

	private static function getAlgorithm($hash) {
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
/*	
	private static function getIterationCount($hash, $algorithm = null) {
		$algorithm = self::checkAlgorithm($algorithm, $hash);
		if ($algorithm == self::ALGORITHM_PBKDF2) {
			return xtc_pbkdf2::getIterationCount($hash);
		} elseif ($algorithm == self::ALGORITHM_SCRYPT) {
			return xtc_scrypt::getIterationCount($hash);
		} elseif ($algorithm == self::ALGORITHM_MD5) {
			return 0;
		}
	}
*/	
	private static function getIterations($hash = null, $algorithm = null) {
		$algorithm = self::checkAlgorithm($algorithm, $hash);
		if ($algorithm == self::ALGORITHM_PBKDF2) {
			return empty($hash) ? xtc_pbkdf2::getIterations() : xtc_pbkdf2::getIterations($hash);
		} elseif ($algorithm == self::ALGORITHM_SCRYPT) {
			return empty($hash) ? xtc_scrypt::getIterations() : xtc_scrypt::getIterations($hash);
		} elseif ($algorithm == self::ALGORITHM_MD5) {
			return 0;
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
