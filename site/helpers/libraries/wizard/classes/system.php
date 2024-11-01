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
 * Implement the wizard step used to setup the basic
 * settings of the global configuration.
 *
 * @since 1.7.1
 */
class VAPWizardStepSystem extends VAPWizardStep
{
	/**
	 * Returns the step title.
	 * Used as a very-short description.
	 *
	 * @return 	string  The step title.
	 */
	public function getTitle()
	{
		return JText::translate('VAPMENUCONFIG');
	}

	/**
	 * Returns an optional step icon.
	 *
	 * @return 	string  The step icon.
	 */
	public function getIcon()
	{
		return '<i class="fas fa-cog"></i>';
	}

	/**
	 * Return the group to which the step belongs.
	 *
	 * @return 	string  The group name.
	 */
	public function getGroup()
	{
		// belongs to GLOBAL group
		return JText::translate('VAPMENUTITLEHEADER3');
	}

	/**
	 * Implements the step execution.
	 *
	 * @param 	JRegistry  $data  The request data.
	 *
	 * @return 	boolean
	 */
	protected function doExecute($data)
	{
		$config = VAPFactory::getConfig();

		if ($currency = $data->get('currency'))
		{
			// get supported currencies
			$map = $this->getCurrencies();

			// make sure the currency exists
			if (!isset($map[$currency]))
			{
				return false;
			}

			$format = $map[$currency];

			// set currency parameters
			$config->set('currencyname', $currency);
			$config->set('currencysymb', $format['symbol']);
			$config->set('currsymbpos', $format['position']);
			$config->set('currdecimaldig', (int) $format['decimals']);
			$config->set('currthousandssep', $format['separator'] == '.' ? ',' : '.');
			$config->set('currdecimalsep', $format['separator']);
		}
		else
		{
			// set specified custom currency
			$config->set('currencyname', $data->get('currencyname'));
			$config->set('currencysymb', $data->get('currencysymb'));
		}

		$config->set('agencyname', $data->get('agencyname'));
		$config->set('adminemail', $data->get('adminemail'));

		if (!$config->get('senderemail'))
		{
			// set sender equals to admin e-mail if empty
			$config->set('senderemail', $data->get('adminemail'));
		}

		// set date/time format
		$config->set('dateformat', $data->get('dateformat'));
		$config->set('timeformat', $data->get('timeformat') ? 'H:i' : 'h:i A');

		return true;
	}

	/**
	 * Returns an associative array containing the most common currencies
	 * and the related formatting information.
	 *
	 * @return 	array
	 * 
	 * @deprecated 1.9 Use VAPCurrencyHelperEnum::getCurrencies() instead.
	 */
	public function getCurrencies()
	{
		return VAPCurrencyHelperEnum::getCurrencies();
	}
}
