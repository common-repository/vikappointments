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
 * @var  mixed                $footer  The table footer.
 */
extract($displayData);

if (empty($data))
{
	// do nothing in case of empty data
	return;
}

$currency = VAPFactory::getCurrency();

// get ordering
$ordering = $widget->getOrdering();

?>

<div class="widget-revenue-table">
	<table data-widget-id="<?php echo $widget->getID(); ?>">

		<thead>
			<tr>

				<!-- DATE -->

				<th style="text-align: left;" class="<?php echo $ordering['column'] == 'date' ? 'sorted' : ''; ?>">
					<?php echo JHtml::fetch('vaphtml.admin.customsort', 'VAPMANAGEINVOICE2', 'date', $ordering['direction'], $ordering['column'], 'desc'); ?>
				</th>

				<!-- COUNT -->
				
				<th style="text-align: right;" class="<?php echo $ordering['column'] == 'count' ? 'sorted' : ''; ?>">
					<?php echo JHtml::fetch('vaphtml.admin.customsort', 'VAPMENUOPTIONS', 'count', $ordering['direction'], $ordering['column'], 'desc'); ?>
				</th>

				<!-- TOTAL GROSS -->
				
				<th style="text-align: right;" class="<?php echo $ordering['column'] == 'total' ? 'sorted' : ''; ?>">
					<?php echo JHtml::fetch('vaphtml.admin.customsort', 'VAPTOTALGROSS', 'total', $ordering['direction'], $ordering['column'], 'desc'); ?>
				</th>

				<!-- TOTAL TAX -->
				
				<th style="text-align: right;" class="<?php echo $ordering['column'] == 'tax' ? 'sorted' : ''; ?>">
					<?php echo JHtml::fetch('vaphtml.admin.customsort', 'VAPTOTALTAX', 'tax', $ordering['direction'], $ordering['column'], 'desc'); ?>
				</th>

				<!-- TOTAL NET -->
				
				<th style="text-align: right;" class="<?php echo $ordering['column'] == 'net' ? 'sorted' : ''; ?>">
					<?php echo JHtml::fetch('vaphtml.admin.customsort', 'VAPTOTALNET', 'net', $ordering['direction'], $ordering['column'], 'desc'); ?>
				</th>

				<!-- TOTAL DISCOUNT -->
				
				<th style="text-align: right;" class="<?php echo $ordering['column'] == 'discount' ? 'sorted' : ''; ?>">
					<?php echo JHtml::fetch('vaphtml.admin.customsort', 'VAPMANAGEPACKAGE13', 'discount', $ordering['direction'], $ordering['column'], 'desc'); ?>
				</th>

			</tr>
		</thead>

		<tbody>
			<?php
			foreach ($data as $date => $totals)
			{
				?>
				<tr>

					<!-- DATE -->

					<td style="text-align: left;" class="<?php echo $ordering['column'] == 'date' ? 'sorted' : ''; ?>">
						<?php echo $date; ?>
					</td>

					<!-- COUNT -->
				
					<td style="text-align: right;" class="<?php echo $ordering['column'] == 'count' ? 'sorted' : ''; ?>">
						<?php echo $totals['count']; ?>
					</td>

					<!-- TOTAL GROSS -->
					
					<td style="text-align: right;" class="<?php echo $ordering['column'] == 'total' ? 'sorted' : ''; ?>">
						<?php echo $currency->format($totals['total']); ?>
					</td>

					<!-- TOTAL TAX -->
					
					<td style="text-align: right;" class="<?php echo $ordering['column'] == 'tax' ? 'sorted' : ''; ?>">
						<?php echo $currency->format($totals['tax']); ?>
					</td>

					<!-- TOTAL NET -->
					
					<td style="text-align: right;" class="<?php echo $ordering['column'] == 'net' ? 'sorted' : ''; ?>">
						<?php echo $currency->format($totals['net']); ?>
					</td>

					<!-- TOTAL DISCOUNT -->
					
					<td style="text-align: right;" class="<?php echo $ordering['column'] == 'discount' ? 'sorted' : ''; ?>">
						<?php echo $currency->format($totals['discount']); ?>
					</td>

				</tr>
				<?php
			}
			?>
		</tbody>

		<tfoot>
			<tr>
				
				<!-- DATE -->

				<td style="text-align: left;" class="<?php echo $ordering['column'] == 'date' ? 'sorted' : ''; ?>">&nbsp;</td>

				<!-- COUNT -->
				
				<td style="text-align: right;" class="<?php echo $ordering['column'] == 'count' ? 'sorted' : ''; ?>">
					<?php echo $footer['count'] ?? 0; ?>
				</td>

				<!-- TOTAL GROSS -->
				
				<td style="text-align: right;" class="<?php echo $ordering['column'] == 'total' ? 'sorted' : ''; ?>">
					<?php echo $currency->format($footer['total'] ?? 0); ?>
				</td>

				<!-- TOTAL TAX -->
				
				<td style="text-align: right;" class="<?php echo $ordering['column'] == 'tax' ? 'sorted' : ''; ?>">
					<?php echo $currency->format($footer['tax'] ?? 0); ?>
				</td>

				<!-- TOTAL NET -->
				
				<td style="text-align: right;" class="<?php echo $ordering['column'] == 'net' ? 'sorted' : ''; ?>">
					<?php echo $currency->format($footer['net'] ?? 0); ?>
				</td>

				<!-- TOTAL DISCOUNT -->
				
				<td style="text-align: right;" class="<?php echo $ordering['column'] == 'discount' ? 'sorted' : ''; ?>">
					<?php echo $currency->format($footer['discount'] ?? 0); ?>
				</td>

			</tr>
		</tfoot>

	</table>
</div>
