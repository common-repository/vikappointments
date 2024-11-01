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
 * VikAppointments appointment table.
 *
 * @since 1.7
 */
class VAPTableReservation extends JTableVAP
{
	/**
	 * Class constructor.
	 *
	 * @param 	object 	$db  The database driver instance.
	 */
	public function __construct($db)
	{
		parent::__construct('#__vikappointments_reservation', 'id', $db);
	}

	/**
	 * Method to bind an associative array or object to the Table instance. This
	 * method only binds properties that are publicly accessible and optionally
	 * takes an array of properties to ignore when binding.
	 *
	 * @param   array|object  $src     An associative array or object to bind to the Table instance.
	 * @param   array|string  $ignore  An optional array or space separated list of properties to ignore while binding.
	 *
	 * @return  boolean  True on success.
	 */
	public function bind($src, $ignore = array())
	{
		$src = (array) $src;

		if (empty($src['id']))
		{
			$src['createdon'] = JFactory::getDate()->toSql();
			$src['createdby'] = JFactory::getUser()->id;

			if (!isset($src['sid']))
			{
				// generate order key
				$src['sid'] = VikAppointments::generateSerialCode(16, 'appointment-sid');
				// generate confirmation key
				$src['conf_key'] = VikAppointments::generateSerialCode(12, 'appointment-confkey');
			}
		}

		if (isset($src['total_cost']))
		{
			// total cost cannot be lower than 0
			$src['total_cost'] = max(array(0, (float) $src['total_cost']));
		}

		if (isset($src['duration']))
		{
			// duration cannot be lower than 1
			$src['duration'] = max(array(1, (int) $src['duration']));
		}

		if (isset($src['custom_f']) && !is_string($src['custom_f']))
		{
			// encode array/object in JSON format
			$src['custom_f'] = json_encode($src['custom_f']);
		}

		if (isset($src['attendees']) && !is_string($src['attendees']))
		{
			// encode array/object in JSON format
			$src['attendees'] = json_encode($src['attendees']);
		}

		if (isset($src['uploads']) && !is_string($src['uploads']))
		{
			// encode array in JSON format
			$src['uploads'] = json_encode($src['uploads']);
		}

		if (isset($src['fields_data']))
		{
			$src['fields_data'] = (array) $src['fields_data'];

			$lookup = array(
				'purchaser_nominative',
				'purchaser_mail',
				'purchaser_phone',
				'purchaser_prefix',
				'purchaser_country',
			);

			// set up billing according to the specified custom fields,
			// only in case the targeted field is empty
			foreach ($lookup as $k)
			{
				if (empty($src[$k]) && !empty($src['fields_data'][$k]))
				{
					$src[$k] = $src['fields_data'][$k];
				}
			}
		}

		// stringify coupon code when passed as array/object
		if (isset($src['coupon']) && !is_string($src['coupon']))
		{
			$cpn = (array) $src['coupon'];
			$src['coupon_str'] = $cpn['code'] . ';;' . $cpn['percentot'] . ';;' . $cpn['value'];
		}

		if (isset($src['cc_data']) && !is_string($src['cc_data']))
		{
			// encode array/object in JSON format
			$src['cc_data'] = json_encode($src['cc_data']);
		}

		// always calculate lock time after creating/updating a reservation
		$src['locked_until'] = strtotime('+' . VAPFactory::getConfig()->getUint('keepapplock') . 'minutes');

		// bind the details before save
		return parent::bind($src, $ignore);
	}
}