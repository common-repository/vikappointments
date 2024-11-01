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
 * Null converter provider aware.
 *
 * @since 1.7.6
 */
final class VAPCurrencyConverterProviderNull extends VAPCurrencyConverterProviderAware
{
	/**
	 * @inheritDoc
	 */
	protected function provide(string $source, string $currency)
	{
		throw new RuntimeException('Missing implementation', 501);
	}
}
