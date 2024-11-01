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
 * @var  VAPAvailabilityTimeline  $timeline  The timeline to render.
 */
extract($displayData);

$config = VAPFactory::getConfig();

$time_format   = $config->get('timeformat');
$show_checkout = $config->getBool('showcheckout');

$tz = VikAppointments::getUserTimezone();
		
$titles_lookup = array(
	JText::translate('VAPFINDRESTIMENOAV'),
	JText::translate('VAPFINDRESBOOKNOW'),
	JText::translate('VAPFINDRESNOENOUGHTIME'),
);

foreach ($timeline as $times)
{
	?>
	<div class="vaptimelinewt">
		<?php
		foreach ($times as $time)
		{	
			$clickEvent = '';

			// get hour and minutes
			$hour = (int) $time->checkin('G');
			$min  = (int) $time->checkin('i');
			
			if ($time->isAvailable())
			{
				// allow time block to be clicked
				$clickEvent = "vapTimeClicked($hour, $min, this);";
			}

			$title = $titles_lookup[$time->status];
			
			?>
			<a href="javascript:void(0)" title="<?php echo $this->escape($title); ?>" onClick="<?php echo $clickEvent; ?>">
				<div 
					class="vap-timeline-block vaptlblock<?php echo $time->status; ?>"
					data-rate="<?php echo $time->price; ?>"
					data-hour="<?php echo $hour; ?>"
					data-min="<?php echo $min; ?>"
				>
					<?php
					echo $time->checkin($time_format, $tz);

					/**
					 * Display checkout time if enabled.
					 *
					 * @since 1.6.2
					 */
					if ($show_checkout)
					{
						echo ' - ' . $time->checkout($time_format, $tz);
					}
					?>
				</div>
			</a>
			<?php
		}
		?>
	</div>
	<?php
}
