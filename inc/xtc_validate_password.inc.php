<?php
/* -----------------------------------------------------------------------------------------
   $Id: xtc_validate_password.inc.php 933158644dcb 2014-02-22 20:14:19Z joramk $   

   XT-Commerce - community made shopping
   http://www.xt-commerce.com

   Copyright (c) 2003 XT-Commerce
   Copyright (c) 2014 Tenretni Marketing GmbH - http://code.google.com/p/xtc-enhanced-passwd/
   -----------------------------------------------------------------------------------------
   based on: 
   (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
   (c) 2002-2003 osCommerce(password_funcs.php,v 1.10 2003/02/11); www.oscommerce.com 
   (c) 2003	 nextcommerce (xtc_validate_password.inc.php,v 1.4 2003/08/13); www.nextcommerce.org

   Released under the GNU General Public License 
   ---------------------------------------------------------------------------------------*/

// Load enhanced password encryption class
include_once 'xtc_encryption_wrapper.inc.php';

/**
 * This funstion validates a plain text password against an encrpyted password
 * 
 * @param type $plain
 * @param type $encrypted
 * @return boolean
 */
function xtc_validate_password($plain, $encrypted) {
	if (xtc_not_null($plain) && xtc_not_null($encrypted)) {
		if (class_exists('xtc_encryption_wrapper')) {
			return xtc_encryption_wrapper::validatePassword($plain, $encrypted);
		} elseif ($encrypted == md5($plain)){
			return true;
		}
	}
	return false;
}
