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
 * European Central converter provider class.
 * This service is free to use but only supports the EUR conversion rates.
 *
 * @since 1.7.6
 */
class VAPCurrencyConverterProviderEcb extends VAPCurrencyConverterProviderAware
{
	use VAPCurrencyHelperCacheable;

	/**
	 * @inheritDoc
	 */
	protected function provide(string $source, string $currency)
	{
		// we can accept only EUR as source
		if (strcasecmp($source, 'EUR'))
		{
			throw new InvalidArgumentException('ECB supports only the EUR currency, ' . $source . ' provided instead.');
		}

		// cache HTTP response for 6 hours
		$buffer = $this->getCached('ecb.xml', 6 * 60 * 60, function() {
			// make request to fetch the latest EUR conversion rates
			$response = (new JHttp)->get('https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml');

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
		foreach ($xml->Cube->Cube->Cube as $currencyNode)
		{
			$attr = $currencyNode->attributes();

			// keep going until we found the matching currency
			if (strcasecmp($currency, (string) $currencyNode->attributes()->currency))
			{
				continue;
			}

			// calculate conversion rate
			$rate = 1 / (float) $currencyNode->attributes()->rate;

			return [
				'rate' => $rate,
			];
		}

		// currency not observed
		throw new DomainException('ECB does not track the [' . $currency . '] exchange rate');
	}
}
