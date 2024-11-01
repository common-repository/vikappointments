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

VAPLoader::import('libraries.helpers.module');

/**
 * Helper class used by the Currency Converter module.
 *
 * @since 1.0
 */
class VikAppointmentsCurrencyConverterHelper
{
	/**
	 * Use methods defined by modules trait for a better reusability.
	 *
	 * @see VAPModuleHelper
	 */
	use VAPModuleHelper;

	/**
	 * Returns the currency selected by the user.
	 * 
	 * @return  string  The ISO 4217 of the preferred currency.
	 */
	public static function getUserCurrency()
	{
		return JFactory::getApplication()->getUserState(
			'vikappointments.user.currency',
			VAPFactory::getConfig()->get('currencyname')
		);
	}

	/**
	 * Returns a list of currencies according to the configured ones.
	 * 
	 * @param   Registry  $params  The module configuration.
	 * 
	 * @return  object[]  The select options.
	 */
	public static function getCurrencies($params)
	{
		$config = VAPFactory::getConfig();

		// take the list containing all the selected currencies
		$value = $params->get('currencies', []);

		// take the option text template
		$template = $params->get('format', '{name} ({symbol})');

		// take only the selected currencies
		$currencies = array_filter(VAPCurrencyHelperEnum::getCurrencies(), function($code) use ($value) {
			return !$value || in_array($code, $value);
		}, ARRAY_FILTER_USE_KEY);

		$options = [];

		// get system currency
		$currencyName = $config->get('currencyname');
		$currencySymb = $config->get('currencysymb');

		if (!isset($currencies[$currencyName]))
		{
			// currency not supported, manually register it
			$options[] = [
				'currency' => $currencyName,
				'code' => $currencyName,
				'symbol' => $currencySymb,
			];
		}
		else
		{
			// always move the default currency on top
			$options[] = array_merge($currencies[$currencyName], ['code' => $currencyName]);
		}

		// iterate all the supported currencies
		foreach ($currencies as $code => $data)
		{
			if ($code === $currencyName)
			{
				// skip default currency
				continue;
			}

			$options[] = array_merge($data, ['code' => $code]);
		}

		// format currencies accordingly
		return array_map(function($currency) use ($template) {
			return JHtml::fetch('select.option', $currency['code'], static::formatCurrency($template, $currency));
		}, $options);
	}

	/**
	 * Formats the currency option displayed in the dropdown.
	 * 
	 * @param   string  $template  The option text template.
	 * @param   array   $metadata  The currency metadata
	 *
	 * @return  string  The resulting option text.
	 */
	protected static function formatCurrency(string $template, array $metadata)
	{
		$template = preg_replace("/{name}/i", $metadata['currency'] ?? '', $template);
		$template = preg_replace("/{symbol}/i", $metadata['symbol'] ?? '', $template);
		$template = preg_replace("/{code}/i", $metadata['code'] ?? '', $template);

		return $template;
	}
}
