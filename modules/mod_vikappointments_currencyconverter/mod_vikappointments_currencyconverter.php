<?php
/**
 * @package     VikAppointments
 * @subpackage  mod_vikappointments_currencyconverter
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2024 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access to this file
defined('ABSPATH') or die('No script kiddies please!');

// require autoloader
if (defined('JPATH_SITE') && JPATH_SITE !== 'JPATH_SITE')
{
	require_once implode(DIRECTORY_SEPARATOR, array(JPATH_SITE, 'components', 'com_vikappointments', 'helpers', 'libraries', 'autoload.php'));
}

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'libraries' . DIRECTORY_SEPARATOR . 'helper.php';

$vik = VAPApplication::getInstance();

// load CSS environment
JHtml::fetch('vaphtml.assets.environment');

$vik->addStyleSheet(VAPASSETS_URI . 'css/input-select.css');

// load custom CSS file
JHtml::fetch('vaphtml.assets.customcss');

// since jQuery is a required dependency, the framework should be 
// invoked even if jQuery is disabled
$vik->loadFramework('jquery.framework');

JHtml::fetch('vaphtml.assets.select2');

// get supported currencies
$currencies = VikAppointmentsCurrencyConverterHelper::getCurrencies($params);

// get user currency
$userCurrency = VikAppointmentsCurrencyConverterHelper::getUserCurrency();

// get module ID
$module_id = VikAppointmentsCurrencyConverterHelper::getID($module);

// load specified layout
require JModuleHelper::getLayoutPath('mod_vikappointments_currencyconverter', $params->get('layout'));
