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
 * Currency converter mediator class.
 *
 * @since 1.7.6
 */
class VAPCurrencyConverterMediator extends VAPCurrencyConverter
{
	/**
	 * A list holding the cached currency instances.
	 * 
	 * @var VAPCurrency[]
	 */
	protected static $currencies = [];

	/**
	 * The chain of responsibility that will be used to fetch the manifest of the currencies.
	 * 
	 * @var VAPCurrencyConverterProvider
	 */
	protected $provider;

	/**
	 * @inheritDoc
	 */
	public function __construct(VAPCurrency $currency, VAPCurrencyConverterProvider $provider)
	{
		parent::__construct($currency);
		
		$this->provider = $provider;
	}

	/**
	 * @inheritDoc
	 */
	final public function getCurrency(string $currency)
	{
		if (strcasecmp($this->currency->getCode(), $currency) == false)
		{
			// same currency, we can return a clone of our instance
			return clone $this->currency;
		}

		if (!isset(static::$currencies[$currency]))
		{
			// get currency manifest data
			$manifest = $this->getManifest(strtoupper($currency));

			// create new currency
			static::$currencies[$currency] = new VAPCurrency(
				// override currency name
				$manifest['currency'],
				// override curency symbol if specified, otherwise it will be equal to the currency name
				$manifest['symbol'] ?? $manifest['currency'],
				// override currency position if specified, otherwise preserve the default one
				$manifest['position'] ?? $this->currency->getPosition(),
				// override currency decimal separator if specified, otherwise preserve the default one
				$manifest['separator'] ?? $this->currency->getDecimalMark(),
				// override currency number of decimals if specified, otherwise preserve the default one
				$manifest['decimals'] ?? $this->currency->getDecimalDigits(),
				// override currency symbol-amount space if specified, otherwise preserve the default one
				$manifest['space'] ?? $this->currency->isSpace(),
				// define the conversion ratio
				$manifest['rate']
			);
		}

		return static::$currencies[$currency];
	}

	/**
	 * @inheritDoc
	 */
	final public function getRate(string $currency)
	{
		return $this->getCurrency($currency)->getConversionRate();
	}

	/**
	 * @see VAPCurrencyConverterProvider::getManifest()
	 * 
	 * @throws UnexpectedValueException
	 */
	protected function getManifest(string $currency)
	{
		// get currency manifest
		$manifest = $this->provider->getManifest($this->currency->getCode(), $currency);

		if (empty($manifest['rate']))
		{
			throw new UnexpectedValueException('Unable to fetch [' . $currency . '] currency manifest');
		}

		if (empty($manifest['currency']))
		{
			$manifest['currency'] = $currency;
		}

		try
		{
			// fetch internal metadata
			$preferences = VAPCurrencyHelperEnum::getMetadata($currency);

			// fulfill the currency preferences
			$manifest['symbol']    =  $manifest['symbol']   ??    $preferences['symbol'];
			$manifest['position']  =  $manifest['position'] ??  $preferences['position'];
			$manifest['separator'] = $manifest['separator'] ?? $preferences['separator'];
			$manifest['decimals']  =  $manifest['decimals'] ??  $preferences['decimals'];
			$manifest['space']     =  $manifest['space']    ??     $preferences['space'];
		}
		catch (DomainException $error)
		{
			// cannot retrieve currency preferences
		}

		return $manifest;
	}
}
