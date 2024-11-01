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

VikAppointmentsLoader::import('language.widget');

/**
 * Switcher class to translate the VikAppointments Currency Converter widget languages.
 *
 * @since 	1.0
 */
class Mod_VikAppointments_CurrencyconverterLanguageHandler extends VikAppointmentsLanguageWidget
{
	/**
	 * Checks if exists a translation for the given string.
	 *
	 * @param 	string 	$string  The string to translate.
	 *
	 * @return 	string 	The translated string, otherwise null.
	 */
	public function translate($string)
	{
		$result = null;

		/**
		 * Translations go here.
		 * @tip Use 'TRANSLATORS:' comment to attach a description of the language.
		 */

		switch ($string)
		{
			/**
			 * Currency converter module.
			 */

			case 'VAP_CURR_CONV_FORMAT':
				$result = __('Currency Format', 'vikappointments');
				break;

			case 'VAP_CURR_CONV_FORMAT_DESC':
				$result = __('The format that will be used to display the currency within the dropdown options. The supported tags will be replaced as described below.<ul><li><code>{code}</code> - The ISO 4217 currency 3-letters code (EUR).</li><li><code>{name}</code> - The extended currency name (Euro).</li><li><code>{symbol}</code> - The symbol displayed next to the prices (€).</li></ul>', 'vikappointments');
				break;

			case 'VAP_CURR_CONV_SUPPORTED_LIST':
				$result = __('Supported Currencies', 'vikappointments');
				break;

			case 'VAP_CURR_CONV_SUPPORTED_LIST_DESC':
				$result = __('Select all the currencies that can be selected from the front-end. Leave empty to support all the currencies.', 'vikappointments');
				break;
				
			default:
				// fallback to parent handler for commons
				$result = parent::translate($string);
		}

		return $result;
	}
}
