<?php
/*-----------------------------------------------------------------
* 	ID:						xtc_validate_password.inc.php
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
 * This function validates a plain text password against an encrypted password
 *
 * @param string $plain
 * @param string $encrypted
 * @return boolean
 */
function xtc_validate_password($plain, $encrypted) {
	if (xtc_not_null($plain) && xtc_not_null($encrypted)) {
		if (class_exists('xtc_encryption_wrapper')) {
			return xtc_encryption_wrapper::validatePassword($plain, $encrypted);
		} elseif ($encrypted == md5($plain)){
			return true;
		} elseif ($encrypted == sha1($plain . SALT_KEY)) {
			return true;
		}
	}
	return false;
}
