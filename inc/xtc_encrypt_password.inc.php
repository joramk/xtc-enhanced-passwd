<?php
/* -----------------------------------------------------------------------------------------
   $Id: xtc_encrypt_password.inc.php 899 2005-04-29 02:40:57Z hhgag $   

   XT-Commerce - community made shopping
   http://www.xt-commerce.com

   Copyright (c) 2003 XT-Commerce
   Copyright (c) 2014 Tenretni Marketing GmbH - http://code.google.com/p/xtc-enhanced-passwd/
   -----------------------------------------------------------------------------------------
   based on: 
   (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
   (c) 2002-2003 osCommerce(password_funcs.php,v 1.10 2003/02/11); www.oscommerce.com 
   (c) 2003	 nextcommerce (xtc_encrypt_password.inc.php,v 1.4 2003/08/13); www.nextcommerce.org

   Released under the GNU General Public License 
   ---------------------------------------------------------------------------------------*/

// Load enhanced password encryption class
require_once 'xtc_create_password.inc.php';

// This function makes a new password from a plaintext password. 
function xtc_encrypt_password($plain) {
	if (empty($plain)) {
		$plain = xtc_create_password(ENTRY_PASSWORD_MIN_LENGTH);
	}
	if (class_exists('xtc_encryption_wrapper')) {
		return xtc_encryption_wrapper::createHash($plain);
	} else {
		return md5($plain);
	}
}
