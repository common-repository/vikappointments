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

VAPLoader::import('libraries.statistics.helpers.commons.query');

/**
 * Helper class used to calculate options statistics.
 *
 * @since 1.7.5
 */
abstract class VAPStatisticsHelperOptions
{
	/**
	 * Use methods defined by query trait for a better reusability.
	 *
	 * @see VAPStatisticsHelperCommonQuery
	 */
	use VAPStatisticsHelperCommonQuery;

	/**
	 * Loads the revenue data coming from the options and group them by date.
	 *
	 * @param 	mixed    $from      The from date object or string.
	 * @param 	mixed    $to        The to date object or string.
	 * @param 	mixed    $column    Either an array of columns or a single one.
	 * @param 	boolean  $extended  True to use an extended date format.
	 *
	 * @return 	mixed
	 */
	public static function getRevenue($from, $to, $column = 'total', $extended = false)
	{
		if (is_string($from))
		{
			// create date instance
			$from = JFactory::getDate($from);
		}

		if (is_string($to))
		{
			// create date instance
			$to = JFactory::getDate($to);
		}

		// check if we are filtering by weeks/last month
		if (VAPDateHelper::diff($from, $to, 'days') <= 31)
		{
			if ($extended)
			{
				// use extended date format
				$label_format = JText::translate('DATE_FORMAT_LC3');
			}
			else
			{
				// use the format set from the configuration
				$label_format = VAPFactory::getConfig()->get('dateformat');
			}

			// get rid of year
			$label_format = preg_replace("/[^a-z]?Y[^a-z]?/", '', $label_format);

			// group by day in SQL query
			$sql_format = '%Y-%m-%d';
			// iterate day by day
			$modifier = '+1 day';
		}
		else
		{
			if ($extended)
			{
				// use extended date format
				$label_format = JText::translate('DATE_FORMAT_LC3');
				// get rid of month day
				$label_format = preg_replace("/[^a-z]?d[^a-z]?/", '', $label_format);
			}
			else
			{
				// Check whether the specified dates are in different years.
				// In case they are, the labels format should be "M Y", otherwise just "M" could be used.
				$label_format = $from->format('Y', true) == $to->format('Y', true) ? 'M' : 'M Y';
			}

			// group by month in SQL query
			$sql_format = '%Y-%m';
			// iterate month by month
			$modifier = '+1 month';
		}

		$dt = clone $from;

		$data = array();

		// iterate as long as the date is lower than the ending date
		while ($dt < $to)
		{
			// format label
			$label = JHtml::fetch('date', $dt->format('Y-m-d H:i:s', true), $label_format);

			if (is_array($column))
			{
				// init chart data with sub-array
				$data[$label] = array();

				foreach ($column as $colName)
				{
					$data[$label][$colName] = 0;
				}
			}
			else
			{
				// init chart data
				$data[$label] = 0;
			}

			// increase date by the fetched modifier
			$dt->modify($modifier);
		}

		$dbo = JFactory::getDbo();

		// build query to fetch appointments total
		$query = static::buildRevenueQuery('appointments', $from, $to, $sql_format);

		// take only the appointments with at least an option sold
		$query->innerjoin($dbo->qn('#__vikappointments_res_opt_assoc', 'a') . ' ON ' . $dbo->qn('a.id_reservation') . ' = ' . $dbo->qn('o.id'));

		// count number of records
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.quantity'), $dbo->qn('count')));

		// sum totals
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.gross'), $dbo->qn('total')));
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.tax'), $dbo->qn('tax')));
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.net'), $dbo->qn('net')));
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.discount'), $dbo->qn('discount')));

		$dbo->setQuery($query);

		foreach ($dbo->loadObjectList() as $row)
		{
			// convert query date into our date format
			$key = JHtml::fetch('date', $row->date, $label_format);

			if (!isset($data[$key]))
			{
				// something went wrong, label not found...
				continue;
			}

			// check whether we have to map one column or more
			if (is_array($column))
			{
				// iterate each column to map
				foreach ($column as $colName)
				{
					// make sure the specified column exists
					if (isset($row->{$colName}))
					{
						// increase the specified column
						$data[$key][$colName] += $row->{$colName};
					}
				}
			}
			else
			{
				// increase the specified column
				$data[$key] += $row->{$column};
			}
		}

		return $data;
	}

	/**
	 * Calculates a few statistics about the sold options.
	 *
	 * @param 	mixed    $from        The from date object or string.
	 * @param 	mixed    $to          The to date object or string.
	 * @param   bool     $variations  Group options by variation.
	 *
	 * @return 	mixed
	 */
	public static function getRevenueData($from = null, $to = null, $variations = false)
	{
		if (!VAPDateHelper::isNull($from) && is_string($from))
		{
			// create date instance
			$from = JFactory::getDate($from);
		}

		if (!VAPDateHelper::isNull($to) && is_string($to))
		{
			// create date instance
			$to = JFactory::getDate($to);
		}

		$dbo = JFactory::getDbo();

		$rows = static::getOptions($variations);

		if (!$rows)
		{
			// no supported options
			return false;
		}

		$data = [];

		// init option data
		foreach ($rows as $option)
		{
			$id   = $option->id;
			$name = $option->name;

			if ($variations && $option->id_variation > 0)
			{
				$id   .= '-' . $option->id_variation;
				$name .= ' - ' . $option->var_name;
			}

			$data[$id] = [
				'name' => $name,
			];
		}

		// build query to fetch appointments total
		$query = static::buildRevenueQuery('appointments', $from, $to);

		// take only the appointments with at least an option sold
		$query->innerjoin($dbo->qn('#__vikappointments_res_opt_assoc', 'a') . ' ON ' . $dbo->qn('a.id_reservation') . ' = ' . $dbo->qn('o.id'));
		
		// group orders by option
		$query->select($dbo->qn('a.id_option'));
		$query->group($dbo->qn('a.id_option'));

		if ($variations)
		{
			$query->select($dbo->qn('a.id_variation'));
			$query->group($dbo->qn('a.id_variation'));
		}

		// count number of records
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.quantity'), $dbo->qn('count')));

		// sum totals
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.gross'), $dbo->qn('total')));
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.tax'), $dbo->qn('tax')));
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.net'), $dbo->qn('net')));
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.discount'), $dbo->qn('discount')));

		$dbo->setQuery($query);
		
		foreach ($dbo->loadObjectList() as $row)
		{
			$optId = $row->id_option;

			if ($variations && $row->id_variation > 0)
			{
				$optId .= '-' . $row->id_variation;
			}

			if (!isset($data[$optId]))
			{
				// option not found
				continue;
			}

			// iterate each column to map
			foreach ($row as $colName => $colValue)
			{
				if ($colName === 'id_option' || $colName === 'id_variation' || $colName === 'payment')
				{
					continue;
				}

				// make sure the specified column exists
				if (!isset($data[$optId][$colName]))
				{
					// init value
					$data[$optId][$colName] = 0;
				}

				// increase the specified column
				$data[$optId][$colName] += $colValue;
			}
		}

		return $data;
	}

	/**
	 * Loads the trend of the sold options.
	 *
	 * @param 	mixed  $from        The from date object or string.
	 * @param 	mixed  $to          The to date object or string.
	 * @param 	mixed  $column      Either an array of columns or a single one.
	 * @param   bool   $variations  Group options by variation.
	 * @param   mixed  $optionIds   Either an option ID or an array.
	 *
	 * @return 	mixed
	 */
	public static function getRevenueTrend($from, $to, $column = 'total', $variations = false, $optionIds = null)
	{
		if (is_string($from))
		{
			// create date instance
			$from = JFactory::getDate($from);
		}

		if (is_string($to))
		{
			// create date instance
			$to = JFactory::getDate($to);
		}

		// check if we are filtering by weeks/last month
		if (VAPDateHelper::diff($from, $to, 'days') <= 31)
		{
			// use the format set from the configuration
			$label_format = VAPFactory::getConfig()->get('dateformat');
			// get rid of year
			$label_format = preg_replace("/[^a-z]?Y[^a-z]?/", '', $label_format);

			// group by day in SQL query
			$sql_format = '%Y-%m-%d';
			// iterate day by day
			$modifier = '+1 day';
		}
		else
		{
			// Check whether the specified dates are in different years.
			// In case they are, the labels format should be "M Y", otherwise just "M" could be used.
			$label_format = $from->format('Y', true) == $to->format('Y', true) ? 'M' : 'M Y';

			// group by month in SQL query
			$sql_format = '%Y-%m';
			// iterate month by month
			$modifier = '+1 month';
		}

		$dt = clone $from;

		$data = [];

		// iterate as long as the date is lower than the ending date
		while ($dt < $to)
		{
			// format label
			$label = JHtml::fetch('date', $dt->format('Y-m-d H:i:s', true), $label_format);

			$data[$label] = [];

			// increase date by the fetched modifier
			$dt->modify($modifier);
		}

		$dbo = JFactory::getDbo();

		$rows = static::getOptions($variations, (array) $optionIds);

		if (!$rows)
		{
			// no supported options
			return false;
		}

		$options = [];

		// create options id-name lookup
		foreach ($rows as $option)
		{
			$id   = $option->id;
			$name = $option->name;

			if ($variations && $option->id_variation > 0)
			{
				$id   .= '-' . $option->id_variation;
				$name .= ' - ' . $option->var_name;
			}

			$options[$id] = $name;
		}

		// build query to fetch appointments total
		$query = static::buildRevenueQuery('appointments', $from, $to, $sql_format);

		// take only the appointments with at least an option sold
		$query->innerjoin($dbo->qn('#__vikappointments_res_opt_assoc', 'a') . ' ON ' . $dbo->qn('a.id_reservation') . ' = ' . $dbo->qn('o.id'));
		
		// group orders by option
		$query->select($dbo->qn('a.id_option'));
		$query->group($dbo->qn('a.id_option'));

		if ($variations)
		{
			$query->select($dbo->qn('a.id_variation'));
			$query->group($dbo->qn('a.id_variation'));
		}

		if ($optionIds)
		{
			// take only the options found with the previous query
			$query->where($dbo->qn('a.id_option') . ' IN (' . implode(',', array_map('intval', (array) $optionIds)) . ')');
		}

		// count number of records
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.quantity'), $dbo->qn('count')));

		// sum totals
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.gross'), $dbo->qn('total')));

		$dbo->setQuery($query);

		// keep track of all the used options, so that we can normalize each dataset
		$fetched = [];

		foreach ($dbo->loadObjectList() as $row)
		{
			// convert query date into our date format
			$key = JHtml::fetch('date', $row->date, $label_format);

			if (!isset($data[$key]))
			{
				// something went wrong, label not found...
				continue;
			}

			$optId = $row->id_option;

			if ($variations && $row->id_variation > 0)
			{
				$optId .= '-' . $row->id_variation;
			}

			// extract option name from lookup
			$optName = $options[$optId] ?? '/';

			// track used option
			$fetched[$optId] = $optName;

			// check whether we have to map one column or more
			if (is_array($column))
			{
				// iterate each column to map
				foreach ($column as $colName)
				{
					// make sure the specified column exists
					if (isset($row->{$colName}))
					{
						if (!isset($data[$key][$optName][$colName]))
						{
							// init total for this option
							$data[$key][$optName][$colName] = 0;
						}

						// increase the specified column
						$data[$key][$optName][$colName] += $row->{$colName};
					}
				}
			}
			else
			{
				if (!isset($data[$key][$optName]))
				{
					// init total for this option
					$data[$key][$optName] = 0;
				}

				// increase the specified column
				$data[$key][$optName] += $row->{$column};
			}
		}

		// normalize datasets by creating a null value for each missing option
		foreach ($data as $key => $options)
		{
			foreach ($fetched as $optName)
			{
				if (!isset($options[$optName]))
				{
					// check whether we have to map one column or more
					if (is_array($column))
					{
						// iterate each column to map
						foreach ($column as $colName)
						{
							$data[$key][$optName][$colName] = 0;
						}
					}
					else
					{
						$data[$key][$optName] = 0;
					}
				}
			}
		}

		return $data;
	}

	/**
	 * Loads the count of the sold options.
	 *
	 * @param 	mixed  $from        The from date object or string.
	 * @param 	mixed  $to          The to date object or string.
	 * @param 	mixed  $column      Either an array of columns or a single one.
	 * @param   bool   $variations  Group options by variation.
	 * @param   mixed  $optionIds   Either an option ID or an array.
	 *
	 * @return 	mixed
	 */
	public static function getRevenueCount($from, $to, $column = 'total', $variations = false, $optionIds = null)
	{
		if (!VAPDateHelper::isNull($from) && is_string($from))
		{
			// create date instance
			$from = JFactory::getDate($from);
		}

		if (!VAPDateHelper::isNull($to) && is_string($to))
		{
			// create date instance
			$to = JFactory::getDate($to);
		}

		$dbo = JFactory::getDbo();

		$rows = static::getOptions($variations, (array) $optionIds);

		if (!$rows)
		{
			// no supported options
			return false;
		}

		$data = $options = [];

		// create options id-name lookup
		foreach ($rows as $option)
		{
			$id   = $option->id;
			$name = $option->name;

			if ($variations && $option->id_variation > 0)
			{
				$id   .= '-' . $option->id_variation;
				$name .= ' - ' . $option->var_name;
			}

			$options[$id] = $name;
			$data[$name] = null;
		}

		// build query to fetch appointments total
		$query = static::buildRevenueQuery('appointments', $from, $to);

		// take only the appointments with at least an option sold
		$query->innerjoin($dbo->qn('#__vikappointments_res_opt_assoc', 'a') . ' ON ' . $dbo->qn('a.id_reservation') . ' = ' . $dbo->qn('o.id'));
		
		// group orders by option
		$query->select($dbo->qn('a.id_option'));
		$query->group($dbo->qn('a.id_option'));

		if ($variations)
		{
			$query->select($dbo->qn('a.id_variation'));
			$query->group($dbo->qn('a.id_variation'));
		}

		if ($optionIds)
		{
			// take only the options found with the previous query
			$query->where($dbo->qn('a.id_option') . ' IN (' . implode(',', array_map('intval', (array) $optionIds)) . ')');
		}

		// count number of records
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.quantity'), $dbo->qn('count')));

		// sum totals
		$query->select(sprintf('SUM(%s) AS %s', $dbo->qn('a.gross'), $dbo->qn('total')));

		$dbo->setQuery($query);

		foreach ($dbo->loadObjectList() as $row)
		{
			$optId = $row->id_option;

			if ($variations && $row->id_variation > 0)
			{
				$optId .= '-' . $row->id_variation;
			}

			// extract option name from lookup
			$optName = $options[$optId] ?? '/';

			if (!array_key_exists($optName, $data))
			{
				// the option doesn't exist any longer
				continue;
			}

			// check whether we have to map one column or more
			if (is_array($column))
			{
				// iterate each column to map
				foreach ($column as $colName)
				{
					// make sure the specified column exists
					if (isset($row->{$colName}))
					{
						if (!isset($data[$optName][$colName]))
						{
							// init total for this option
							$data[$optName][$colName] = 0;
						}

						// increase the specified column
						$data[$optName][$colName] += $row->{$colName};
					}
				}
			}
			else
			{
				if (!isset($data[$optName]))
				{
					// init total for this option
					$data[$optName] = 0;
				}

				// increase the specified column
				$data[$optName] += $row->{$column};
			}
		}

		return $data;
	}

	/**
	 * Returns a list containing all the supported options.
	 * 
	 * @param   bool   $variations  Group options by variation.
	 * @param   array  $options     An array of options to filter.
	 * 
	 * @return  object[]
	 */
	public static function getOptions($variations = false, array $options = [])
	{
		$dbo = JFactory::getDbo();

		// load all the supported options
		$q = $dbo->getQuery(true)
			->select($dbo->qn(['o.id', 'o.name']))
			->from($dbo->qn('#__vikappointments_option', 'o'))
			->order($dbo->qn('o.ordering') . ' ASC');

		if ($variations)
		{
			$q->select($dbo->qn('v.id', 'id_variation'));
			$q->select($dbo->qn('v.name', 'var_name'));
			$q->leftjoin($dbo->qn('#__vikappointments_option_value', 'v') . ' ON ' . $dbo->qn('v.id_option') . ' = ' . $dbo->qn('o.id'));
			$q->order($dbo->qn('v.ordering') . ' ASC');
		}

		if ($options)
		{
			// take only the specified options
			$q->where($dbo->qn('o.id') . ' IN (' . implode(',', array_map('intval', $options)) . ')');
		}

		$dbo->setQuery($q);
		return $dbo->loadObjectList();
	}
}
