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
 * Currency API converter provider class.
 * @link https://currencyapi.com
 *
 * @since 1.7.6
 */
class VAPCurrencyConverterProviderCurrencyapi extends VAPCurrencyConverterProviderAware
{
	use VAPCurrencyHelperCacheable;

	/** @var string */
	protected $apikey;

	/** @var int */
	protected $lifetime;

	/**
	 * Class constructor.
	 * 
	 * @param  string  $apikey    The service API Key.
	 * @param  int     $lifetime  The cache lifetime (in minutes).
	 */
	public function __construct(string $apikey, int $lifetime = 240)
	{
		$this->apikey = $apikey;
		$this->lifetime = max(1, abs($lifetime));
	}

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
		if ($this->apikey === '')
		{
			// prevent query in case the API key was not provided
			throw new InvalidArgumentException('Missing provider configuration');
		}

		// cache HTTP response for 4 hours
		$buffer = $this->getCached('currencyapi_' . strtolower($source) . '.xml', $this->lifetime * 60, function() use ($source) {
			// make request to fetch the latest conversion rates
			$response = (new JHttp)->get('https://api.currencyapi.com/v3/latest?base_currency=' . $source, [
				'apikey' => $this->apikey,
			]);

			if ($response->code != 200)
			{
				// decode body from JSON
				$body = json_decode($response->body ?: '');

				// use provided message, if any
				throw new UnexpectedValueException($body->message ?? $response->body, $response->code);
			}

			return $response->body;
		});

		// decode buffer from JSON
		$body = json_decode($buffer);

		if (!isset($body->data->{$currency}->value))
		{
			// currency not observed
			throw new DomainException('Currency API does not track the [' . $currency . '] exchange rate');
		}

		// calculate conversion rate
		$rate = 1 / (float) $body->data->{$currency}->value;

		return [
			'rate' => $rate,
		];
	}
}
