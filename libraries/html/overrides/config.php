<?php
/** 
 * @package     VikAppointments - Libraries
 * @subpackage  html.overrides
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

$vik = VAPApplication::getInstance();

JText::script('VAP_CONFIRM_MESSAGE_UNSAVE');

?>

<div class="config-fieldset">

	<div class="config-fieldset-body">

		<p>
			<?php
			_e(
				'From this section it is possible to override the pages and the layouts of the plugin. Click the button below to open the file manager and start editing the pages.',
				'vikappointments'
			);
			?>
		</p>

		<?php echo $vik->alert(__('Go ahead only if you are able to deal with PHP and HTML code.', 'vikappointments'), 'warning'); ?>

		<div>
			<a href="admin.php?page=vikappointments&view=overrides" class="button button-hero" id="vap-overrides-btn" style="text-align: center;">
				<?php _e('Open Overrides Manager', 'vikappointments'); ?>
			</a>
		</div>

	</div>

</div>

<script>
	(function($) {
		'use strict';

		$(function() {
			$('#vap-overrides-btn').on('click', function(event) {
				if (!configObserver.isChanged()) {
					// nothing has changed, go ahead
					return true;
				}

				// ask for a confirmation
				if (!confirm(Joomla.JText._('VAP_CONFIRM_MESSAGE_UNSAVE'))) {
					// do not leave the page
					event.preventDefault();
					event.stopPropagation();
					return false;
				}
			});
		});
	})(jQuery);
</script>