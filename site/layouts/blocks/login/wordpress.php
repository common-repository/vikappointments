<?php
/** 
 * @package     VikAppointments
 * @subpackage  core
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$returnUrl   = isset($displayData['return'])   ? $displayData['return']   : null;
$remember    = isset($displayData['remember']) ? $displayData['remember'] : false;
$footerLinks = isset($displayData['footer'])   ? $displayData['footer']   : true;
$formId      = isset($displayData['form'])     ? ltrim($displayData['form'], '#') : null;

$vik = VAPApplication::getInstance();

// create login URL for Wordpress
$url = wp_login_url($returnUrl);
// append action=login
$url .= (strpos($url, '?') !== false ? '&' : '?') . 'action=login';
?>

<script>
	(function($) {
		'use strict';

		$(function() {
			<?php if ($formId): ?>
				// if we are already inside a form, update the action of the form when the login button gets clicked
				$('#vap-wp-login-submit').on('click', () => {
					$('.vap-login-form').closest('form').attr('action', '<?php echo $url; ?>');
				});
			<?php endif; ?>
		});
	})(jQuery);
</script>

<?php
if ($formId)
{
	// the login is already contained within a parent form
	?>
	<div class="vap-login-form">
	<?php
}
?>
	<h3><?php echo JText::translate('VAPLOGINTITLE'); ?></h3>
	
	<div class="vaploginfieldsdiv">
		
		<?php
		/**
		 * Display the login form through the native WordPress function.
		 * 
		 * @since 1.2
		 */
		wp_login_form(array(
			'echo'           => true,
			'redirect'       => $returnUrl ? $vik->routeForExternalUse($returnUrl) : null,
			'form_id'        => 'loginform',
			'label_username' => __('Username or Email Address'),
			'label_password' => __('Password' ),
			'label_remember' => __('Remember Me' ),
			'label_log_in'   => __('Log In'),
			'id_username'    => 'user_login',
			'id_password'    => 'user_pass',
			'id_remember'    => 'rememberme',
			'id_submit'      => 'vap-wp-login-submit',
			'remember'       => true,
			'value_username' => '',
			'value_remember' => $remember,
		));
		?>

	</div>

	<?php
	if ($footerLinks)
	{
		?>
		<div class="vap-login-footer-links">
			<div>
				<a href="<?php echo wp_lostpassword_url(); ?>" target="_blank">
					<?php echo JText::translate('COM_USERS_LOGIN_RESET'); ?>
				</a>
			</div>
		</div>
		<?php
	}
	?>

	<?php echo JHtml::fetch('form.token'); ?>

<?php
if ($formId)
{
	?></div><?php
}
