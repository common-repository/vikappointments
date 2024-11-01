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
 * Float Rates converter provider class.
 * @link https://floatrates.com
 *
 * @since 1.7.6
 */
class VAPCurrencyConverterProviderFloatrates extends VAPCurrencyConverterProviderAware
{
	use VAPCurrencyHelperCacheable;

	/**
	 * @inheritDoc
	 * 
	 * The Currency API service allows up to 300 requests per month.
	 * This means that we can refresh the conversion rates up to 9 times per day.
	 * 
	 * @link https://currencyapi.com/docs/latest
	 */
	protected function provide(string $source, string $currency)
	{
		// cache HTTP response for 12 hours
		$buffer = $this->getCached('floatrates_' . strtolower($source) . '.xml', 12 * 60 * 60, function() use ($source) {
			// make request to fetch the latest conversion rates
			$response = (new JHttp)->get('https://www.floatrates.com/daily/' . strtolower($source) . '.xml');

			if ($response->code != 200)
			{
				// an error has occurred
				throw new UnexpectedValueException($response->body, $response->code);
			}

			return $response->body;
		});

		// decode XML string
		$xml = simplexml_load_string($buffer);

		if (!$xml instanceof SimpleXMLElement)
		{
			// the received XML cannot be decoded
			throw new UnexpectedValueException('Invalid XML received: ' . $buffer);
		}

		// iterate all the supported currency nodes
		foreach ($xml->item as $item)
		{
			// keep going until we found the matching currency
			if (strcasecmp($currency, (string) $item->targetCurrency))
			{
				continue;
			}

			return [
				'rate' => (float) $item->inverseRate,
			];
		}

		// currency not observed
		throw new DomainException('ECB does not track the [' . $currency . '] exchange rate');
	}
}
