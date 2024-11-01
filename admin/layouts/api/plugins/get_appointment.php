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
 * Layout variables
 * -----------------
 * @var  VAPApiEvent  $plugin  The instance of the event.
 */
extract($displayData);

JHtml::fetch('formbehavior.chosen');

$vik = VAPApplication::getInstance();

// create base URI
$uri = 'index.php?option=com_vikappointments&task=api&event=' . $plugin->getName();
$uri = $vik->routeForExternalUse($uri);

?>

<p>This plugin can be used to fetch the details of a specific appointment.</p>

<h3>Usage</h3>

<pre>
<strong>End-Point URL</strong>
<?php echo $uri; ?>


<strong>Params</strong>
username    (string)    The username of the application.
password    (string)    The password of the application.
id          (integer)   The ID of the appointment to fetch.
</pre>

<br />

<h3>Generate Ping URL</h3>

<div style="margin-bottom: 10px;" class="form-with-select">

	<select id="plg-login">
		<?php echo JHtml::fetch('select.options', JHtml::fetch('vaphtml.admin.apilogins', true)); ?>
	</select>

	<input type="text" id="plg-oid" placeholder="ID" />

</div>

<pre id="plgurl">

</pre>

<br />

<h3>Successful Response (JSON)</h3>

<pre>
{
    "id": 1,
    "sid": "ABCD1234EFGH5678",
    "status": "C",
    "statusRole": "APPROVED",
    ...
    "appointments": [
    	...
    ],
    "billing": {
    	...
    }
}
</pre>

<br />

<h3>Failure Response (JSON)</h3>

<pre>
{
    "errcode": 400,
    "error": "The reason of the error"
}
</pre>

<script>

	(function($) {
		'use strict';

		$(function() {
			VikRenderer.chosen('.form-with-select');
			
			$('#plg-login, #plg-oid').on('change', function() {
				var clean = '<?php echo $uri; ?>';

				var login = $('#plg-login').val().split(/;/);

				clean += '&username=' + login[0];
				clean += '&password=' + (login[1] ? login[1] : '');
				clean += '&args[id]=' + $('#plg-oid').val();

				var url = encodeURI(clean);

				$("#plgurl").html('<a href="' + url + '" target="_blank">' + clean + '</a>');
			});

			$('#plg-login').trigger('change');
		});
	})(jQuery);

</script>
