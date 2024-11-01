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
 * Imports an array of iCalendar events.
 * 
 * @since 1.7.3
 */
class VAPIcalImporter extends JObject
{
	/**
	 * The employee ID. If omitted, the system will attempt
	 * to extract it from the calendar event by looking into
	 * the "organizer" attribute.
	 * 
	 * @var int
	 */
	protected $employee;

	/**
	 * The service ID. If omitted, the system will attempt
	 * to extract it from the calendar by checking whether
	 * there's a service that matches the event summary.
	 * 
	 * @var int
	 */
	protected $service;

	/**
	 * Whether the system should validate the availability of
	 * the imported events or whether it should import them
	 * in any case.
	 * 
	 * @var bool
	 * @since 1.7.5
	 */
	protected $validateAvailability;

	/**
	 * A unique identifier used to create an assignment between
	 * the imported events and the source calendar.
	 * 
	 * @var string|null
	 * @since 1.7.5
	 */
	protected $calendarHash;

	/**
	 * Class constructor.
	 * 
	 * @param  array  $options  An array of options.
	 */
	public function __construct(array $options = [])
	{
		$this->employee = (int) ($options['id_employee'] ?? 0);
		$this->service  = (int) ($options['id_service']  ?? 0);

		$this->validateAvailability = (bool) ($options['validate_availability'] ?? true);

		$this->calendarHash = $options['calendar_uid'] ?? null;
	}

	/**
	 * Attempts to import a list of iCalendar events.
	 * 
	 * @param   VAPIcalEvent[]  $events  The events to import.
	 * 
	 * @return  VAPIcalEvent[]  A list of imported events.
	 */
	public function import(array $events)
	{
		$added = [];

		// iterate all events
		foreach ($events as $event)
		{
			if (!$event instanceof VAPIcalEvent)
			{
				// ignore invalid instances
				continue;
			}

			try
			{
				// try to import the event
				$appData = $this->importEvent($event);

				if ($appData)
				{
					$appData['summary'] = $event->summary;

					// event properly registered
					$added[] = $appData;
				}
			}
			catch (Exception $e)
			{
				// track error
				$this->setError($e);
			}
		}

		return $added;
	}

	/**
	 * Imports the specified event.
	 * 
	 * @param   VAPIcalEvent  $event  The event to import.
	 * 
	 * @return  mixed         The appointment data on success, false otherwise.
	 * 
	 * @throws  Exception
	 */
	public function importEvent(VAPIcalEvent $event)
	{
		$data = array();

		///////////////////////////////////
		// fetch event unique identifier //
		///////////////////////////////////

		if (!$event->uid)
		{
			// missing event ID
			throw new Exception('Event ID not specified', 400);
		}

		$data['icaluid'] = $event->uid;

		// load reservation model
		$model = JModelVAP::getInstance('reservation');

		if (!$model)
		{
			// an error occurred, model not found...
			throw new Exception('Missing reservation model', 500);
		}

		// check whether we already fetched the specified event
		$item = $model->getItem([
			'icaluid' => $event->uid,
		]);

		if ($item)
		{
			// do reservation update
			$data['id'] = $item->id;

			// get last modify of the appointment
			$dt = JFactory::getDate($item->modifiedon ? $item->modifiedon : $item->createdon)->toISO8601();

			// update item only in case the event has a modify date greater than
			// the one saved in the database
			if ($dt >= $event->getLastModify())
			{
				// nothing has changed, avoid to process the event
				return false;
			}
		}

		//////////////////////////////
		// fetch check-in date time //
		//////////////////////////////

		if (!$event->start)
		{
			// missing start date time
			throw new Exception('Event start date time not specified', 400);
		}

		$data['checkin_ts'] = JDate::getInstance($event->start)->toSql();

		////////////////////////////////
		// fetch appointment duration //
		////////////////////////////////

		// extract duration from event (convert seconds in minutes)
		$data['duration'] = $event->duration / 60;

		if (!$data['duration'])
		{
			// missing duration
			throw new Exception($event . ': event duration not specified', 400);
		}

		///////////////////////
		// fetch employee ID //
		///////////////////////

		if ($this->employee)
		{
			// use the configured employee
			$data['id_employee'] = $this->employee;
		}
		else
		{
			if (!$event->organizer)
			{
				// missing employee
				throw new Exception($event . ': event organizer (employee) not specified', 400);
			}

			// get employee model
			$employeeModel = JModelVAP::getInstance('employee');

			if (!$employeeModel)
			{
				// an error occurred, model not found...
				throw new Exception('Missing employee model', 500);
			}

			// check if we have an employee assigned to the specified e-mail
			$employee = $employeeModel->getItem(array('email' => $event->organizer));

			if (!$employee)
			{
				// missing employee
				throw new Exception(sprintf($event . ': event organizer [%s] not found', $event->organizer), 404);
			}

			// register found employee
			$data['id_employee'] = $employee->id;
		}

		//////////////////////
		// fetch service ID //
		//////////////////////

		if ($this->service)
		{
			// use the configured service
			$data['id_service'] = $this->service;

			// service already specified, assume the summary is the name/mail of the customer
			if (strpos($event->summary, '@') !== false)
			{
				// e-mail address given
				$data['purchaser_mail'] = trim($event->summary);
			}
			else
			{
				// fallback to customer nominative
				$data['purchaser_nominative'] = trim($event->summary);
			}
		}
		else
		{
			// find the service that fit at best the given event
			$data['id_service'] = $this->findService($data, $event);

			if (!$data['id_service'])
			{
				// no assigned services
				throw new Exception(sprintf($event . ': event organizer [%s] does not support any services', $event->organizer), 500);
			}
		}

		//////////////////////////////////
		// fetch number of participants //
		//////////////////////////////////

		// fetch attendees list
		$attendees = $event->getAttendeesList();

		if (!$attendees)
		{
			// use default number of participants
			$data['people'] = 1;
		}
		else
		{
			// number of participants equals to the number of attendees
			$data['people'] = max(array(1, count($attendees)));

			// use the first available e-mail
			$data['purchaser_mail'] = array_shift($attendees);

			// check whether we still have other attendees to register
			if ($attendees)
			{
				$data['attendees'] = array();

				foreach ($attendees as $email)
				{
					// register only the attendee e-mail
					$data['attendees'][] = array('purchaser_mail' => $email);
				}
			}
		}

		/////////////////////////
		// fetch customer name //
		/////////////////////////
		
		if (!empty($data['purchaser_mail']))
		{
			// get customer model
			$customerModel = JModelVAP::getInstance('customer');

			if (!$customerModel)
			{
				// an error occurred, model not found...
				throw new Exception('Missing customer model', 500);
			}

			// check if we have a customer assigned to the specified e-mail
			$customer = $customerModel->getItem(array('billing_mail' => $data['purchaser_mail']));

			// does the customer exist?
			if ($customer)
			{
				// yep, inject customer details
				$data['purchaser_nominative'] = $customer->billing_name;
				$data['purchaser_phone']      = $customer->billing_phone;
				$data['purchaser_country']    = $customer->country_code;
				$data['id_user']              = $customer->id;
			}
		}

		/////////////////////////
		// register user notes //
		/////////////////////////

		if ($event->description)
		{
			// register description contents within the reservation notes
			$data['notes'] = nl2br($event->description);
		}

		////////////////////////////
		// create new appointment //
		////////////////////////////

		// force a confirmed status for the event to import
		$data['status'] = JHtml::fetch('vaphtml.status.confirmed', 'appointments', 'code');

		// remind that the appointment has been imported from a remote iCal
		$data['status_comment'] = 'VAP_STATUS_CHANGED_ON_ICAL_IMPORT';

		if ($this->validateAvailability)
		{
			// validate the availability of the appointment to avoid conflicts
			$data['validate_availability'] = 'admin';
		}

		if ($this->calendarHash)
		{
			// register within the details of the appointment the source
			// calendar from which the events has been downloaded
			$data['icalhash'] = $this->calendarHash;
		}

		if (empty($data['id']))
		{
			// recalculate the totals
			$model->recalculateTotals($data);
		}

		// save appointment details
		$id = $model->save($data);

		if (!$id)
		{
			// an error occurred, retrieve error from model
			$error = $model->getError();

			if (!$error instanceof Exception)
			{
				$error = new Exception($event . ': ' . ($error ? $error : 'Error'), 500);
			}

			throw $error;
		}

		return $model->getData();
	}

	/**
	 * Tries to extract the service from the given payload.
	 *
	 * @param 	array         $data   The appointment data to save.
	 * @param 	VAPIcalEvent  $event  The payload event.
	 *
	 * @return 	integer       The service ID.
	 */
	protected function findService($data, $event)
	{
		static $services = [];

		if (!isset($services[$data['id_employee']]))
		{
			// load all services supported by this employee
			$services[$data['id_employee']] = JModelVAP::getInstance('employee')->getServices($data['id_employee']);
		}

		// first of all check if we have a service with the name
		// contained into the summary or in the description
		foreach ($services[$data['id_employee']] as $service)
		{
			if (stripos((string) $event->summary, $service->name) !== false || stripos((string) $event->description, $service->name))
			{
				// name found, return service ID
				return (int) $service->id;
			}
		}

		// otherwise look for the first service with matching duration
		foreach ($services[$data['id_employee']] as $service)
		{
			if ($service->duration == $data['duration'])
			{
				// matching duration, return service ID
				return (int) $service->id;
			}
		}

		// return first available service as fallback
		return $services[$data['id_employee']] ? (int) $services[$data['id_employee']][0]->id : 0;
	}

	/**
	 * Detects the events that have been manually removed and internally takes action.
	 * 
	 * @param   VAPIcalEvent[]  $events  The available events.
	 * @param   bool            $delete  Whether the appointments should be permanently
	 *                                   deleted or whether the status should be updated.
	 * 
	 * @return  int[]           A list of cancelled appointments.
	 * 
	 * @since   1.7.5
	 */
	public function cancel(array $events, bool $delete = false)
	{
		if (!$this->calendarHash)
		{
			// calendar hash not provided, do not go ahead
			return [];
		}

		// load reservation model
		$model = JModelVAP::getInstance('reservation');

		if (!$model)
		{
			// an error occurred, model not found...
			throw new Exception('Missing reservation model', 500);
		}

		// take only the ID of the imported events
		$events = array_map(function($event) {
			return $event->uid;
		}, $events);

		$deletedEvents = [];

		// iterate the reservations one by one
		foreach ($this->getCalendarAppointments() as $internalEvent)
		{
			if (in_array($internalEvent->icaluid, $events))
			{
				// the event is still available on the remote calendar
				continue;
			}

			// the event is no longer available on the remote calendar...
			if ($delete)
			{
				// permanently delete the appointment
				$result = $model->delete($internalEvent->id);
			}
			else
			{
				$result = $model->save([
					'id'      => $internalEvent->id,
					'icaluid' => '', // clear the reference to the remote ical ID
					'status'  => JHtml::fetch('vaphtml.status.cancelled', 'appointments', 'code'),
					// register a note about the status change
					'status_comment' => 'VAP_STATUS_CHANGED_ON_ICAL_DELETE',
				]);
			}

			if ($result)
			{
				$deletedEvents[] = $internalEvent->id;
			}
			else
			{
				// get last error
				$error = $model->getError();

				if (!$error instanceof Exception)
				{
					$error = new Exception($error);
				}

				$this->setError($error);
			}
		}

		return $deletedEvents;
	}

	/**
	 * Returns all the appointments that have been imported from the
	 * same resource specified in the importer constructor.
	 * 
	 * @return  object[]
	 * 
	 * @since   1.7.5
	 */
	protected function getCalendarAppointments()
	{
		$db = JFactory::getDbo();

		// take all the reservations imported from the specified source
		$query = $db->getQuery(true)
			->select($db->qn(['id', 'icaluid']))
			->from($db->qn('#__vikappointments_reservation'))
			->where($db->qn('icalhash') . ' = ' . $db->q($this->calendarHash))
			// the check-in must be in the future
			->where($db->qn('checkin_ts') . ' > ' . $db->q(JFactory::getDate()->toSql()));

		// get any approved codes
		$approved = JHtml::fetch('vaphtml.status.find', 'code', ['appointments' => 1, 'approved' => 1]);

		if ($approved)
		{
			// the status must be approved
			$query->where($db->qn('status') . ' IN (' . implode(',', array_map(array($db, 'q'), $approved)) . ')');
		}

		$db->setQuery($query);
		return $db->loadObjectList();
	}
}
