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
 * Currency converter class handler.
 *
 * @since 1.7.6
 */
abstract class VAPCurrencyConverter
{
	/** @var VAPCurrency */
	protected $currency;

	/**
	 * Class constructor.
	 * 
	 * @param  VAPCurrency  $currency  The source currency object.
	 */
	public function __construct(VAPCurrency $currency)
	{
		$this->currency = $currency;
	}

	/**
	 * Returns a new currency instance containing the preferences of the
	 * specified one as well as the ratio to perform real-time conversions.
	 * 
	 * @param   string  $currency  The new currency (ISO 4217).
	 * 
	 * @return  VAPCurrency
	 */
	abstract public function getCurrency(string $currency);

	/**
	 * Returns the conversion rate among the specified currency and the default one.
	 * 
	 * @param   string  $currency  The destination currency (ISO 4217).
	 * 
	 * @return  float
	 */
	abstract public function getRate(string $currency);

	/**
	 * Converts the provided price from the original currency to the specified one.
	 * 
	 * @param   float   $amount    The amount to convert.
	 * @param   string  $currency  The destination currency (ISO 4217).
	 * 
	 * @return  float
	 */
	final public function getPrice(float $amount, string $currency)
	{
		return $amount / $this->getRate($currency);
	}
}
