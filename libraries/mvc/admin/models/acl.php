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

JLoader::import('adapter.mvc.models.form');

/**
 * VikAppointments plugin ACL model.
 *
 * @since 1.0
 * @see   JModelForm
 */
class VikAppointmentsModelAcl extends JModelForm
{
	/**
	 * A list containing the roles to ignore.
	 *
	 * @var array
	 */
	private $ignores = array('administrator');

	/**
	 * @override
	 * Updates the ACL for the existing Wordpress user roles.
	 *
	 * @param 	array 	 &$data  The array data containing the ACL rules.
	 *
	 * @return 	boolean  True on success, otherwise false.
	 */
	public function save(&$data)
	{
		foreach ($data as $slug => $actions)
		{
			$role = get_role($slug);

			if ($role && !in_array($slug, $this->ignores))
			{
				foreach ($actions as $cap => $has)
				{
					$has = (int) $has;

					if ($has != -1)
					{
						$b = $role->add_cap($cap, (bool) $has);
					}
				}
			}
		}

		return true;
	}
}
