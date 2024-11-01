<?php
/**
 * @package     VikAppointments
 * @subpackage  mod_vikappointments_currencyconverter
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access to this file
defined('ABSPATH') or die('No script kiddies please!');

jimport('adapter.module.widget');

/**
 * Currency Converter Module implementation for WP.
 *
 * @see 	JWidget
 * @since 	1.0
 */
class ModVikappointmentsCurrencyconverter_Widget extends JWidget
{
	/**
	 * Class constructor.
	 */
	public function __construct()
	{
		// attach the absolute path of the module folder
		parent::__construct(dirname(__FILE__));

		try
		{
			// convert this widget into a block
			$this->registerBlockType(
				VIKAPPOINTMENTS_CORE_MEDIA_URI,
				[
					'icon' => 'money-alt',
					'keywords' => [
						__('VikAppointments', 'vikappointments'),
						__('Currency Converter', 'vikappointments'),
						__('Widget'),
					],
				]
			);
		}
		catch (Throwable $error)
		{
			// there's a conflict with an outdated plugin
		}
	}
}
