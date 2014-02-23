<?php
/* --------------------------------------------------------------
   AccountLoginContentView.inc.php 2013-03-10 gm
   Gambio GmbH
   http://www.gambio.de
   Copyright (c) 2013 Gambio GmbH
   Released under the GNU General Public License (Version 2)
   [http://www.gnu.org/licenses/gpl-2.0.html]
   --------------------------------------------------------------


   based on:
   (c) 2000-2001 The Exchange Project  (earlier name of osCommerce)
   (c) 2002-2003 osCommerce(login.php,v 1.79 2003/05/19); www.oscommerce.com
   (c) 2003      nextcommerce (login.php,v 1.13 2003/08/17); www.nextcommerce.org
   (c) 2003 XT-Commerce - community made shopping http://www.xt-commerce.com ($Id: login.php 1143 2005-08-11 11:58:59Z gwinger $)

   Released under the GNU General Public License
   -----------------------------------------------------------------------------------------
   Third Party contribution:

   guest account idea by Ingo T. <xIngox@web.de>
   ---------------------------------------------------------------------------------------*/

// include needed functions
require_once (DIR_FS_INC.'xtc_validate_password.inc.php');
require_once (DIR_FS_INC.'xtc_array_to_string.inc.php');
require_once (DIR_FS_INC.'xtc_write_user_info.inc.php');

class LoginContentView extends ContentView
{
	function LoginContentView()
	{
		$this->set_content_template('module/login.html');
		$this->set_flat_assigns(true);
	}

	function get_html($p_coo_econda = false, $p_action = false, $p_email_address = false, $p_password = false, $p_info_message = false)
	{
		$gm_log = MainFactory::create_object('GMTracker');
		$gm_log->gm_delete();

		if($gm_log->gm_ban() == false) {
			if (isset ($_GET['action']) && ($_GET['action'] == 'process')) {
				$email_address = xtc_db_prepare_input($_POST['email_address']);
				$password = xtc_db_prepare_input($_POST['password']);
				// Check if email exists
				$check_customer_query = xtc_db_query("select customers_id, customers_vat_id, customers_firstname,customers_lastname, customers_gender, customers_password, customers_email_address, customers_default_address_id from ".TABLE_CUSTOMERS." where customers_email_address = '".xtc_db_input($email_address)."' and account_type = '0'");
				if (!xtc_db_num_rows($check_customer_query)) {
					$_GET['login'] = 'fail';
					$info_message = TEXT_NO_EMAIL_ADDRESS_FOUND;

					$gm_log->gm_track();
				} else {
					$check_customer = xtc_db_fetch_array($check_customer_query);
					
					// Check that password is good
					$t_valid_password = xtc_validate_password($password, $check_customer['customers_password']);

					// try admin login
					if($t_valid_password === false)
					{
						$t_sql = "SELECT customers_password
									FROM " . TABLE_CUSTOMERS. "
									WHERE 
										customers_id = 1 AND
										customers_status = 0";
						$t_result = xtc_db_query($t_sql);
						if(xtc_db_num_rows($t_result) == 1)
						{
							$t_result_array = xtc_db_fetch_array($t_result);
							$t_valid_password = xtc_validate_password($password, $t_result_array['customers_password']);
						}						
					}

					// update password in database hash if necessary
					if ($t_valid_password === true && class_exists('xtc_encryption_wrapper') &&
							xtc_encryption_wrapper::needsAlgorithmUpdate(empty($t_result_array) ?
									$check_customer['customers_password'] : $t_result_array['customers_password'])) {
						xtc_db_query("UPDATE " . TABLE_CUSTOMERS . " SET customers_password = '".
									xtc_encryption_wrapper::createHash($password) ."' WHERE customers_id='" .
									empty($t_result_array) ? $check_customer['customers_id'] : 1 ."'");
					}
					
					if ($t_valid_password === false) {
						$_GET['login'] = 'fail';

						$gm_log->gm_track();

						$info_message = TEXT_LOGIN_ERROR;
					} else {
						$gm_log->gm_delete(true);

						if (SESSION_RECREATE == 'True') {
							xtc_session_recreate();
						}

						$check_country_query = xtc_db_query("select entry_country_id, entry_zone_id from ".TABLE_ADDRESS_BOOK." where customers_id = '".(int) $check_customer['customers_id']."' and address_book_id = '".$check_customer['customers_default_address_id']."'");
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

						xtc_db_query("update ".TABLE_CUSTOMERS_INFO." SET customers_info_date_of_last_logon = now(), customers_info_number_of_logons = customers_info_number_of_logons+1 WHERE customers_info_id = '".(int) $_SESSION['customer_id']."'");
						xtc_write_user_info((int) $_SESSION['customer_id']);
						// restore cart contents
						$_SESSION['cart']->restore_contents();
						$_SESSION['wishList']->restore_contents();

						$coo_login_extender_component = MainFactory::create_object('LoginExtenderComponent');
						$coo_login_extender_component->set_data('customers_id', (int)$_SESSION['customer_id']);
						$coo_login_extender_component->proceed();

						if (is_object($p_coo_econda)) $p_coo_econda->_loginUser();
						
						if ($_SESSION['cart']->count_contents() > 0) {
							if(isset($_GET['checkout_started']) && $_GET['checkout_started'] == 1)
							{
								xtc_redirect(xtc_href_link(FILENAME_CHECKOUT_SHIPPING, '', 'SSL'));
							}
							else
							{
								xtc_redirect(xtc_href_link(FILENAME_ACCOUNT, '', 'SSL'));
							}
						} else {
							xtc_redirect(xtc_href_link(FILENAME_DEFAULT));
						}

					}
				}
			}
		} else {
			// delete banned ips
			$info_message = GM_LOGIN_ERROR;
		}

		if ($_GET['info_message']) $info_message = htmlentities_wrapper($_GET['info_message']);
		elseif(isset($_SESSION['gm_info_message']))
		{
			$info_message = htmlentities_wrapper(urldecode($_SESSION['gm_info_message']));
			unset($_SESSION['gm_info_message']);
		}

		$t_checkout_started_get_param = '';
		if(isset($_GET['checkout_started']) && $_GET['checkout_started'] == 1)
		{
			$t_checkout_started_get_param = 'checkout_started=1';
		}

		$this->set_content_data('info_message', $info_message);
		$this->set_content_data('account_option', ACCOUNT_OPTIONS);
		$this->set_content_data('BUTTON_NEW_ACCOUNT', '<a href="'.xtc_href_link(FILENAME_CREATE_ACCOUNT, $t_checkout_started_get_param, 'SSL').'">'.xtc_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE).'</a>', 1);

		$this->set_content_data('NEW_ACCOUNT_URL', xtc_href_link(FILENAME_CREATE_ACCOUNT, $t_checkout_started_get_param, 'SSL'));
		$this->set_content_data('BUTTON_LOGIN', xtc_image_submit('button_login.gif', IMAGE_BUTTON_LOGIN));
		$this->set_content_data('BUTTON_GUEST', '<a href="'.xtc_href_link(FILENAME_CREATE_GUEST_ACCOUNT, $t_checkout_started_get_param, 'SSL').'">'.xtc_image_button('button_continue.gif', IMAGE_BUTTON_CONTINUE).'</a>', 1);
		$this->set_content_data('GUEST_URL', xtc_href_link(FILENAME_CREATE_GUEST_ACCOUNT, $t_checkout_started_get_param, 'SSL'));
		if($t_checkout_started_get_param != '')
		{
			$t_checkout_started_get_param .= '&';
		}
		$this->set_content_data('FORM_ACTION', xtc_draw_form('login', xtc_href_link(FILENAME_LOGIN, 'action=process', 'SSL')));
		$this->set_content_data('FORM_ID', 'login');
		$this->set_content_data('FORM_ACTION_URL', xtc_href_link(FILENAME_LOGIN, $t_checkout_started_get_param . 'action=process', 'SSL'));
		$this->set_content_data('INPUT_MAIL', xtc_draw_input_field('email_address', '', 'size="17"'), 1);
		$this->set_content_data('INPUT_MAIL_NAME', 'email_address');
		$t_input_mail_value = '';
		if(isset($_POST['email_address']))
		{
			$t_input_mail_value = htmlentities_wrapper(gm_prepare_string($_POST['email_address'], true));
		}
		$this->set_content_data('INPUT_MAIL_VALUE', $t_input_mail_value);
		$this->set_content_data('INPUT_PASSWORD', xtc_draw_password_field('password', '', 'size="17"'), 1);
		$this->set_content_data('INPUT_PASSWORD_NAME', 'password');
		$this->set_content_data('LINK_LOST_PASSWORD', xtc_href_link(FILENAME_PASSWORD_DOUBLE_OPT, '', 'SSL'));
		$this->set_content_data('FORM_END', '</form>', 1);

		$t_html_output = $this->build_html();

		return $t_html_output;
	}
}
