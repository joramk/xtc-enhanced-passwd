<?php
/*-----------------------------------------------------------------
* 	ID:						xtc_create_password.inc.php
* 	Letzter Stand:			v2.3
* 	zuletzt geaendert von:	cseoak
* 	Datum:					2012/11/19
*
* 	Copyright (c) since 2010 commerce:SEO by Webdesign Erfurt
* 	http://www.commerce-seo.de
* ------------------------------------------------------------------
* 	based on:
* 	(c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
* 	(c) 2002-2003 osCommerce - www.oscommerce.com
* 	(c) 2003     nextcommerce - www.nextcommerce.org
* 	(c) 2005     xt:Commerce - www.xt-commerce.com
* 	Released under the GNU General Public License
* ---------------------------------------------------------------*/


// Load enhanced password encryption class
include_once 'xtc_encryption_wrapper.inc.php';

/**
 * Creates a random string for a given length with printable characters only
 *
 * @param int $length
 * @return string
 */
function xtc_RandomString($length) {
        $chars = array( 'a', 'A', 'b', 'B', 'c', 'C', 'd', 'D', 'e', 'E', 'f', 'F', 'g', 'G', 'h', 'H', 'i', 'I', 'j', 'J',  'k', 'K', 'l', 'L', 'm', 'M', 'n','N', 'o', 'O', 'p', 'P', 'q', 'Q', 'r', 'R', 's', 'S', 't', 'T',  'u', 'U', 'v','V', 'w', 'W', 'x', 'X', 'y', 'Y', 'z', 'Z', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0');
        $max_chars = count($chars) - 1;
        srand( (double) microtime()*1000000);
        $rand_str = '';
        for($i=0;$i<$length;$i++) {
                $rand_str = ( $i == 0 ) ? $chars[rand(0, $max_chars)] : $rand_str . $chars[rand(0, $max_chars)];
        }
        return $rand_str;
}

/**
 * Creates a random password hash with a given password length
 *
 * @param int $length
 * @return string
 */
function xtc_create_password($length) {
	$min_length = is_numeric(ENTRY_PASSWORD_MIN_LENGTH) ? ENTRY_PASSWORD_MIN_LENGTH : 8;
	$pass=xtc_RandomString($length > $min_length ? $length : $min_length);
	if (class_exists('xtc_encryption_wrapper')) {
		return xtc_encryption_wrapper::createHash($pass);
	} else {
		if (ACCOUNT_PASSWORD_SECURITY == 'false') {
			return md5($pass);
		} else {
			return sha1($pass . SALT_KEY);
		}
	}
}
