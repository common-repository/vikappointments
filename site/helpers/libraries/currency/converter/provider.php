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
 * Currency converter provider class.
 * Acts as a chain of responsibility, meaning that the process automatically moves to the
 * next provider until the request is satisfied.
 *
 * @since 1.7.6
 */
interface VAPCurrencyConverterProvider
{
	/**
	 * Returns an array holding the metadata of the specified currency.
	 * 
	 * @param   string  $source    The base currency (ISO 4217).
	 * @param   string  $currency  The destination currency (ISO 4217).
	 * 
	 * @return  array   An associative array holding all the following attributes:
	 *                  - currency   string  The currency ISO 4217 code (mandatory);
	 *                  - symbol     string  The currency symbol (mandatory);
	 *                  - rate       float   The currency conversion ratio (mandatory);
	 *                  - position   int     The currency position (1: after, 2: before);
	 *                  - separator  string  The currency decimal separator (1: comma, 2: period);
	 *                  - decimals   int     The number of preferred digits for the floating part;
	 *                  - space      bool    Whether there should be a space among the currency symbol and the amount.
	 */
	public function getManifest(string $source, string $currency);

	/**
	 * Configures the next provider to invoke in case this one is not able
	 * to satisfy the initial request.
	 * 
	 * @param   VAPCurrencyConverterProvider  $nextProvider
	 * 
	 * @return  VAPCurrencyConverterProvider  The next provider instance to support chaining.
	 */
	public function setNext(VAPCurrencyConverterProvider $nextProvider);
}
