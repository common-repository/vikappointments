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
 * VikAppointments cron job table.
 *
 * @since 1.7
 */
class VAPTableCronjob extends JTableVAP
{
	/**
	 * Class constructor.
	 *
	 * @param 	object 	$db  The database driver instance.
	 */
	public function __construct($db)
	{
		parent::__construct('#__vikappointments_cronjob', 'id', $db);

		// register required fields
		$this->_requiredFields[] = 'name';
		$this->_requiredFields[] = 'class';
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

		if (empty($src['id']) && empty($src['createdon']))
		{
			// set creation date
			$src['createdon'] = JDate::getInstance()->toSql();
		}

		if (isset($src['params']) && !is_string($src['params']))
		{
			// stringify cron job parameters
			$src['params'] = json_encode($src['params']);
		}

		// bind the details before save
		return parent::bind($src, $ignore);
	}
}
