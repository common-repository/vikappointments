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

jimport('joomla.form.formfield.list');

/**
 * Form field override to display a list of supported currencies.
 *
 * @since 1.0
 */
class JFormFieldCurrencies extends JFormFieldList
{
	/**
	 * The type of the field.
	 * MUST be equal to the definition in the XML file.
	 *
	 * @var string 
	 */
	protected $type = 'currencies';

	/**
	 * Method to get the options to populate the list.
	 *
	 * @return  array  The field option objects.
	 */
	public function getOptions()
	{
		$options = [];

		if (defined('JPATH_SITE') && JPATH_SITE !== 'JPATH_SITE')
		{
			// autoload VAP
			$success = require_once JPath::clean(JPATH_SITE . '/components/com_vikappointments/helpers/libraries/autoload.php');
		}
		else
		{
			// WP platform, VAP should be already loaded
			$success = defined('VIKAPPOINTMENTS_SOFTWARE_VERSION');
		}

		if (!$success)
		{
			throw new Exception('VikAppointments is not installed!', 404);
		}

		// get all currencies
		$currencies = VAPCurrencyHelperEnum::getCurrencies();

		// get system currency
		$currencyName = VAPFactory::getConfig()->get('currencyname');

		if (!isset($currencies[$currencyName]))
		{
			// currency not supported, manually register it
			$options[] = JHtml::fetch('select.option', $currencyName, $currencyName);
		}
		else
		{
			// always move the default currency on top
			$options[] = JHtml::fetch('select.option', $currencyName, $currencies[$currencyName]['currency'] . ' (' . $currencyName . ')');
		}

		// iterate all the supported currencies
		foreach ($currencies as $code => $data)
		{
			if ($code === $currencyName)
			{
				// skip default currency
				continue;
			}

			$options[] = JHtml::fetch('select.option', $code, $data['currency'] . ' (' . $code . ')');
		}

		return array_merge(parent::getOptions(), $options);
	}
}
