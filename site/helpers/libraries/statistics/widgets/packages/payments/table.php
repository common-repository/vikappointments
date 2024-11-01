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
 * Widget used to fetch the most used payment methods for packages purchase.
 *
 * @since 1.7
 */
class VAPStatisticsWidgetPackagesPaymentsTable extends VAPStatisticsWidget
{
	/**
	 * Use the layout provided by "Finance - Payment Methods" widget.
	 *
	 * @var string
	 */
	protected $layoutId = 'finance.payments.table';

	/**
	 * Checks whether the specified group is supported by the widget.
	 *
	 * @param 	string 	 $group  The group to check.
	 *
	 * @return 	boolean  True if supported, false otherwise.
	 */
	public function isSupported($group)
	{
		return empty($group) || $group == '*' || $group == 'dashboard' || $group == 'packages';
	}

	/**
	 * Checks whether the specified user is capable to access this widget.
	 *
	 * @param 	JUser    $user  The user instance.
	 *
	 * @return 	boolean  True if capable, false otherwise.
	 */
	public function checkPermissions($user)
	{
		// the visibility of this widget is limited because it displays financial reports
		return $user->authorise('core.access.analytics.finance', 'com_vikappointments');
	}

	/**
	 * Returns the widget description.
	 * By default, the description is a translatable string built
	 * in the following format: VAP_STATS_WIDGET_[NAME]_DESC.
	 *
	 * @return 	string
	 */
	public function getDescription()
	{
		// replicate description used by "Finance - Payment Methods" widget
		return JText::translate('VAP_STATS_WIDGET_FINANCE_PAYMENTS_TABLE_DESC');
	}

	/**
	 * Override this method to return a configuration form of the widget.
	 *
	 * @return 	array
	 */
	public function getForm()
	{
		return array(
			/**
			 * The initial date of the range.
			 *
			 * The parameter is VOLATILE because, every time the session
			 * ends, we need to restore the field to an empty value, just
			 * to obtain the current date.
			 *
			 * @var date
			 */
			'datefrom' => array(
				'type'     => 'date',
				'label'    => JText::translate('VAPEXPORTRES3'),
				'volatile' => true,
			),

			/**
			 * The ending date of the range.
			 *
			 * The parameter is VOLATILE because, every time the session
			 * ends, we need to restore the field to an empty value, just
			 * to obtain the current date.
			 *
			 * @var date
			 */
			'dateto' => array(
				'type'     => 'date',
				'label'    => JText::translate('VAPEXPORTRES4'),
				'volatile' => true,
			),

			/**
			 * The ordering mode of the table rows.
			 *
			 * The parameter is VOLATILE because, every time the session
			 * ends, we need to restore the field to an empty value, just
			 * to obtain the current date.
			 *
			 * @var date
			 */
			'ordering' => array(
				'type'     => 'hidden',
				'label'    => JText::translate('VAPMANAGECUSTOMF6'),
				'default'  => 'payname.asc',
				'volatile' => true,
			),
		);
	}

	/**
	 * Checks whether the widget is able to export the fetched data.
	 *
	 * @return 	array  A list of supported exportable functions.
	 */
	public function isExportable()
	{
		return ['export', 'print'];
	}

	/**
	 * Loads the dataset(s) that will be recovered asynchronously
	 * for being displayed within the widget.
	 *
	 * It is possible to return an array of records to be passed
	 * to a chart or directly the HTML to replace.
	 *
	 * @return 	mixed
	 */
	public function getData()
	{
		// fetch data and display
		return $this->display($this->fetchData());
	}

	/**
	 * Create adapter for export method.
	 * This widget only supports "print" export function.
	 *
	 * @param 	mixed  $rule  The requested export type.
	 *
	 * @return 	void
	 */
	public function export($rule = null)
	{
		// auto-fetch the widget data
		$data = $this->fetchData();

		if ($rule == 'print')
		{
			// auto-print the document
			JHtml::fetch('vaphtml.sitescripts.winprint', 256);

			// display widget with fetched data
			echo $this->display($data);
		}
		else
		{
			$app = JFactory::getApplication();

			// send headers for CSV download
			$app->setHeader('Cache-Control', 'no-store, no-cache');
			$app->setHeader('Content-Type', 'text/csv; charset=UTF-8');
			$app->setHeader('Content-Disposition', 'attachment; filename="' . htmlspecialchars($this->getTitle()) . '.csv"');
			$app->sendHeaders();
			
			$handle = fopen('php://output', 'w');

			// create table heading
			$head = array(
				// payment
				JText::translate('VAPMANAGERESERVATION13'),
				// count
				JText::translate('VAPORDERS'),
				// total gross
				JText::translate('VAPTOTALGROSS'),
				// total tax
				JText::translate('VAPTOTALTAX'),
				// total net
				JText::translate('VAPTOTALNET'),
				// total discount
				JText::translate('VAPMANAGEPACKAGE13'),
				// payment charge
				JText::translate('VAPINVPAYCHARGE'),
			);

			// include table heading
			fputcsv($handle, $head, ',', '"');

			$currency = VAPFactory::getCurrency();

			// iterate all records
			foreach ($data['data'] as $columns)
			{
				$row = array();

				foreach ($columns as $k => $v)
				{
					if ($k !== 'count' && is_numeric($v))
					{
						// format value as currency for a better usage
						$v = $currency->format($v);
					}

					// include column within the record
					$row[] = $v;
				}

				// include table row
				fputcsv($handle, $row, ',', '"');
			}

			fclose($handle);

			// terminate session
			$app->close();
		}
	}

	/**
	 * Helper getter used to access the ordering data of the widget.
	 *
	 * @return 	array  An associative array containing the ordering column and direction.
	 */
	public function getOrdering()
	{
		// extract ordering mode from widget settings, built as [COLUMN].[DIRECTION]
		$ordering = strtolower($this->getOption('ordering', 'payname.asc'));

		if (strpos($ordering, '.') !== false)
		{
			// split ordering to obtain column and direction
			list($order, $orderDir) = explode('.', $ordering);
		}
		else
		{
			// use default ordering ascending direction
			$order    = $ordering;
			$orderDir = 'asc';
		}

		return array(
			'column'    => $order,
			'direction' => $orderDir,
		);
	}

	/**
	 * Loads the dataset(s) that will be recovered asynchronously
	 * for being displayed within the widget.
	 *
	 * It is possible to return an array of records to be passed
	 * to a chart or directly the HTML to replace.
	 *
	 * @return 	mixed
	 */
	protected function fetchData()
	{
		$filters = array();
		$filters['datefrom'] = $this->getOption('datefrom');
		$filters['dateto']   = $this->getOption('dateto');

		$tz = JFactory::getUser()->getTimezone();

		if (!VAPDateHelper::isNull($filters['datefrom']))
		{
			// convert specified date to SQL format
			$filters['datefrom'] = new JDate(VAPDateHelper::getDate($filters['datefrom'], 0, 0, 0), $tz);	
		}

		if (!VAPDateHelper::isNull($filters['dateto']))
		{
			// convert specified date to SQL format
			$filters['dateto'] = new JDate(VAPDateHelper::getDate($filters['dateto'], 23, 59, 59), $tz);
		}

		$data = array();

		// import packages helper
		VAPLoader::import('libraries.statistics.helpers.packages');
		// fetch payment details
		$data['data'] = VAPStatisticsHelperPackages::getPaymentsData($filters['datefrom'], $filters['dateto']);

		$data['footer'] = array();

		foreach ($data['data'] as $id_payment => $totals)
		{
			foreach ($totals as $k => $v)
			{
				if (is_numeric($v))
				{
					if (!isset($data['footer'][$k]))
					{
						$data['footer'][$k] = 0;
					}

					$data['footer'][$k] += $v;
				}
			}
		}

		// extract widget ordering
		$ordering = $this->getOrdering();

		if ($ordering['column'] == 'payname')
		{
			if ($ordering['direction'] == 'desc')
			{
				// reverse ordering in case of descending payment
				$data['data'] = array_reverse($data['data']);
			}
		}
		else
		{
			// order the items according to the specified column and direction
			uasort($data['data'], function($a, $b) use ($ordering)
			{
				if (!isset($a[$ordering['column']]) || !isset($b[$ordering['column']]))
				{
					// the specified ordering column is not supported
					throw new DomainException(sprintf('Ordering [%s] not supported', $ordering['column']), 400);
				}

				// compare items
				$diff = $a[$ordering['column']] - $b[$ordering['column']];

				if ($ordering['direction'] == 'desc')
				{
					// reverse direction in case of descending ordering
					$diff *= -1;
				}

				return $diff;
			});
		}

		return $data;
	}
}
