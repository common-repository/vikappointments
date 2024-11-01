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
 * Interface used to handle mail templates entities.
 *
 * @since 1.7
 */
interface VAPMailTemplate
{
	/**
	 * Returns the code of the template before being parsed.
	 *
	 * @param 	string  $file  An optional template file to use. If not specified,
	 *                         the one set in configuration will be used.
	 *
	 * @return 	void
	 */
	public function setFile($file);

	/**
	 * Returns the code of the template before being parsed.
	 *
	 * @return 	string
	 */
	public function getTemplate();

	/**
	 * Fetches the subject to be used in the e-mail.
	 *
	 * @return 	string
	 */
	public function getSubject();

	/**
	 * Parses the HTML of the template and returns it.
	 *
	 * @return 	string
	 */
	public function getHtml();

	/**
	 * Sends the HTML contents via e-mail.
	 *
	 * @return 	boolean
	 */
	public function send();

	/**
	 * Checks whether the notification should be sent.
	 *
	 * @return 	boolean
	 */
	public function shouldSend();
}
