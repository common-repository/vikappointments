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
 * @var  mixed                $data    The chart data to immediately display.
 */
extract($displayData);

// include chart JS dependencies
JHtml::fetch('vaphtml.assets.chartjs');

// get list of preset colors
$colors = JHtml::fetch('vaphtml.color.preset', $list = true, $group = false);

?>

<div class="canvas-align-bottom appointments-payments-chart">
	<canvas></canvas>
</div>

<script>

	/**
	 * Defines a pool of charts, if undefined.
	 *
	 * @var object
	 */
	if (typeof APPOINTMENTS_PAYMENTS_CHARTS === 'undefined') {
		var APPOINTMENTS_PAYMENTS_CHARTS = {};
	}

	// init callbacks pool in case the caller didn't declare it
	if (typeof WIDGET_CALLBACKS === 'undefined') {
		var WIDGET_CALLBACKS = {};
	}

	(function($) {
		'use strict';

		// get default system preset of colors
		const colorsPreset = <?php echo json_encode($colors); ?>;

		/**
		 * Register callback to be executed after
		 * completing the update request.
		 *
		 * @param 	mixed   widget  The widget selector.
		 * @param 	mixed   data    The AJAX response.
		 * @param 	object  config  The widget configuration.
		 *
		 * @return 	void
		 */
		WIDGET_CALLBACKS[<?php echo $widget->getID(); ?>] = (widget, data, config) => {
			// get widget ID
			var id = $(widget).attr('id');

			// start from first position of the preset
			let colorIndex = 0;

			/**
			 * Create callback used to format the values displayed
			 * on the Y axis, according to the saved configuration.
			 *
			 * @param 	mixed 	value  The value passed by the chart.
			 *
			 * @return 	string  The formatted value.
			 */
			const formatAxisY = (value) => {
				<?php
				if ($widget->hasFinanceAccess())
				{
					?>
					// do not display decimal values on Y axis
					return VAPCurrency.getInstance().format(value, 0);
					<?php
				}
				else
				{
					?>
					// display value as it is
					return value;
					<?php
				}
				?>
			}

			/**
			 * Create callback used to format the values displayed
			 * with the tooltips of the hovered points, according
			 * to the saved configuration.
			 *
			 * @param 	mixed 	item  The item to display.
			 * @param 	mixed 	data  The chart data.
			 *
			 * @return 	string  The formatted value.
			 */
			const formatPointTooltip = (item, data) => {
				// extract payment name from dataset
				let payName = data.datasets[item.datasetIndex].label;

				<?php
				if ($widget->hasFinanceAccess())
				{
					?>
					// format value as currency
					let label = VAPCurrency.getInstance().format(item.value);
					<?php
				}
				else
				{
					?>
					// use value as it is
					let label = item.value;
					<?php
				}
				?>

				// create label in the form "PAYMENT: TOTAL"
				return ' ' + payName + ': ' + label;
			}

			// prepare chart data
			var chartData = {
				labels: Object.keys(data),
				datasets: {},
			};

			// iterate all dates
			$.each(data, (lbl, payments) => {
				// iterate all payments
				$.each(payments, (paymentName, value) => {
					// check whether we already created the dataset for this payment gateway
					if (!chartData.datasets.hasOwnProperty(paymentName)) {
						// get progressive color
						let color = colorsPreset[colorIndex++ % colorsPreset.length];

						// nope, init data set
						chartData.datasets[paymentName] = {
							// the label string that appears when hovering the mouse above the lines intersection points
							label: paymentName,
							// the background color drawn behind the line (33 = 20% opacity)
							backgroundColor: "#" + color + "33",
							// the fill color of the line
							borderColor: "#" + color,
							// the fill color of the points
							pointBackgroundColor: "#" + color,
							// the border color of the points
							pointBorderColor: "#fff",
							// the radius of the points (in pixel)
							pointRadius: 4,
							// the fill color of the points when hovered
							pointHoverBackgroundColor: "#fff",
							// the border color of the points when hovered
							pointHoverBorderColor: "#" + color,
							// the radius of the points (in pixel) when hovered
							pointHoverRadius: 5,
							// the line dataset
							data: [],
						};
					}

					// include value within the dataset
					chartData.datasets[paymentName].data.push(value);
				});
			});

			// convert datasets into a linear array
			chartData.datasets = Object.values(chartData.datasets);

			// init chart from scratch if NULL
			if (!APPOINTMENTS_PAYMENTS_CHARTS.hasOwnProperty(id)) {
				// prepare chart configuration
				var options = {
					legend: {
						// draw legend only if we are printing the document
						display: <?php echo isset($data) ? 'true' : 'false'; ?>,
					},
					// axes handling
					scales: {
						// Y Axis properties
						yAxes: [{
							// make sure the chart starts at 0
							ticks: {
								// format value as currency
								callback: formatAxisY,
								beginAtZero: true,
							},
						}],
					},
					// tooltip handling
					tooltips: {
						// tooltip callbacks are used to customize default texts
						callbacks: {
							// format the tooltip text displayed when hovering a point
							label: formatPointTooltip,
							// change label colors because, by default, the legend background is blank
							labelColor: (tooltipItem, chart) => {
								// get tooltip item meta data
								var meta = chart.data.datasets[tooltipItem.datasetIndex];

								return {
									// use white border
									borderColor: 'rgb(0,0,0)',
									// use same item background color
									backgroundColor: meta.borderColor,
								};
							},
						},
					},
					animation: {
						// unset duration in case we are exporting the chart
						duration: <?php echo isset($data) ? 0 : 1000; ?>,
					},
				};

				// get 2D canvas for LINE chart
				var canvas = $(widget).find('canvas')[0];
				var ctx    = canvas.getContext('2d');

				// init chart from scratch if undefined
				APPOINTMENTS_PAYMENTS_CHARTS[id] = new Chart(ctx, {
					type:    'line',
					data:    chartData,
					options: options,
				});
			}
			// otherwise update labels and values
			else {
				// update chart data
				APPOINTMENTS_PAYMENTS_CHARTS[id].data = chartData;

				// update format callbacks
				APPOINTMENTS_PAYMENTS_CHARTS[id].options.scales.yAxes[0].ticks.callback = formatAxisY;
				APPOINTMENTS_PAYMENTS_CHARTS[id].options.tooltips.callbacks.label = formatPointTooltip;

				// refresh chart
				APPOINTMENTS_PAYMENTS_CHARTS[id].update();
			}
		}

		<?php

		////////////////////////
		///// EXPORT UTILS /////
		////////////////////////

		if (isset($data))
		{
			?>
			$(function() {
				// auto-invoke callback on page loading completion
				WIDGET_CALLBACKS[<?php echo $widget->getID(); ?>](
					// exported assume, there should be only one widget displayed
					$('.appointments-payments-chart'),
					// JSON-encode the passed chart data
					<?php echo json_encode($data); ?>,
					// JSON-encode the widget configuration
					<?php echo json_encode($widget->getOptions()); ?>
				);
			});
			<?php
		}
		?>
	})(jQuery);

</script>
