# -----------------------------------------------------------------------------------------
#  $Id: enhanced_passwd.sql,v 1.0 2014/02/16 17:21:16 joramk Exp $
#
#  XT-Commerce - community made shopping
#  http://www.xt-commerce.com 
#
#  Copyright (c) 2003 XT-Commerce
#  -----------------------------------------------------------------------------------------
#  Third Party Contributions:
#  Customers status v3.x (c) 2002-2003 Elari elari@free.fr
#  Download area : www.unlockgsm.com/dload-osc/
#  CVS : http://cvs.sourceforge.net/cgi-bin/viewcvs.cgi/elari/?sortby=date#dirlist
#  BMC 2003 for the CC CVV Module
#  qenta v1.0          Andreas Oberzier <xtc@netz-designer.de>
#  Encrpytion wrapper v1.0 (c) 2014 Tenretni Marketing GmbH
#  --------------------------------------------------------------
#  based on:
#  (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
#  (c) 2002-2003 osCommerce (oscommerce.sql,v 1.83); www.oscommerce.com
#  (c) 2003  nextcommerce (nextcommerce.sql,v 1.76 2003/08/25); www.nextcommerce.org
#
#  Released under the GNU General Public License
#
#  --------------------------------------------------------------
# NOTE: * Please make any modifications to this file by hand!
#       * DO NOT use a mysqldump created file for new changes!
#       * Please take note of the table structure, and use this
#         structure as a standard for future modifications!
#       * To see the 'diff'erence between MySQL databases, use
#         the mysqldiff perl script located in the extras
#         directory of the 'catalog' module.
#       * Comments should be like these, full line comments.
#         (don't use inline comments)
#  --------------------------------------------------------------

ALTER TABLE `customers` CHANGE `customers_password` `customers_password` VARCHAR( 255 ) NOT NULL;
