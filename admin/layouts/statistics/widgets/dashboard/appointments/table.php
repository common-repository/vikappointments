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

/**
 * Layout variables
 * -----------------
 * @var  VAPStatisticsWidget  $widget  The instance of the widget to be displayed.
 * @var  mixed                $data    The table rows data.
 */
extract($displayData);

JHtml::fetch('vaphtml.status.contextmenu', 'appointments');

// get active tab
$active = JFactory::getApplication()->input->cookie->get('vap_widget_' . $widget->getName() . '_active_' . $widget->getID(), 'latest');

// fetch AJAX URL for status code change
$url = "index.php?option=com_vikappointments&task=reservation.changestatusajax";
$url = VAPApplication::getInstance()->ajaxUrl($url);

?>

<div class="canvas-align-top">
	
	<!-- widget container -->

	<div class="vapdash-container">

		<!-- widget tabs -->

		<div class="vapdash-tab-head">
			<div class="vapdash-tab-button">
				<a href="javascript:void(0)" data-pane="latest" class="<?php echo ($active == 'latest' ? 'active' : ''); ?>">
					<?php echo JText::translate('VAPDASHLATESTRESERVATIONS'); ?>
				</a>
			</div>

			<div class="vapdash-tab-button">
				<a href="javascript:void(0)" data-pane="incoming" class="<?php echo ($active == 'incoming' ? 'active' : ''); ?>">
					<?php echo JText::translate('VAPDASHINCOMINGRESERVATIONS'); ?>
				</a>
			</div>

			<div class="vapdash-tab-button">
				<a href="javascript:void(0)" data-pane="current" class="<?php echo ($active == 'current' ? 'active' : ''); ?>">
					<?php echo JText::translate('VAP_STATS_WIDGET_DASHBOARD_APPOINTMENTS_TABLE_CURRENT_FIELD'); ?>
				</a>
			</div>
		</div>

		<!-- widget latest appointments pane -->

		<div class="vapdash-tab-pane" data-pane="latest" style="<?php echo $active == 'latest' ? '' : 'display:none'; ?>">

		</div>

		<!-- widget incoming appointments pane -->

		<div class="vapdash-tab-pane" data-pane="incoming" style="<?php echo $active == 'incoming' ? '' : 'display:none'; ?>">

		</div>

		<!-- widget current appointments pane -->

		<div class="vapdash-tab-pane" data-pane="current" style="<?php echo $active == 'current' ? '' : 'display:none'; ?>">

		</div>

	</div>

</div>

<script>

	(function($) {
		'use strict';

		/**
		 * Flag used to track the ID of the latest order 
		 * fetched. In this way, we can play a sound every
		 * time a new order is higher the the latest one.
		 *
		 * USE the same variable for each widget in order
		 * to avoid playing the sound more than once.
		 *
		 * @var integer
		 */
		let LATEST_ORDER_FETCHED = 0;

		/**
		 * Register callback to be executed before
		 * launching the update request.
		 *
		 * @param 	mixed 	widget  The widget selector.
		 * @param 	object  config  The widget configuration.
		 *
		 * @return 	void
		 */
		WIDGET_PREFLIGHTS[<?php echo $widget->getID(); ?>] = (widget, config) => {
			// count disabled panes
			var disabled = 0;
			// reference to first enabled tab
			var firstEnabled = null;
			// flag to check if the current active pane is disabled
			var activeDisabled = false;

			var id = $(widget).attr('id');

			// iterate panes and toggle them according to the widget config
			$(widget).find('.vapdash-tab-head a').each(function() {
				var pane = $(this).data('pane');

				if (config[pane]) {
					$(this).show()
						.parent()
							.show();

					// register tab as first available, only
					// if the flag is still empty
					if (!firstEnabled) {
						firstEnabled = this;
					}
				} else {
					$(this).hide()
						.parent()
							.hide();

					// increase disabled counter
					disabled++;
					// inform the caller that the active pane is disabled
					activeDisabled = activeDisabled || $(this).hasClass('active');
				}
			});

			// hide all tabs in case only one is enabled
			if (disabled < 2) {
				$(widget).find('.vapdash-tab-head').show();
			} else {
				$(widget).find('.vapdash-tab-head').hide();
			}

			// in case the active pane was disabled, we should display the first one available
			if (activeDisabled && firstEnabled) {
				$(firstEnabled).trigger('click');
			}

			// destroy any previously selected context menus to handle the status change
			$('#widget-<?php echo $widget->getID(); ?> .status-hndl').statusCodesPopup('destroy');
		}

		/**
		 * Register callback to be executed after
		 * completing the update request.
		 *
		 * @param 	mixed 	widget  The widget selector.
		 * @param 	string 	data    The JSON response.
		 * @param 	object  config  The widget configuration.
		 *
		 * @return 	void
		 */
		WIDGET_CALLBACKS[<?php echo $widget->getID(); ?>] = (widget, data, config) => {
			var id = $(widget).attr('id');

			$(widget).find('.vapdash-tab-pane').each(function() {
				// get pane id
				var pane = $(this).data('pane');

				if (data[pane] !== undefined) {
					// fill body with returned HTML
					$(this).html(data[pane]);
				} else {
					// set empty string
					$(this).html('');
				}
			});

			$(widget).find('.hasTooltip').tooltip();

			var tmp_latest_id   = LATEST_ORDER_FETCHED;
			var firstDownload   = LATEST_ORDER_FETCHED == 0;
			var shouldPlaySound = false;

			// iterate all order IDs to look for a newer one
			$(widget).find('tr[data-order-id]').each(function() {
				// extract order ID
				var id_order = parseInt($(this).data('order-id'));

				// the order is higher than the current one
				if (id_order > LATEST_ORDER_FETCHED) {
					// update flag
					LATEST_ORDER_FETCHED = id_order;
				}

				// Make sure we are not doing the first download of the session.
				// Compare order ID with previous one in order to mark all the
				// new records instead of the latest one.
				if (!firstDownload  && id_order > tmp_latest_id) {
					shouldPlaySound = true;
				}
			});

			// play notification sound in case of new orders
			// and in case we are not doing the first download
			if (shouldPlaySound) {
				$.vapDashboard('play');
			}

			// init status code popup
			$('#widget-<?php echo $widget->getID(); ?> .status-hndl').statusCodesPopup({
				group: 'appointments',
				url: '<?php echo $url; ?>',
				onShow: () => {
					// stop dashboard timer as long as the context menu is open
					$.vapDashboard('stop');
				},
				onHide: () => {
					// restart dashboard timer after dismissing the context menu
					$.vapDashboard('start');
				},
			});
		}

		$(function() {
			// get widget element
			const widget = $('#widget-<?php echo $widget->getID(); ?>');

			// register click event for tab buttons
			$(widget).find('.vapdash-tab-head a').on('click', function() {
				// get button pane
				var pane = $(this).data('pane');

				// deactivate all buttons
				$(widget).find('.vapdash-tab-head a').removeClass('active');
				// active clicked button
				$(this).addClass('active');

				// hide all panes
				$(widget).find('.vapdash-tab-pane').hide();
				// show selected pane
				$(widget).find('.vapdash-tab-pane[data-pane="' + pane + '"]').show();

				// register selected button in cookie
				document.cookie = 'vap.widget.<?php echo $widget->getName(); ?>.active.<?php echo $widget->getID(); ?>=' + pane + '; path=/';
			});

			// display order details in a modal box
			$(document).on('click', '#widget-<?php echo $widget->getID(); ?> a[data-order-id]', function() {
				// get order ID
				const order_id = $(this).data('order-id');

				// update href to access the management page of the order
				let href = $('#orderinfo-edit-btn').attr('href');
				$('#orderinfo-edit-btn').attr('href', href.replace(/cid\[\]=[\d]*$/, 'cid[]=' + order_id));

				// create URL
				const url = 'index.php?option=com_vikappointments&view=orderinfo&tmpl=component&cid[]=' + order_id;
				// open modal
				$('#jmodal-orderinfo').vapJModal('open', url);
			});

			// display customer details in a modal box
			$(document).on('click', '#widget-<?php echo $widget->getID(); ?> a[data-customer-id]', function() {
				// get customer ID
				const user_id = $(this).data('customer-id');

				// update href to access the management page of the customer
				let href = $('#custinfo-edit-btn').attr('href');
				$('#custinfo-edit-btn').attr('href', href.replace(/cid\[\]=[\d]*$/, 'cid[]=' + user_id));

				// create URL
				const url = 'index.php?option=com_vikappointments&view=customerinfo&tmpl=component&cid[]=' + user_id;
				// open modal
				$('#jmodal-custinfo').vapJModal('open', url);
			});
		});
	})(jQuery);

</script>