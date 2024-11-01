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
 * Currency API converter provider aware.
 *
 * @since 1.7.6
 */
abstract class VAPCurrencyConverterProviderAware implements VAPCurrencyConverterProvider
{
	/** @var VAPCurrencyConverterProvider|null */
	protected $nextProvider;

	/**
	 * @inheritDoc
	 */
	final public function getManifest(string $source, string $currency)
	{
		try
		{
			// provide currency data
			return $this->provide($source, $currency);	
		}
		catch (Exception $error)
		{
			// provider failed, catch error silently
		}

		// move to the next provider, if any
		if ($this->nextProvider)
		{
			// invoke the next provider
			return $this->nextProvider->getManifest($source, $currency);	
		}

		// we reached the end of the chain, unable to satisfy the request
		return null;
	}

	/**
	 * @inheritDoc 
	 */
	final public function setNext(VAPCurrencyConverterProvider $nextProvider)
	{
		$this->nextProvider = $nextProvider;

		// Return the instance of the next provider to support chaining.
		// Returning $this would replace the previously registered provider instead.
		return $this->nextProvider;
	}

	/**
	 * Concrete implementor used to fetch the currency manifest.
	 * 
	 * @see VAPCurrencyConverterProvider::getManifest()
	 */
	abstract protected function provide(string $source, string $currency);
}
