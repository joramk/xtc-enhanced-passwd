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

require_once 'class.pbkdf2.php';

class xtc_pbkdf2 extends Pbkdf2Abstract {

	static public $PBKDF2_ITERATIONS = 262144;

	public static function create_hash($password) {
		return parent::create_hash($password, self::$PBKDF2_ITERATIONS);
	}
	
	public static function getIterationCount($hash) {
        $params = explode(":", $hash);
        return $params[self::$HASH_ITERATION_INDEX];
    }
}
