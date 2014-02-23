<?php

/* -----------------------------------------------------------------
 * 	$Id: login.php 471 2013-07-09 18:32:20Z akausch $
 * 	Copyright (c) 2011-2021 commerce:SEO by Webdesign Erfurt
 * 	http://www.commerce-seo.de
 * ------------------------------------------------------------------
 * 	based on:
 * 	(c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
 * 	(c) 2002-2003 osCommerce - www.oscommerce.com
 * 	(c) 2003     nextcommerce - www.nextcommerce.org
 * 	(c) 2005     xt:Commerce - www.xt-commerce.com
 * 	Released under the GNU General Public License
 * --------------------------------------------------------------- */

include ('includes/application_top.php');
$smarty = new Smarty;
require (DIR_FS_CATALOG . 'templates/' . CURRENT_TEMPLATE . '/source/boxes.php');
require_once (DIR_FS_INC . 'xtc_validate_password.inc.php');
require_once (DIR_FS_INC . 'xtc_array_to_string.inc.php');
require_once (DIR_FS_INC . 'xtc_write_user_info.inc.php');
require_once (DIR_FS_INC . 'xtc_get_country_list.inc.php');
require_once (DIR_FS_INC . 'xtc_validate_email.inc.php');
require_once (DIR_FS_INC . 'xtc_encrypt_password.inc.php');

if (isset($_SESSION['customer_id']) && ($_SESSION['cart']->count_contents() > 0)) {
    xtc_redirect(xtc_href_link(FILENAME_SHOPPING_CART, '', 'SSL'));
} elseif (isset($_SESSION['customer_id'])) {
    xtc_redirect(xtc_href_link(FILENAME_ACCOUNT, '', 'SSL'));
}

// redirect the customer to a friendly cookie-must-be-enabled page if cookies are disabled (or the session has not started)
if ($session_started == false) {
    xtc_redirect(xtc_href_link(FILENAME_COOKIE_USAGE));
}

#Ohne Login Schutz
if (LOGIN_SAFE == 'false') {
    if (isset($_GET['action']) && ($_GET['action'] == 'process')) {
        $email_address = xtc_db_prepare_input($_POST['email_address']);
        $password = xtc_db_prepare_input($_POST['password']);

        // Check if email exists
        $check_customer_query = xtc_db_query("SELECT customers_id, customers_vat_id, customers_firstname,customers_lastname, customers_gender, customers_password, customers_email_address, customers_default_address_id FROM " . TABLE_CUSTOMERS . " WHERE customers_email_address = '" . xtc_db_input($email_address) . "' and account_type = '0'");
        if (!xtc_db_num_rows($check_customer_query)) {
            $_GET['login'] = 'fail';
            $info_message = TEXT_NO_EMAIL_ADDRESS_FOUND;
        } else {
            $check_customer = xtc_db_fetch_array($check_customer_query);
            // Check that password is good
            if (!xtc_validate_password($password, $check_customer['customers_password'])) {
                $_GET['login'] = 'fail';
                $info_message = TEXT_LOGIN_ERROR;
            } else {
				if (class_exists('xtc_encryption_wrapper') &&
						xtc_encryption_wrapper::needsAlgorithmUpdate($check_customer['customers_password'])) {
					xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET customers_password = '".
							xtc_encryption_wrapper::createHash($password) ."' WHERE customers_id='". $check_customer['customers_id'] ."'");
				}
                if (SESSION_RECREATE == 'true') {
                    xtc_session_recreate();
                }
                $check_country_query = xtc_db_query("SELECT entry_country_id, entry_zone_id FROM " . TABLE_ADDRESS_BOOK . " WHERE customers_id = '" . (int) $check_customer['customers_id'] . "' and address_book_id = '" . $check_customer['customers_default_address_id'] . "'");
                $check_country = xtc_db_fetch_array($check_country_query);
                $_SESSION['customer_gender'] = $check_customer['customers_gender'];
                $_SESSION['customer_first_name'] = $check_customer['customers_firstname'];
                $_SESSION['customer_last_name'] = $check_customer['customers_lastname'];
                $_SESSION['customer_id'] = $check_customer['customers_id'];
                $_SESSION['customer_vat_id'] = $check_customer['customers_vat_id'];
                $_SESSION['customer_default_address_id'] = $check_customer['customers_default_address_id'];
                $_SESSION['customer_country_id'] = $check_country['entry_country_id'];
                $_SESSION['customer_zone_id'] = $check_country['entry_zone_id'];
                $date_now = date('Ymd');
                xtc_db_query("UPDATE " . TABLE_CUSTOMERS_INFO . " SET customers_info_date_of_last_logon = now(), customers_info_number_of_logons = customers_info_number_of_logons+1 WHERE customers_info_id = '" . (int) $_SESSION['customer_id'] . "'");
                xtc_write_user_info((int) $_SESSION['customer_id']);
                // restore cart contents
                $_SESSION['cart']->restore_contents();
                $_SESSION['wishList']->restore_contents();


                if ($_SESSION['customer_id'] == '1' && ADMIN_AFTER_LOGIN == 'true') {
                    if ($browser->isMobile()) {
                        xtc_redirect(xtc_href_link('admin/mobile.php', '', 'SSL'));
                    } else {
                        xtc_redirect(xtc_href_link('admin/start.php', '', 'SSL'));
                    }
                } elseif ($_SESSION['cart']->count_contents() > 0) {
                    xtc_redirect(xtc_href_link(FILENAME_SHOPPING_CART, '', 'SSL'));
                } else {
                    xtc_redirect($_SERVER['HTTP_REFERER']);
                }
            }
        }
    }
} else {
    if (isset($_GET['action']) && ($_GET['action'] == 'process')) {
        $email_address = xtc_db_prepare_input($_POST['email_address']);
        $password = xtc_db_prepare_input($_POST['password']);
        $check_customer_query = xtc_db_query("SELECT 
												 customers_id, 
												 customers_vat_id, 
												 customers_firstname,
												 customers_lastname, 
												 customers_gender, 
												 customers_password, 
												 customers_email_address, 
												 customers_default_address_id, 
												 login_tries,
												 login_time
											   FROM 
												customers 
											   WHERE 
												 customers_email_address = '" . xtc_db_input($email_address) . "' 
												 and account_type = '0'");
        if (!xtc_db_num_rows($check_customer_query)) {
            $_GET['login'] = 'fail';
            $info_message = TEXT_NO_EMAIL_ADDRESS_FOUND;
        } else {
            $check_customer = xtc_db_fetch_array($check_customer_query);
            $login_success = true;
            $blocktime = LOGIN_TIME;
            $time = time();
            $logintime = strtotime($check_customer['login_time']);
            $difference = $time - $logintime;
            $login_tries = $check_customer['login_tries'];
            if ($login_tries >= LOGIN_NUM && $difference < $blocktime && ANTISPAM_PASSWORD == 'true') {
                //Antispam beginn
                $antispam_query = xtc_db_fetch_array(xtDBquery("SELECT id, question FROM " . TABLE_CSEO_ANTISPAM . " WHERE language_id = '" . (int) $_SESSION['languages_id'] . "' ORDER BY rand() LIMIT 1"));
                $smarty->assign('ANTISPAMCODEID', xtc_draw_hidden_field('antispamid', $antispam_query['id']));
                $smarty->assign('ANTISPAMCODEQUESTION', $antispam_query['question']);
                $smarty->assign('INPUT_ANTISPAMCODE', xtc_draw_input_field('codeanwser', '', 'size="6" maxlength="6"', 'text', false));
                $smarty->assign('ANTISPAMCODEACTIVE', ANTISPAM_PASSWORD);
                //Antispam end
            }

            if (!empty($_POST["codeanwser"])) {
                if (!mb_strtolower($antispam_query['answer'], 'UTF-8') == mb_strtolower($_POST["codeanwser"], 'UTF-8')) {
                    xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET login_tries = login_tries+1, login_time = now() WHERE customers_email_address = '" . xtc_db_input($email_address) . "'");
                    if (!xtc_validate_password($password, $check_customer['customers_password']) || $check_customer['customers_email_address'] != $email_address) {
                        $info_message = TEXT_LOGIN_ERROR;
                        $login_success = false;
                    }
                } else {
                    $info_message = TEXT_WRONG_CODE;
                    $login_success = false;
                }
            } elseif (!xtc_validate_password($password, $check_customer['customers_password'])) {
                $_GET['login'] = 'fail';
                $info_message = TEXT_LOGIN_ERROR;
                xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET login_tries = login_tries+1, login_time = now() WHERE customers_email_address = '" . xtc_db_input($email_address) . "'");
                $login_success = false;
            }

            if ($login_success) {
                xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET login_tries = 0, login_time = now() WHERE customers_email_address = '" . xtc_db_input($email_address) . "'");
                if (SESSION_RECREATE == 'true') {
                    xtc_session_recreate();
                }
                $check_country_query = xtc_db_query("SELECT entry_country_id, entry_zone_id FROM " . TABLE_ADDRESS_BOOK . " WHERE customers_id = '" . (int) $check_customer['customers_id'] . "' and address_book_id = '" . $check_customer['customers_default_address_id'] . "'");
                $check_country = xtc_db_fetch_array($check_country_query);
                $_SESSION['customer_gender'] = $check_customer['customers_gender'];
                $_SESSION['customer_first_name'] = $check_customer['customers_firstname'];
                $_SESSION['customer_last_name'] = $check_customer['customers_lastname'];
                $_SESSION['customer_id'] = $check_customer['customers_id'];
                $_SESSION['customer_vat_id'] = $check_customer['customers_vat_id'];
                $_SESSION['customer_default_address_id'] = $check_customer['customers_default_address_id'];
                $_SESSION['customer_country_id'] = $check_country['entry_country_id'];
                $_SESSION['customer_zone_id'] = $check_country['entry_zone_id'];
                $date_now = date('Ymd');
                xtc_db_query("UPDATE " . TABLE_CUSTOMERS_INFO . " SET customers_info_date_of_last_logon = now(), customers_info_number_of_logons = customers_info_number_of_logons+1 WHERE customers_info_id = '" . (int) $_SESSION['customer_id'] . "'");
                xtc_write_user_info((int) $_SESSION['customer_id']);
                $_SESSION['cart']->restore_contents();
                // $_SESSION['wishList']->restore_contents();
                if (preg_match("/shopping_cart/i", $_SERVER['HTTP_REFERER']))
                    xtc_redirect(FILENAME_SHOPPING_CART);
                else
                    xtc_redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }
}

$breadcrumb->add(NAVBAR_TITLE_LOGIN, xtc_href_link(FILENAME_LOGIN, '', 'SSL'));
require_once (DIR_WS_INCLUDES . 'header.php');
if ($_GET['info_message']) {
    $info_message = $_GET['info_message'];
}


$smarty->assign('info_message', $info_message);
$smarty->assign('account_option', ACCOUNT_OPTIONS);
$smarty->assign('BUTTON_NEW_ACCOUNT', '<a href="' . xtc_href_link(FILENAME_CREATE_ACCOUNT, '', 'SSL') . '">' . xtc_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>');
$smarty->assign('BUTTON_LOGIN', xtc_image_submit('button_login.gif', IMAGE_BUTTON_LOGIN, 'id="login"'));
$smarty->assign('BUTTON_GUEST', '<a href="' . xtc_href_link(FILENAME_CREATE_GUEST_ACCOUNT, '', 'SSL') . '">' . xtc_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE) . '</a>');
$smarty->assign('FORM_LOGIN_ACTION', xtc_draw_form('login', xtc_href_link(FILENAME_LOGIN, 'action=process', 'SSL')));
$smarty->assign('INPUT_MAIL', xtc_draw_input_field('email_address', '', 'id="login_email_address"', 'email'));
$smarty->assign('INPUT_LOGIN_PASSWORD', xtc_draw_password_field('password', '', 'id="login_password"'));
$smarty->assign('LINK_LOST_PASSWORD', xtc_href_link(FILENAME_PASSWORD_DOUBLE_OPT, '', 'SSL'));
$smarty->assign('FORM_LOGIN_END', '</form>');


//Create Account

$process = false;
if (isset($_POST['action']) && ($_POST['action'] == 'process')) {
    $process = true;

    if (ACCOUNT_GENDER == 'true')
        $gender = xtc_db_prepare_input($_POST['gender']);
    $firstname = xtc_db_prepare_input($_POST['firstname']);
    $lastname = xtc_db_prepare_input($_POST['lastname']);
    if (ACCOUNT_DOB == 'true')
        $dob = xtc_db_prepare_input($_POST['dob']);
    $email_address = xtc_db_prepare_input($_POST['email_address']);
    if (ACCOUNT_COMPANY == 'true')
        $company = xtc_db_prepare_input($_POST['company']);
    if (ACCOUNT_COMPANY_VAT_CHECK == 'true')
        $vat = xtc_db_prepare_input($_POST['vat']);
    $street_address = xtc_db_prepare_input($_POST['street_address']);
    $street_address_num = xtc_db_prepare_input($_POST['street_address_num']);
    if (ACCOUNT_SUBURB == 'true')
        $suburb = xtc_db_prepare_input($_POST['suburb']);
    $postcode = xtc_db_prepare_input($_POST['postcode']);
    $city = xtc_db_prepare_input($_POST['city']);
    $zone_id = xtc_db_prepare_input($_POST['zone_id']);
    if (ACCOUNT_STATE == 'true')
        $state = xtc_db_prepare_input($_POST['state']);
    $country = xtc_db_prepare_input($_POST['country']);
    $telephone = xtc_db_prepare_input($_POST['telephone']);
    $fax = xtc_db_prepare_input($_POST['fax']);
    $newsletter = xtc_db_prepare_input($_POST['newsletter']);
    $password = xtc_db_prepare_input($_POST['password']);
    $confirmation = xtc_db_prepare_input($_POST['confirmation']);
    if (TRUSTED_SHOP_CREATE_ACCOUNT_DS == 'true') {
        $datensg = xtc_db_prepare_input($_POST['datensg']);
    }

    $error = false;

    if (ACCOUNT_GENDER == 'true') {
        if (($gender != 'm') && ($gender != 'f')) {
            $error = true;
            $messageStack->add('create_account', ENTRY_GENDER_ERROR);
        }
    }

    if (strlen($firstname) < ENTRY_FIRST_NAME_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_FIRST_NAME_ERROR);
    }

    if (strlen($lastname) < ENTRY_LAST_NAME_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_LAST_NAME_ERROR);
    }

    require_once(DIR_WS_CLASSES . 'class.vat_validation.php');
    $vatID = new vat_validation($vat, '', '', $country);

    $customers_status = $vatID->vat_info['status'];
    $customers_vat_id_status = $vatID->vat_info['vat_id_status'];
    $error = $vatID->vat_info['error'];

    if ($error == 1) {
        $messageStack->add('create_account', ENTRY_VAT_ERROR);
        $error = true;
    }

    if (strlen($email_address) < ENTRY_EMAIL_ADDRESS_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR);
    } elseif (xtc_validate_email($email_address) == false) {
        $error = true;
        $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_CHECK_ERROR);
    } else {
        $check_email_query = xtc_db_query("select count(*) as total from " . TABLE_CUSTOMERS . " where customers_email_address = '" . xtc_db_input($email_address) . "'");
        $check_email = xtc_db_fetch_array($check_email_query);
        if ($check_email['total'] > 0) {
            $error = true;
            $messageStack->add('create_account', ENTRY_EMAIL_ADDRESS_ERROR_EXISTS);
        }
    }

    if (strlen($street_address) < ENTRY_STREET_ADDRESS_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_STREET_ADDRESS_ERROR);
    }

    if (strlen($postcode) < ENTRY_POSTCODE_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_POST_CODE_ERROR);
    }

    if (strlen($city) < ENTRY_CITY_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_CITY_ERROR);
    }

    if (is_numeric($country) == false) {
        $error = true;
        $messageStack->add('create_account', ENTRY_COUNTRY_ERROR);
    }

    if (ACCOUNT_STATE == 'true') {
        $zone_id = 0;
        $check_query = xtc_db_query("select count(*) as total from " . TABLE_ZONES . " where zone_country_id = '" . (int) $country . "'");
        $check = xtc_db_fetch_array($check_query);
        $entry_state_has_zones = ($check['total'] > 0);
        if ($entry_state_has_zones == true) {
            $zone_query = xtc_db_query("select zone_id,zone_name from " . TABLE_ZONES . " where zone_country_id = '" . (int) $country . "' and zone_id = '" . (int) $state . "' ");

            if (xtc_db_num_rows($zone_query) >= 1) {
                $zone = xtc_db_fetch_array($zone_query);
                $zone_id = $zone['zone_id'];
            } else {
                $error = true;
                $messageStack->add('create_account', ENTRY_STATE_ERROR_SELECT);
            }
        } else {
            if (strlen($state) < ENTRY_STATE_MIN_LENGTH) {
                $error = true;
                $messageStack->add('create_account', ENTRY_STATE_ERROR);
            }
        }
    }

    if (strlen($telephone) < ENTRY_TELEPHONE_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_TELEPHONE_NUMBER_ERROR);
    }

    if (strlen($password) < ENTRY_PASSWORD_MIN_LENGTH) {
        $error = true;
        $messageStack->add('create_account', ENTRY_PASSWORD_ERROR);
    } elseif ($password != $confirmation) {
        $error = true;
        $messageStack->add('create_account', ENTRY_PASSWORD_ERROR_NOT_MATCHING);
    }
    if (TRUSTED_SHOP_CREATE_ACCOUNT_DS == 'true' && $mobile_template == 'False') {
        if (!isset($datensg) || empty($datensg)) {
            $error = true;
            $messageStack->add('create_account', ERROR_DATENSG_NOT_ACCEPTED);
        }
    }

    if (ACCOUNT_DOB == 'true') {
        if (ENTRY_DOB_MIN_LENGTH > 0 && $dob != '') {
            if (checkdate(substr(xtc_date_raw($dob), 4, 2), substr(xtc_date_raw($dob), 6, 2), substr(xtc_date_raw($dob), 0, 4)) == false) {
                $error = true;
                $messageStack->add('create_account', ENTRY_DATE_OF_BIRTH_ERROR);
            }
        } elseif (ENTRY_DOB_MIN_LENGTH > 0 && $dob == '') {
            $error = true;
            $messageStack->add('create_account', ENTRY_DATE_OF_BIRTH_ERROR);
        }
    }

    if ($customers_status == 0 || !$customers_status)
        $customers_status = DEFAULT_CUSTOMERS_STATUS_ID;

    if ($error == false) {
        $sql_data_array = array('customers_vat_id' => $vat,
            'customers_vat_id_status' => $customers_vat_id_status,
            'customers_status' => $customers_status,
            'customers_firstname' => $firstname,
            'customers_lastname' => $lastname,
            'customers_email_address' => $email_address,
            'customers_telephone' => $telephone,
            'customers_fax' => $fax,
            'customers_password' => xtc_encrypt_password($password),
            'customers_newsletter' => $newsletter,
            'customers_date_added' => 'now()',
            'customers_last_modified' => 'now()',
            'datensg' => 'now()');

        if (ACCOUNT_GENDER == 'true')
            $sql_data_array['customers_gender'] = $gender;
        if (ACCOUNT_DOB == 'true')
            $sql_data_array['customers_dob'] = xtc_date_raw($dob);

        function new_customer_id($space = '-') {
            $new_cid = '';
            $day = date("d");
            $mon = date("m");
            $year = date("y");
            $cid_query = xtc_db_query("SELECT customers_id FROM " . TABLE_CUSTOMERS . " ORDER BY customers_id DESC LIMIT 1");
            $last_cid = xtc_db_fetch_array($cid_query);
            $new_cid = $day . $mon . $year . $space . ($last_cid['customers_id'] + 1000);

            return $new_cid;
        }

        $sql_data_array['customers_cid'] = new_customer_id();

        xtc_db_perform(TABLE_CUSTOMERS, $sql_data_array);

        $_SESSION['customer_id'] = xtc_db_insert_id();
        $user_id = xtc_db_insert_id();
        xtc_write_user_info($user_id);
        $sql_data_array = array('customers_id' => $_SESSION['customer_id'],
            'entry_firstname' => $firstname,
            'entry_lastname' => $lastname,
            'entry_street_address' => $street_address . ' ' . $street_address_num,
            'entry_postcode' => $postcode,
            'entry_city' => $city,
            'entry_country_id' => $country,
            'address_date_added' => 'now()',
            'address_last_modified' => 'now()');

        if (ACCOUNT_GENDER == 'true')
            $sql_data_array['entry_gender'] = $gender;
        if (ACCOUNT_COMPANY == 'true')
            $sql_data_array['entry_company'] = $company;
        if (ACCOUNT_SUBURB == 'true')
            $sql_data_array['entry_suburb'] = $suburb;
        if (ACCOUNT_STATE == 'true') {
            if ($zone_id > 0) {
                $sql_data_array['entry_zone_id'] = $zone_id;
                $sql_data_array['entry_state'] = '';
            } else {
                $sql_data_array['entry_zone_id'] = '0';
                $sql_data_array['entry_state'] = $zone['zone_name'];
            }
        }

        xtc_db_perform(TABLE_ADDRESS_BOOK, $sql_data_array);

        $address_id = xtc_db_insert_id();

        xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET customers_default_address_id = '" . $address_id . "' WHERE customers_id = '" . (int) $_SESSION['customer_id'] . "'");
        xtc_db_query("INSERT INTO " . TABLE_CUSTOMERS_INFO . " (customers_info_id, customers_info_number_of_logons, customers_info_date_account_created) VALUES ('" . (int) $_SESSION['customer_id'] . "', '0', now())");

        if (SESSION_RECREATE == 'true')
            xtc_session_recreate();

        if ($newsletter == '1') {

            require_once (DIR_FS_INC . 'xtc_random_charcode.inc.php');
            $vlcode = xtc_random_charcode(32);

            $sql_newletter_array = array('customers_email_address' => $email_address,
                'customers_id' => (int) $_SESSION['customer_id'],
                'customers_status' => $customers_status,
                'customers_firstname' => $firstname,
                'customers_lastname' => $lastname,
                'mail_status' => '0',
                'mail_key' => $vlcode,
                'date_added' => 'now()');

            xtc_db_perform(TABLE_NEWSLETTER_RECIPIENTS, $sql_newletter_array);

            require_once (DIR_FS_INC . 'xtc_random_charcode.inc.php');
            $vlcode = xtc_random_charcode(32);
            $link = xtc_href_link(FILENAME_NEWSLETTER, 'action=activate&email=' . $email_address . '&key=' . $vlcode, 'NONSSL');

            $smarty->assign('EMAIL', xtc_db_input($_POST['email']));
            $smarty->assign('LINK', $link);

            $smarty->assign('language', $_SESSION['language']);
            $smarty->assign('tpl_path', 'templates/' . CURRENT_TEMPLATE . '/');
            $smarty->assign('logo_path', HTTP_SERVER . DIR_WS_CATALOG . 'templates/' . CURRENT_TEMPLATE . '/img/');

            $smarty->caching = false;
            require_once (DIR_FS_INC . 'cseo_get_mail_body.inc.php');
            $html_mail = $smarty->fetch('html:newsletter_aktivierung');
            $html_mail .= $signatur_html;
            $txt_mail = $smarty->fetch('txt:newsletter_aktivierung');
            $txt_mail .= $signatur_text;
            require_once (DIR_FS_INC . 'cseo_get_mail_data.inc.php');
            $mail_data = cseo_get_mail_data('newsletter_aktivierung');

            $newsletter_subject = str_replace('{$shop_besitzer}', STORE_OWNER, $mail_data['EMAIL_SUBJECT']);
            $newsletter_subject = str_replace('{$shop_name}', STORE_NAME, $newsletter_subject);

            $newsletter_from = str_replace('{$shop_name}', STORE_NAME, $mail_data['EMAIL_ADDRESS_NAME']);

            if (SEND_EMAILS == true) {
                xtc_php_mail($mail_data['EMAIL_ADDRESS'], $newsletter_from, xtc_db_input($email_address), $firstname . ' ' . $lastname, $mail_data['EMAIL_FORWARD'], $mail_data['EMAIL_REPLAY_ADDRESS'], $mail_data['EMAIL_REPLAY_ADDRESS_NAME'], '', '', $newsletter_subject, $html_mail, $txt_mail);
            }
        } else {
            $newsletter = '0';
        }

        $_SESSION['customer_first_name'] = $firstname;
        $_SESSION['customer_last_name'] = $lastname;
        $_SESSION['customer_default_address_id'] = $address_id;
        $_SESSION['customer_country_id'] = $country;
        $_SESSION['customer_zone_id'] = $zone_id;
        $_SESSION['customer_vat_id'] = $vat;

        $_SESSION['cart']->restore_contents();

        $smarty->assign('language', $_SESSION['language']);
        if (ACCOUNT_GENDER == 'true')
            $smarty->assign('GENDER', $gender);

        require_once (DIR_FS_INC . 'cseo_get_mail_data.inc.php');
        $mail_data = cseo_get_mail_data('create_account');
        $smarty->assign('MAIL_REPLY_ADDRESS', $mail_data['EMAIL_ADDRESS']);
        $smarty->assign('VNAME', $_SESSION['customer_first_name']);
        $smarty->assign('NNAME', $_SESSION['customer_last_name']);
        $smarty->assign('logo_path', HTTP_SERVER . DIR_WS_CATALOG . 'templates/' . CURRENT_TEMPLATE . '/img/');

        $smarty->assign('content', $module_content);
        $smarty->caching = false;
        if (TRUSTED_SHOP_PASSWORD_EMAIL == 'true') {
            $smarty->assign('USERNAME4MAIL', $email_address);
            $smarty->assign('PASSWORT4MAIL', $password);
        }

        if (isset($_SESSION['tracking']['refID'])) {
            $campaign_check_query_raw = "SELECT * FROM " . TABLE_CAMPAIGNS . " WHERE campaigns_refID = '" . $_SESSION[tracking][refID] . "'";
            $campaign_check_query = xtc_db_query($campaign_check_query_raw);
            if (xtc_db_num_rows($campaign_check_query) > 0) {
                $campaign = xtc_db_fetch_array($campaign_check_query);
                $refID = $campaign['campaigns_id'];
            }
            else
                $refID = 0;

            xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET refferers_id = '" . $refID . "' WHERE customers_id = '" . (int) $_SESSION['customer_id'] . "'");

            $leads = $campaign['campaigns_leads'] + 1;
            xtc_db_query("UPDATE " . TABLE_CAMPAIGNS . " SET campaigns_leads = '" . $leads . "' WHERE campaigns_id = '" . $refID . "'");
        }

        if (ACTIVATE_GIFT_SYSTEM == 'true') {
            if (NEW_SIGNUP_GIFT_VOUCHER_AMOUNT > 0) {
                $coupon_code = create_coupon_code();
                $insert_query = xtc_db_query("insert into " . TABLE_COUPONS . " (coupon_code, coupon_type, coupon_amount, date_created) values ('" . $coupon_code . "', 'G', '" . NEW_SIGNUP_GIFT_VOUCHER_AMOUNT . "', now())");
                $insert_id = xtc_db_insert_id($insert_query);
                $insert_query = xtc_db_query("insert into " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent) values ('" . $insert_id . "', '0', 'Admin', '" . $email_address . "', now() )");

                $smarty->assign('SEND_GIFT', 'true');
                $smarty->assign('GIFT_AMMOUNT', $xtPrice->xtcFormat(NEW_SIGNUP_GIFT_VOUCHER_AMOUNT, true));
                $smarty->assign('GIFT_CODE', $coupon_code);
                $smarty->assign('GIFT_LINK', xtc_href_link(FILENAME_GV_REDEEM, 'gv_no=' . $coupon_code, 'NONSSL', false));
            }
            if (NEW_SIGNUP_DISCOUNT_COUPON != '') {
                $coupon_code = NEW_SIGNUP_DISCOUNT_COUPON;
                $coupon_query = xtc_db_query("select * from " . TABLE_COUPONS . " where coupon_code = '" . $coupon_code . "'");
                $coupon = xtc_db_fetch_array($coupon_query);
                $coupon_id = $coupon['coupon_id'];
                $coupon_desc_query = xtc_db_query("select * from " . TABLE_COUPONS_DESCRIPTION . " where coupon_id = '" . $coupon_id . "' and language_id = '" . (int) $_SESSION['languages_id'] . "'");
                $coupon_desc = xtc_db_fetch_array($coupon_desc_query);
                $insert_query = xtc_db_query("insert into " . TABLE_COUPON_EMAIL_TRACK . " (coupon_id, customer_id_sent, sent_firstname, emailed_to, date_sent) values ('" . $coupon_id . "', '0', 'Admin', '" . $email_address . "', now() )");

                $smarty->assign('SEND_COUPON', 'true');
                $smarty->assign('COUPON_DESC', $coupon_desc['coupon_description']);
                $smarty->assign('COUPON_CODE', $coupon['coupon_code']);
            }
        }
        require_once (DIR_FS_INC . 'cseo_get_mail_body.inc.php');
        $smarty->caching = false;
        $html_mail = $smarty->fetch('html:create_account');
        $html_mail .= $signatur_html;
        $smarty->caching = false;
        $txt_mail = $smarty->fetch('txt:create_account');
        $txt_mail .= $signatur_text;
        require_once (DIR_FS_INC . 'cseo_get_mail_data.inc.php');
        $mail_data = cseo_get_mail_data('create_account');

        xtc_php_mail($mail_data['EMAIL_ADDRESS'], $mail_data['EMAIL_ADDRESS_NAME'], $email_address, $name, $mail_data['EMAIL_FORWARD'], $mail_data['EMAIL_REPLAY_ADDRESS'], $mail_data['EMAIL_REPLAY_ADDRESS_NAME'], '', '', $mail_data['EMAIL_SUBJECT'], $html_mail, $txt_mail);

        if (!isset($mail_error)) {
            if ($_SESSION['cart']->count_contents() > 0)
                xtc_redirect(xtc_href_link(FILENAME_SHOPPING_CART, '', 'SSL'));
            else
                xtc_redirect(xtc_href_link(FILENAME_DEFAULT, '', 'SSL'));
        }
        else
            echo $mail_error;
    }
}


if ($messageStack->size('create_account') > 0)
    $smarty->assign('error', $messageStack->output('create_account'));

$smarty->assign('FORM_ACTION', xtc_draw_form('create_account', xtc_href_link(FILENAME_LOGIN, '', 'SSL'), 'post', 'onsubmit="return check_form(this);" autocomplete="off"') . xtc_draw_hidden_field('action', 'process'));

if (ACCOUNT_GENDER == 'true') {
    $smarty->assign('gender', '1');
    $smarty->assign('INPUT_MALE', xtc_draw_radio_field(array('name' => 'gender', 'suffix' => MALE), 'm', '', 'id="gender-1"'));
    $smarty->assign('INPUT_FEMALE', xtc_draw_radio_field(array('name' => 'gender', 'suffix' => FEMALE, 'text' => (xtc_not_null(ENTRY_GENDER_TEXT) ? '<span class="inputRequirement">' . ENTRY_GENDER_TEXT . '</span>' : '')), 'f', '', 'id="gender-1"'));
}
else
    $smarty->assign('gender', '0');

$smarty->assign('INPUT_FIRSTNAME', xtc_draw_input_fieldNote(array('name' => 'firstname', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_FIRST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_FIRST_NAME_TEXT . '</span>' : '')), '', 'size="29" class="create_account_firstname" required id="create_firstname"'));
$smarty->assign('INPUT_LASTNAME', xtc_draw_input_fieldNote(array('name' => 'lastname', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_LAST_NAME_TEXT) ? '<span class="inputRequirement">' . ENTRY_LAST_NAME_TEXT . '</span>' : '')), '', 'size="29" class="create_account_lastname" required id="create_lastname"'));

if (ACCOUNT_DOB == 'true') {
    $smarty->assign('birthdate', '1');
    if (ENTRY_DOB_MIN_LENGTH > 0)
        $smarty->assign('INPUT_DOB', xtc_draw_input_fieldNote(array('name' => 'dob', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_DATE_OF_BIRTH_TEXT) ? '<span class="inputRequirement">' . ENTRY_DATE_OF_BIRTH_TEXT . '</span>' : '')), '', 'size="29" required class="create_account_dob" id="create_dob"', 'text'));
    else
        $smarty->assign('INPUT_DOB', xtc_draw_input_fieldNote(array('name' => 'dob', 'text' => ''), '', 'size="29" required class="create_account_dob" id="create_dob"', 'text'));
} else {
    $smarty->assign('birthdate', '0');
}

$smarty->assign('INPUT_EMAIL', xtc_draw_input_fieldNote(array('name' => 'email_address', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_EMAIL_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_EMAIL_ADDRESS_TEXT . '</span>' : '')), '', 'size="29" required class="create_account_email" id="create_email"', 'email'));

if (ACCOUNT_COMPANY == 'true') {
    $smarty->assign('company', '1');
    $smarty->assign('INPUT_COMPANY', xtc_draw_input_fieldNote(array('name' => 'company', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_COMPANY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COMPANY_TEXT . '</span>' : '')), '', 'size="29" class="create_account_company" id="create_company"'));
} else {
    $smarty->assign('company', '0');
}

if (ACCOUNT_COMPANY_VAT_CHECK == 'true') {
    $smarty->assign('vat', '1');
    $smarty->assign('INPUT_VAT', xtc_draw_input_fieldNote(array('name' => 'vat', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_VAT_TEXT) ? '<span class="inputRequirement">' . ENTRY_VAT_TEXT . '</span>' : '')), '', 'size="29" class="create_account_vat" id="create_vat"'));
} else {
    $smarty->assign('vat', '0');
}

$smarty->assign('INPUT_STREET', xtc_draw_input_fieldNote(array('name' => 'street_address', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_STREET_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_STREET_ADDRESS_TEXT . '</span>' : '')), '', 'size="21" required class="create_account_street" id="create_street_address"'));
$smarty->assign('INPUT_STREET_NUM', xtc_draw_input_fieldNote(array('name' => 'street_address_num', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_STREET_ADDRESS_TEXT) ? '<span class="inputRequirement">' . ENTRY_STREET_ADDRESS_TEXT . '</span>' : '')), '', 'size="3" class="create_account_street_num"'));

if (ACCOUNT_SUBURB == 'true') {
    $smarty->assign('suburb', '1');
    $smarty->assign('INPUT_SUBURB', xtc_draw_input_fieldNote(array('name' => 'suburb', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_SUBURB_TEXT) ? '<span class="inputRequirement">' . ENTRY_SUBURB_TEXT . '</span>' : '')), '', 'size="29" class="create_account_suburb" id="create_suburb"'));
} else {
    $smarty->assign('suburb', '0');
}

$smarty->assign('INPUT_CODE', xtc_draw_input_fieldNote(array('name' => 'postcode', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_POST_CODE_TEXT) ? '<span class="inputRequirement">' . ENTRY_POST_CODE_TEXT . '</span>' : '')), '', 'size="5" required class="create_account_postcode" id="create_postcode"'));
$smarty->assign('INPUT_CITY', xtc_draw_input_fieldNote(array('name' => 'city', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_CITY_TEXT) ? '<span class="inputRequirement">' . ENTRY_CITY_TEXT . '</span>' : '')), '', 'size="19" required class="create_account_city" id="create_city"'));

if (ACCOUNT_STATE == 'true') {
    $smarty->assign('state', '1');

    $zones_array = array();
    $zones_query = xtc_db_query("SELECT zone_id, zone_name FROM " . TABLE_ZONES . " WHERE zone_country_id = '" . (isset($_POST['country']) ? (int) $country : STORE_COUNTRY) . "' ORDER BY zone_name");
    while ($zones_values = xtc_db_fetch_array($zones_query)) {
        $zones_array[] = array('id' => $zones_values['zone_id'], 'text' => $zones_values['zone_name']);
    }
    $state_input = xtc_draw_pull_down_menuNote(array('name' => 'state', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_STATE_TEXT) ? '<span class="inputRequirement">' . ENTRY_STATE_TEXT . '</span>' : '')), $zones_array, '', ' class="create_account_state" id="create_state"');

    $smarty->assign('INPUT_STATE', $state_input);
} else {
    $smarty->assign('state', '0');
}

if (isset($_POST['country'])) {
    $selected = $_POST['country'];
} elseif (isset($_SESSION['country'])) {
    $selected = $_SESSION['country'];
} else {
    $selected = STORE_COUNTRY;
}
$counrty_count_query = xtc_db_query("SELECT countries_id, status FROM " . TABLE_COUNTRIES . " WHERE status = '1'");
$counrty_count = xtc_db_num_rows($counrty_count_query);
if ($counrty_count > 1) {
    $smarty->assign('SELECT_COUNTRY_JS', '
	<script type="text/javascript">
	<!--
		jQuery(function(){
			jQuery("select#country").change(function(){
				var value = jQuery("select#country").val();
				jQuery.ajax({
				  type: "GET",
				  url: "getCountry.php",
				  data: "land=" + value,
				  cache: false,
				  success: function(html){
					jQuery("#state").html(html);
				  },
				  beforeSend: function(){
					jQuery("#state").html("<p style=\'width:198px\' align=\'center\'><img src=\'images/wait.gif\' alt=\'\' /></p>");
				  }
				});
			});
		});
	//-->
	</script>');
    $smarty->assign('SELECT_COUNTRY', xtc_get_country_list(array('name' => 'country', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_COUNTRY_TEXT) ? '<span class="inputRequirement">' . ENTRY_COUNTRY_TEXT . '</span>' : '')), $selected, 'id="country" class="create_account_country"'));
} else {
    $smarty->assign('SELECT_COUNTRY_ENABLE', 'false');
    $smarty->assign('SELECT_COUNTRY', xtc_draw_hidden_field('country', $selected));
}

$smarty->assign('INPUT_TEL', xtc_draw_input_fieldNote(array('name' => 'telephone', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_TELEPHONE_NUMBER_TEXT) ? '<span class="inputRequirement">' . ENTRY_TELEPHONE_NUMBER_TEXT . '</span>' : '')), '', 'size="29" required class="telephone" id="create_telephone"'));
$smarty->assign('INPUT_FAX', xtc_draw_input_fieldNote(array('name' => 'fax', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_FAX_NUMBER_TEXT) ? '<span>' . ENTRY_FAX_NUMBER_TEXT . '</span>' : '')), '', 'size="29" id="create_fax"'));
$smarty->assign('INPUT_PASSWORD', xtc_draw_password_fieldNote(array('name' => 'password', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_PASSWORD_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_TEXT . '</span>' : '')), '', 'size="29" id="create_password" onkeyup="passwordStrength(this.value)"'));
$smarty->assign('INPUT_CONFIRMATION', xtc_draw_password_fieldNote(array('name' => 'confirmation', 'text' => '&nbsp;' . (xtc_not_null(ENTRY_PASSWORD_CONFIRMATION_TEXT) ? '<span class="inputRequirement">' . ENTRY_PASSWORD_CONFIRMATION_TEXT . '</span>' : '')), '', 'size="29" class="password" id="create_confirmation"'));
$smarty->assign('INPUT_NEWSLETTER', xtc_draw_checkbox_field('newsletter', '1', false, 'id="create_newsletter"'));

if (TRUSTED_SHOP_CREATE_ACCOUNT_DS == 'true') {
    $shop_content_query = xtc_db_query("SELECT content_text, content_file FROM " . TABLE_CONTENT_MANAGER . " WHERE content_group = '2' AND languages_id='" . (int) $_SESSION['languages_id'] . "'");
    $shop_content_data = xtc_db_fetch_array($shop_content_query);
    if ($shop_content_data['content_file'] != '') {
        if ($shop_content_data['content_file'] == 'janolaw_datenschutz.php') {
            include(DIR_FS_INC . 'janolaw.inc.php');
            $datensg = JanolawContent('datenschutzerklaerung', 'txt');
        }
        else
            $datensg = '<iframe src="' . DIR_WS_CATALOG . 'media/content/' . $shop_content_data['content_file'] . '" width="100%" height="300"></iframe>';
    }
    else
        $datensg = '<div class="agbframe">' . $shop_content_data['content_text'] . '</textarea>';

    $smarty->assign('DSG', $datensg);
    $smarty->assign('BUTTON_PRINT', '<a style="cursor:pointer" onclick="javascript:window.open(\'' . xtc_href_link(FILENAME_PRINT_CONTENT, 'coID=2') . '\', \'popup\', \'toolbar=0, width=640, height=600\')">' . PRINT_CONTENT . '</a>');
    $smarty->assign('DATENSG_CHECKBOX', '<input id="create_dsg" type="checkbox" value="datensg" name="datensg" />');
} else {
    $smarty->assign('TRUSTED_DSG', 'false');
}

$smarty->assign('FORM_END', '</form>');
$smarty->assign('BUTTON_SUBMIT', xtc_image_submit('button_send.gif', IMAGE_BUTTON_CONTINUE));
// Create Account end

$smarty->assign('language', $_SESSION['language']);
$smarty->assign('DEVMODE', USE_TEMPLATE_DEVMODE);
$smarty->caching = false;

$main_content = $smarty->fetch(CURRENT_TEMPLATE . '/module/login.html');
$smarty->assign('main_content', $main_content);

$smarty->display(CURRENT_TEMPLATE . '/index.html',USE_TEMPLATE_DEVMODE);

include ('includes/application_bottom.php');
