<?php
/*-----------------------------------------------------------------
* 	ID:						xtc_encrypt_password.inc.php
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
require_once 'xtc_create_password.inc.php';

/**
 * This function generates a new password hash for a given plaintext password.
 *
 * @param string $plain
 * @return string
 */
function xtc_encrypt_password($plain) {
	if (empty($plain)) {
		$plain = xtc_create_password(ENTRY_PASSWORD_MIN_LENGTH);
	}
	if (class_exists('xtc_encryption_wrapper')) {
		return xtc_encryption_wrapper::createHash($plain);
	} else {
		if (ACCOUNT_PASSWORD_SECURITY == 'false') {
			return md5($plain);
		} else {
			return sha1($plain . SALT_KEY);
		}
	}
}
