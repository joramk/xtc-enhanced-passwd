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

interface xtc_encryption_algorithm {

	static function createHash($password);
	static function validatePassword($password, $hash);
	static function getIterations($hash);
}

// Load required encryption algorithm classes always from same location
require_once (strpos(DIR_WS_CLASSES, DIR_FS_DOCUMENT_ROOT) === 0 ?
		'' : DIR_FS_DOCUMENT_ROOT) . DIR_WS_CLASSES .
		'pbkdf2/xtc_pbkdf2.php';
require_once (strpos(DIR_WS_CLASSES, DIR_FS_DOCUMENT_ROOT) === 0 ?
		'' : DIR_FS_DOCUMENT_ROOT) . DIR_WS_CLASSES .
		'scrypt/xtc_scrypt.php';
require_once (strpos(DIR_WS_CLASSES, DIR_FS_DOCUMENT_ROOT) === 0 ?
		'' : DIR_FS_DOCUMENT_ROOT) . DIR_WS_CLASSES .
		'bcrypt/xtc_bcrypt.php';

class xtc_encryption_wrapper {

	const ALGORITHM_MD5    = 0;
	const ALGORITHM_BCRYPT = 1;
	const ALGORITHM_PBKDF2 = 2;
	const ALGORITHM_SCRYPT = 3;
	
 	public static $ALGORITHM_DEFAULT = self::ALGORITHM_PBKDF2;
	public static $UPDATE_PASSWORDS  = true;
	
	public static function createHash($password, $algorithm = null) {
		$algorithm = self::checkAlgorithm($algorithm);
		if ($algorithm == self::ALGORITHM_PBKDF2) {
			return xtc_pbkdf2::createHash($password);
		} elseif ($algorithm == self::ALGORITHM_SCRYPT) {
			return xtc_scrypt::createHash($password);
		} elseif ($algorithm == self::ALGORITHM_BCRYPT) {
			return xtc_bcrypt::createHash($password);
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
		} elseif ($algorithm == self::ALGORITHM_BCRYPT) {
			return xtc_bcrypt::validatePassword($password, $hash);
		} elseif ($algorithm == self::ALGORITHM_MD5) {
			return md5($password) == $hash;
		}
	}
	
	public static function needsAlgorithmUpdate($hash) {
		return self::$UPDATE_PASSWORDS && (
				self::getAlgorithm($hash) != self::$ALGORITHM_DEFAULT
				|| self::getIterations($hash) != self::getIterations());
	}

	private static function getAlgorithm($hash) {
		if (preg_match('/^.+:\d+:.+:.+$/', $hash)) {
			return self::ALGORITHM_PBKDF2;
		} elseif (preg_match('/^\d+\$\d+\$\d+\$.+\$.+$/', $hash)) {
			return self::ALGORITHM_SCRYPT;
		} elseif (preg_match('/^.+\$.+\$[$.\/0-9A-Za-z]+$/', $hash)) {
			return self::ALGORITHM_BCRYPT;
		} elseif (preg_match('/^[a-f0-9]{32}$/i', $hash)) {
			return self::ALGORITHM_MD5;
		} else {
			trigger_error(__CLASS__ . ':' . __FUNCTION__ .
					':Unknown encryption algorithm detected.', E_USER_ERROR);
		}
	}

	private static function getIterations($hash = null, $algorithm = null) {
		$algorithm = self::checkAlgorithm($algorithm, $hash);
		if ($algorithm == self::ALGORITHM_PBKDF2) {
			return empty($hash) ? xtc_pbkdf2::getIterations() : xtc_pbkdf2::getIterations($hash);
		} elseif ($algorithm == self::ALGORITHM_SCRYPT) {
			return empty($hash) ? xtc_scrypt::getIterations() : xtc_scrypt::getIterations($hash);
		} elseif ($algorithm == self::ALGORITHM_BCRYPT) {
			return empty($hash) ? xtc_bcrypt::getIterations() : xtc_bcrypt::getIterations($hash);
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
			if ($algorithm != self::ALGORITHM_MD5
					&& $algorithm != self::ALGORITHM_PBKDF2
					&& $algorithm != self::ALGORITHM_SCRYPT
					&& $algorithm != self::ALGORITHM_BCRYPT) {
				trigger_error(__CLASS__ . ':' . __FUNCTION__ .
						':Invalid encryption algorithm defined.', E_USER_ERROR);
			}
			return $algorithm;
		}
	}
}
