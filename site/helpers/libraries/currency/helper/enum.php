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
 * Currency enumeration helper.
 *
 * @since 1.7.6
 */
final class VAPCurrencyHelperEnum
{
	/**
	 * Returns the metadata information of the specified currency.
	 * 
	 * @param   string  $currency  The currency code to look for (ISO 4217).
	 * 
	 * @return  array   The currency metadata.
	 * 
	 * @throws  DomainException
	 */
	public static function getMetadata(string $currency)
	{
		// fetch all the supported currencies
		$currencies = static::getCurrencies();

		$currency = strtoupper($currency);

		if (!isset($currencies[$currency]))
		{
			// currency not supported
			throw new DomainException('Cannot find [' . $currency . '] currency metadata');
		}

		return $currencies[$currency];
	}

	/**
	 * Returns an associative array containing the most common currencies
	 * and the related formatting information.
	 *
	 * @return 	array
	 */
	public static function getCurrencies()
	{
		static $currencies = null;

		if (!$currencies)
		{
			return [
				'EUR' => [
					'currency'  => 'Euro',
					'symbol'    => '€',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => ',',
					'space'     => true,
				],
				'USD' => [
					'currency'  => 'US Dollar',
					'symbol'    => '$',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => true,
				],
				'GBP' => [
					'currency'  => 'Pound Sterling',
					'symbol'    => '£',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => false,
				],
				'JPY' => [
					'currency'  => 'Yen',
					'symbol'    => '¥',
					'position'  => 2,
					'decimals'  => 0,
					'separator' => '.',
					'space'     => true,
				],
				'ARS' => [
					'currency'  => 'Argentine Peso',
					'symbol'    => '$',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => ',',
					'space'     => true,
				],
				'AUD' => [
					'currency'  => 'Australian Dollar',
					'symbol'    => '$',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => false,
				],
				'BRL' => [
					'currency'  => 'Brazilian Real',
					'symbol'    => 'R$',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => ',',
					'space'     => false,
				],
				'CAD' => [
					'currency'  => 'Canadian Dollar',
					'symbol'    => '$',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => false,
				],
				'CLP' => [
					'currency'  => 'Chilean Peso',
					'symbol'    => '$',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => false,
				],
				'CNY' => [
					'currency'  => 'Yuan Renminbi',
					'symbol'    => '¥',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => true,
				],
				'COP' => [
					'currency'  => 'Colombian Peso',
					'symbol'    => '$',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => true,
				],
				'CZK' => [
					'currency'  => 'Czech Koruna',
					'symbol'    => 'Kč',
					'position'  => 1,
					'decimals'  => 2,
					'separator' => ',',
					'space'     => true,
				],
				'DKK' => [
					'currency'  => 'Danish Krone',
					'symbol'    => 'kr.',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => ',',
					'space'     => false,
				],
				'HKD' => [
					'currency'  => 'Hong Kong Dollar',
					'symbol'    => 'HK$',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => false,
				],
				'HUF' => [
					'currency'  => 'Hungarian Forint',
					'symbol'    => 'Ft',
					'position'  => 1,
					'decimals'  => 2,
					'separator' => ',',
					'space'     => true,
				],
				'INR' => [
					'currency'  => 'Indian Rupee',
					'symbol'    => '₹',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => false,
				],
				'ILS' => [
					'currency'  => 'New Israeli Shekel',
					'symbol'    => '₪',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => ',',
					'space'     => true,
				],
				'KRW' => [
					'currency'  => 'Won',
					'symbol'    => '₩',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => false,
				],
				'MYR' => [
					'currency'  => 'Malaysian Ringgit',
					'symbol'    => 'RM',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => true,
				],
				'MXN' => [
					'currency'  => 'Mexican Peso',
					'symbol'    => '$',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => false,
				],
				'MAD' => [
					'currency'  => 'Moroccan Dirham',
					'symbol'    => '.د.م.',
					'position'  => 1,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => true,
				],
				'NZD' => [
					'currency'  => 'New Zealand Dollar',
					'symbol'    => '$',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => false,
				],
				'NOK' => [
					'currency'  => 'Norwegian Krone',
					'symbol'    => 'kr',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => false,
				],
				'PHP' => [
					'currency'  => 'Philippine Peso',
					'symbol'    => '₱',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => false,
				],
				'PLN' => [
					'currency'  => 'Zloty',
					'symbol'    => 'zł',
					'position'  => 1,
					'decimals'  => 2,
					'separator' => ',',
					'space'     => true,
				],
				'RUB' => [
					'currency'  => 'Russian Ruble',
					'symbol'    => 'p.',
					'position'  => 1,
					'decimals'  => 2,
					'separator' => ',',
					'space'     => true,
				],
				'SAR' => [
					'currency'  => 'Saudi Riyal',
					'symbol'    => '﷼',
					'position'  => 1,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => true,
				],
				'SGD' => [
					'currency'  => 'Singapore Dollar',
					'symbol'    => '$',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => true,
				],
				'ZAR' => [
					'currency'  => 'Rand',
					'symbol'    => 'R',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => false,
				],
				'SEK' => [
					'currency'  => 'Swedish Krona',
					'symbol'    => 'kr',
					'position'  => 1,
					'decimals'  => 2,
					'separator' => ',',
					'space'     => true,
				],
				'CHF' => [
					'currency'  => 'Swiss Franc',
					'symbol'    => 'fr.',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => ',',
					'space'     => true,
				],
				'TWD' => [
					'currency'  => 'New Taiwan Dollar',
					'symbol'    => '元',
					'position'  => 2,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => true,
				],
				'THB' => [
					'currency'  => 'Baht',
					'symbol'    => '฿',
					'position'  => 1,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => true,
				],
				'TRY' => [
					'currency'  => 'Turkish Lira',
					'symbol'    => '₺',
					'position'  => 1,
					'decimals'  => 2,
					'separator' => '.',
					'space'     => false,
				],
				'VND' => [
					'currency'  => 'Dong',
					'symbol'    => '₫',
					'position'  => 1,
					'decimals'  => 2,
					'separator' => ',',
					'space'     => true,
				],
			];
		}
		
		return $currencies;
	}
}
