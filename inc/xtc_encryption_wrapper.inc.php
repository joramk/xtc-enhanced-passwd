<?php
/*---------------------------------------------------------------------------
   $Id: xtc_encrypt_password.inc.php 1cf1e3f88d62 2014-02-22 20:13:21Z joramk $   

   Contribution for XT-Commerce http://www.xt-commerce.com
   by Tenretni Marketing GmbH http://www.tenretni-marketing.de

   Copyright 2014 Tenretni Marketing GmbH
   --------------------------------------------------------------------------
   based on: 
   (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
   (c) 2002-2003 osCommerce www.oscommerce.com 
   (c) 2003  nextcommerce www.nextcommerce.org
   (c) 2003 XT-Commerce

   Released under the GNU General Public License 
   ---------------------------------------------------------------------------*/

interface xtc_encryption_algorithm {

	/**
	 * Creates a password hash for the given plaintext password.
	 * 
	 * @param string $password
	 * @return string
	 */
	static function createHash($password);

	/**
	 * Validates a password against a password hash and returns the result.
	 * 
	 * @param string $password
	 * @param string $hash
	 * @return boolean
	 */
	static function validatePassword($password, $hash);

	/**
	 * Gets the crrent default encryption parameters
	 * or the parameters used to generate the optionally given hash.
	 * 
	 * @param string $hash
	 * @return string
	 */
	static function getParameters($hash = null);
}

// Load required encryption algorithm classes always from same location.
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

	const ALGORITHM_MD5      = 0;
	const ALGORITHM_SHA1SALT = 1;
	const ALGORITHM_BCRYPT   = 2;
	const ALGORITHM_PBKDF2   = 3;
	const ALGORITHM_SCRYPT   = 4;

	/**
	 * Defines the default encryption algorithm to use.
	 * 
	 * @var int 
	 */
 	public static $ALGORITHM_DEFAULT = self::ALGORITHM_PBKDF2;

	/**
	 * Defines wheter passwords get updated on validation or not.
	 * 
	 * @var boolean 
	 */
	public static $UPDATE_PASSWORDS  = true;

	/**
	 * Creates a hash for a password for the optionally defined algorithm.
	 * 
	 * @param string $password
	 * @param int $algorithm
	 * @return string
	 */
	public static function createHash($password, $algorithm = null) {
		switch (self::checkAlgorithm($algorithm)) {
			case self::ALGORITHM_PBKDF2:
				return xtc_pbkdf2::createHash($password);
			case self::ALGORITHM_SCRYPT:
				return xtc_scrypt::createHash($password);
			case self::ALGORITHM_BCRYPT:
				return xtc_bcrypt::createHash($password);
			case self::ALGORITHM_MD5:
				return md5($password);
			case self::ALGORITHM_SHA1SALT:
				return sha1($password . SALT_KEY);
		}
	}

	/**
	 * Validates a password against the given hash,
	 * optionally with the defined algorithm.
	 * 
	 * @param string $password
	 * @param string $hash
	 * @param int $algorithm
	 * @return boolean
	 */
	public static function validatePassword($password, $hash, $algorithm = null) {
		switch (self::checkAlgorithm($algorithm, $hash)) {
			case self::ALGORITHM_PBKDF2:
				return xtc_pbkdf2::validatePassword($password, $hash);
			case self::ALGORITHM_SCRYPT:
				return xtc_scrypt::validatePassword($password, $hash);
			case self::ALGORITHM_BCRYPT:
				return xtc_bcrypt::validatePassword($password, $hash);
			case self::ALGORITHM_SHA1SALT:
				return sha1($password . SALT_KEY) === $hash;
			case self::ALGORITHM_MD5:
				return md5($password) === $hash;
		}
	}

	/**
	 * Checks if a algorithm update on the given hash is needed.
	 * 
	 * @param string $hash
	 * @return boolean
	 */
	public static function needsAlgorithmUpdate($hash) {
		return self::$UPDATE_PASSWORDS &&
				(self::getAlgorithm($hash) != self::$ALGORITHM_DEFAULT
				|| self::getParameters($hash) != self::getParameters());
	}

	/**
	 * Gets the algorithm parameters from the given hash.
	 * 
	 * @param string $hash
	 * @param int $algorithm
	 * @return string
	 */
	private static function getParameters($hash = null, $algorithm = null) {
		switch (self::checkAlgorithm($algorithm, $hash)) {
			case self::ALGORITHM_PBKDF2:
				return empty($hash) ? xtc_pbkdf2::getParameters() :
						xtc_pbkdf2::getParameters($hash);
			case self::ALGORITHM_SCRYPT:
				return empty($hash) ? xtc_scrypt::getParameters() :
						xtc_scrypt::getParameters($hash);
			case self::ALGORITHM_BCRYPT:
				return empty($hash) ? xtc_bcrypt::getParameters() :
						xtc_bcrypt::getParameters($hash);
			default:
				return '';
		}
	}

	/**
	 * Checks wheter an hash or algorithm is valid and can be used for
	 * encryption and decryption of passwords.
	 * 
	 * @param int $algorithm
	 * @param string $hash
	 * @return int
	 */
	private static function checkAlgorithm($algorithm, $hash = null) {
		if (empty($algorithm) && empty($hash)) {
			self::checkInsecureAlgorithm();
			return self::$ALGORITHM_DEFAULT;
		} elseif (empty($algorithm) && !empty($hash)) {
			return self::getAlgorithm($hash);
		} else {
			self::validateAlgorithm($algorithm);
			return $algorithm;
		}
	}

	/**
	 * Detects the used algorithm for a given password hash.
	 * 
	 * @param string $hash
	 * @return int
	 */
	private static function getAlgorithm($hash) {
		if (preg_match('/^.+:\d+:.+:.+$/', $hash)) {
			return self::ALGORITHM_PBKDF2;
		} elseif (preg_match('/^\d+\$\d+\$\d+\$.+\$.+$/', $hash)) {
			return self::ALGORITHM_SCRYPT;
		} elseif (preg_match('/^.+\$.+\$[$.\/0-9A-Za-z]+$/', $hash)) {
			return self::ALGORITHM_BCRYPT;
		} elseif (preg_match('/^[a-f0-9]{40}$/i', $hash)) {
			return self::ALGORITHM_SHA1SALT;
		} elseif (preg_match('/^[a-f0-9]{32}$/i', $hash)) {
			return self::ALGORITHM_MD5;
		} else {
			trigger_error(__CLASS__ . '::' . __FUNCTION__ .
					' Unknown encryption algorithm detected.',
					E_USER_ERROR);
		}
	}

	/**
	 * Validates the given algorithm and triggers and E_USER_ERROR if
	 * the algorithm is not supported.
	 * 
	 * @param type $algorithm
	 */
	private static function validateAlgorithm($algorithm) {
		if ($algorithm != self::ALGORITHM_PBKDF2
				&& $algorithm != self::ALGORITHM_SCRYPT
				&& $algorithm != self::ALGORITHM_BCRYPT
				&& $algorithm != self::ALGORITHM_SHA1SALT
				&& $algorithm != self::ALGORITHM_MD5) {
			trigger_error(__CLASS__ . '::' . __FUNCTION__ .
					' Invalid encryption algorithm defined.',
					E_USER_ERROR);
		}
	}
	
	/**
	 * Checks for an insecure default algorithm and triggers E_USER_WARNING
	 * if an insecure default encryption algorithm is defined.
	 */
	private static function checkInsecureAlgorithm() {
		if(self::$ALGORITHM_DEFAULT == self::ALGORITHM_MD5 ||
				self::$ALGORITHM_DEFAULT == self::ALGORITHM_SHA1SALT) {
			trigger_error(__CLASS__ . '::' . __FUNCTION__ .
					' Insecure default encryption algorithm defined.',
					E_USER_WARNING);
		}
	}
}
