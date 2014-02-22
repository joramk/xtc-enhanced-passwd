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

require_once 'PasswordHashClass.php';

class xtc_pbkdf2 extends PasswordHash implements xtc_encryption_algorithm {

	public static function createHash($password) {
		return parent::create_hash($password);
	}
	
	public static function getIterations($hash = null) {
		if (empty($hash)) {
			return PBKDF2_ITERATIONS;
		} else {
	        $params = explode(":", $hash);
	        return $params[HASH_ITERATION_INDEX];
		}
    }
}
