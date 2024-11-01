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
 * Event used to fetch the details of a specific appointment.
 *
 * @since 1.7.6
 */
class VAPApiEventGetAppointment extends VAPApiEvent
{
	/**
	 * The custom action that the event have to perform.
	 * This method should not contain any exit or die function, 
	 * otherwise the event won't be properly terminated.
	 *
	 * @param 	array           $args      The provided arguments for the event.
	 * @param 	VAPApiResponse  $response  The response object for admin.
	 *
	 * @return 	mixed           The response to output or the error message (VAPApiError).
	 */
	protected function doAction(array $args, VAPApiResponse $response)
	{
		$response->setStatus(1);

		// fetch appointment ID from the request
		$id = (int) ($args['id'] ?? 0);

		try
		{
			if ($id <= 0)
			{
				throw new InvalidArgumentException('Invalid appointment ID', 400);
			}

			VAPLoader::import('libraries.order.factory');
  			$order = VAPOrderFactory::getAppointments($id);
		}
		catch (Exception $error)
		{
			// register response and abort request
			$response->setStatus(0)->setContent($error->getMessage());

			throw $error;
		}

		// let the application framework safely output the response
		return $order;
	}

	/**
	 * @override
	 * Returns the description of the plugin.
	 *
	 * @return 	string
	 */
	public function getDescription()
	{
		// read the description HTML from a layout
		return JLayoutHelper::render('api.plugins.get_appointment', array('plugin' => $this));
	}
}
