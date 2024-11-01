<?php
/** 
 * @package     VikAppointments - Libraries
 * @subpackage  system
 * @author      E4J s.r.l.
 * @copyright   Copyright (C) 2021 E4J s.r.l. All Rights Reserved.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 * @link        https://vikwp.com
 */

// No direct access
defined('ABSPATH') or die('No script kiddies please!');

VikAppointmentsLoader::import('update.manager');
VikAppointmentsLoader::import('update.license');

/**
 * Class used to handle the activation, deactivation and 
 * uninstallation of VikAppointments plugin.
 *
 * @since 1.0
 */
class VikAppointmentsInstaller
{
	/**
	 * Flag used to init the class only once.
	 *
	 * @var boolean
	 */
	protected static $init = false;

	/**
	 * Initialize the class attaching wp actions.
	 *
	 * @return 	void
	 */
	public static function onInit()
	{
		// init only if not done yet
		if (static::$init === false)
		{
			// handle installation message
			add_action('admin_notices', array('VikAppointmentsInstaller', 'handleMessage'));

			/**
			 * Register hooks and actions here
			 */

			// mark flag as true to avoid init it again
			static::$init = true;
		}
	}

	/**
	 * Handles the activation of the plugin.
	 *
	 * @param 	boolean  $message 	True to display the activation message,
	 * 								false to ignore it.
	 *
	 * @return 	void
	 */
	public static function activate($message = true)
	{
		// get installed software version
		$version = get_option('vikappointments_software_version', null);

		// check if the plugin has been already installed
		if (is_null($version))
		{
			// dispatch UPDATER to launch installation queries
			VikAppointmentsUpdateManager::install();

			// mark the plugin has installed to avoid duplicated installation queries
			update_option('vikappointments_software_version', VIKAPPOINTMENTS_SOFTWARE_VERSION);
		}

		if ($message)
		{
			// set activation flag to display a message
			add_option('vikappointments_onactivate', 1);
		}
	}

	/**
	 * Handles the deactivation of the plugin.
	 *
	 * @return 	void
	 */
	public static function deactivate()
	{
		// do nothing for the moment
	}

	/**
	 * Handles the uninstallation of the plugin.
	 *
	 * @param 	boolean  $drop 	True to drop the tables of VikAppointments from the database.
	 *
	 * @return 	void
	 */
	public static function uninstall($drop = true)
	{
		// dispatch UPDATER to drop database tables
		VikAppointmentsUpdateManager::uninstall($drop);

		// delete installation flag
		delete_option('vikappointments_software_version');
	}

	/**
	 * Handles the uninstallation of the plugin.
	 * Proxy for uninstall method which always force database drop.
	 *
	 * @return 	void
	 *
	 * @uses 	uninstall()
	 */
	public static function delete()
	{
		// complete uninstallation by dropping the database
		static::uninstall(true);
	}

	/**
	 * Checks if the current version should be updated
	 * and, eventually, processes it.
	 * 
	 * @return 	void
	 */
	public static function update()
	{
		// get installed software version
		$version = get_option('vikappointments_software_version', null);

		$app = JFactory::getApplication();

		// check if we are running an older version
		if (VikAppointmentsUpdateManager::shouldUpdate($version))
		{
			// avoid useless redirections if doing ajax
			if (!wp_doing_ajax() && $app->isAdmin() && JFactory::getUser()->authorise('core.admin', 'com_vikappointments'))
			{
				// Turn on maintenance mode before running the update.
				// In case the maintenance mode was already active, then
				// an error message will be thrown.
				static::setMaintenance(true);

				// process the update (we don't need to raise an error)
				VikAppointmentsUpdateManager::update($version);

				// update cached plugin version
				update_option('vikappointments_software_version', VIKAPPOINTMENTS_SOFTWARE_VERSION);

				// update internal configuration version
				VAPFactory::getConfig()->set('version', VIKAPPOINTMENTS_SOFTWARE_VERSION);

				// deactivate the maintenance mode on update completion
				static::setMaintenance(false);

				/**
				 * Check if pro version, but attempt to re-download the Pro settings
				 * within the current loading flow rather than redirecting. In case
				 * something goes wrong, fallback to the old "get pro" redirect method.
				 * 
				 * @since 1.2.14
				 */
				if (VikAppointmentsLicense::isPro())
				{
					// load license model
					$model = JModel::getInstance('vikappointments', 'license', 'admin');

					// download PRO version hoping that all will go fine
					$result = $model->download(VikAppointmentsLicense::getKey());

					if ($result === false)
					{
						// an error occurred, retrieve it as exception
						$error = $model->getError(null, $toString = true);

						// display exception error
						$app->enqueueMessage($error, 'error');

						// fallback to the pro-package download page (old method)
						$app->redirect('index.php?option=com_vikappointments&view=getpro&version=' . $version);
						$app->close();
					}
				}
			}
		}
		// check if the current instance is a new blog of a network
		else if (is_null($version))
		{
			/**
			 * The version is NULL, vikappointments_software_version doesn't
			 * exist as an option of this blog.
			 * We need to launch the installation manually.
			 *
			 * @see activate()
			 */

			// Use FALSE to ignore the activation message
			static::activate(false);
		}
	}

	/**
	 * Callback used to complete the update of the plugin
	 * made after a scheduled event.
	 *
	 * @param 	array  $results  The results of all attempted updates.
	 *
	 * @return 	void
	 *
	 * @since 	1.1.11
	 */
	public static function automaticUpdate($results)
	{
		// create log trace
		$trace = '### VikAppointments Automatic Update | ' . JHtml::fetch('date', new JDate(), 'Y-m-d H:i:s') . "\n\n";
		$trace .= "```json\n" . json_encode($results, JSON_PRETTY_PRINT) . "\n```\n\n";

		// iterate all plugins
		foreach ($results['plugin'] as $plugin)
		{
			if (!empty($plugin->item->slug))
			{
				// register check trace
				$trace .= "Does `{$plugin->item->slug}` match `vikappointments`?\n\n";

				// make sure the plugin slug matches this one
				if ($plugin->item->slug == 'vikappointments')
				{
					// register status trace
					$trace .= "Did WP complete the update without errors? [" . ($plugin->result ? 'Y' : 'N') . "]\n\n";

					// plugin found, make sure the update was successful
					if ($plugin->result)
					{
						try
						{
							// register version trace
							$trace .= sprintf("Updating from [%s] to [%s]...\n\n", VIKAPPOINTMENTS_SOFTWARE_VERSION, $plugin->item->new_version);

							// complete the update in background
							static::backgroundUpdate($plugin->item->new_version);

							// update completed without errors
							$trace .= "Background update completed\n\n";
						}
						catch (Exception $e)
						{
							// something went wrong, register error within the trace
							$trace .= sprintf(
								"An error occurred while trying to finalize the update (%d):\n> %s\n\n",
								$e->getCode(),
								$e->getMessage()
							);

							/**
							 * @todo An error occurred while trying to download the PRO version,
							 *       evaluate to send an e-mail to the administrator.
							 */
						}
					}
				}
			}
		}

		// register debug trace within a log file
		JLoader::import('adapter.filesystem.file');
		JFile::write(VIKAPPOINTMENTS_BASE . DIRECTORY_SEPARATOR . 'au-log.md', $trace . "---\n\n");
	}

	/**
	 * Same as update task, but all made in background.
	 *
	 * @param 	string  $new_version  The new version of the plugin.
	 * 
	 * @return 	void
	 *
	 * @since 	1.1.11
	 */
	protected static function backgroundUpdate($new_version)
	{
		// get installed software version
		$version = get_option('vikappointments_software_version', null);

		// DO NOT use shouldUpdate method because, since we are always within
		// the same flow, the version constant is still referring to the previous
		// version. So, always assume to proceed with the update of the plugin.

		// Turn on maintenance mode before running the update.
		// In case the maintenance mode was already active, then
		// an error message will be thrown.
		static::setMaintenance(true);

		// process the update (we don't need to raise an error)
		VikAppointmentsUpdateManager::update($version);

		// update cached plugin version
		update_option('vikappointments_software_version', $new_version);

		// update internal configuration version
		VAPFactory::getConfig()->set('version', $new_version);

		// deactivate the maintenance mode on update completion
		static::setMaintenance(false);

		// check if pro version
		if (VikAppointmentsLicense::isPro())
		{
			// load license model
			$model = JModel::getInstance('vikappointments', 'license', 'admin');

			// download PRO version hoping that all will go fine
			$result = $model->download(VikAppointmentsLicense::getKey());

			if ($result === false)
			{
				// an error occurred, retrieve it as exception
				$error = $model->getError(null, $toString = false);

				// propagate exception
				throw $error;
			}
		}
	}

	/**
	 * Checks whether the automatic updates should be turned off.
	 * This is useful to prevent auto-updates for those customers
	 * that are running an expired PRO version. This will avoid
	 * losing the files after an unexpected update.
	 *
	 * @param 	boolean  $update  The current auto-update choice.
	 * @param 	object   $item    The plugin offer.
	 *
	 * @return 	mixed    Null to let WP decides, false to always deny it.
	 *
	 * @since 	1.1.11
	 */
	public static function useAutoUpdate($update, $item)
	{
		// make sure we are fetching VikAppointments
		if (!empty($item->slug) && $item->slug == 'vikappointments')
		{
			// plugin found, lets check whether the user is
			// not running the PRO version
			if (!VikAppointmentsLicense::isPro())
			{
				// not a PRO version, check whether a license
				// key was registered
				if (VikAppointmentsLicense::getKey())
				{
					// The plugin registered a key; the customer
					// chose to let the license expires...
					// We need to prevent auto-updates.
					$update = false;
				}
			}
		}

		return $update;
	}

	/**
	 * Toggle maintenance mode for the site.
	 * Creates/deletes the maintenance file to enable/disable maintenance mode.
	 *
	 * @param 	boolean  $enable  True to enable maintenance mode, false to disable.
	 *
	 * @return 	void
	 *
	 * @since 	1.2
	 */
	protected static function setMaintenance($enable)
	{
		$maintenance_file_path = VIKAPPOINTMENTS_BASE . '/maintenance.txt';

		if ($enable)
		{
			/**
			 * Check if we are in maintenance mode.
			 * 
			 * @since 1.2.14  Go ahead in case the user clicked the "Retry" link.
			 */
			if (JFile::exists($maintenance_file_path) && JFactory::getApplication()->input->getBool('disable_maintenance_mode', false) == false)
			{
				// default die message
				$message = sprintf(
					'<h1>%s</h1><p>%s</p>',
					__('Maintenance'),
					__('VikAppointments plugin is in maintenance mode. Please wait for the update completion.', 'vikappointments')
				);

				$args = [
					// HTTP error code "locked"
					'code' => 423,
				];

				/**
				 * In case the update process is taking more than 5 minutes, warn the user
				 * and add the possibility to retry.
				 * 
				 * @since 1.2.14 
				 */
				if (filemtime($maintenance_file_path) < strtotime('-5 minutes'))
				{
					$uri = new JUri(JUri::current());
					$uri->setVar('disable_maintenance_mode', 1);

					// set link for the message to be displayed
					$args['link_url'] = (string) $uri;
					$args['link_text'] = __('Retry');

					// warn the user
					$message .= sprintf(
						'<p>%s<br />%s</p>',
						__('The update process is taking too long...', 'vikappointments'),
						__('Click the link below to retry the update process. Do not leave or refresh the page.', 'vikappointments')
					);
				}

				// raise error message in case the update process is currently running
				wp_die($message, __('Maintenance'), $args);
			}

			// ignore the maximum execution time to let the server safely completes the update
			ignore_user_abort(true);
			set_time_limit(0);

			// enter in maintenance mode for the current version
			JFile::write($maintenance_file_path, VIKAPPOINTMENTS_SOFTWARE_VERSION);
		}
		else
		{
			// turn off maintenance mode
			JFile::delete($maintenance_file_path);
		}
	}

	/**
	 * In case of an expired PRO version, prompts a message informing
	 * the user that it is going to lose the PRO features.
	 *
	 * @param  array  $data      An array of plugin metadata.
 	 * @param  array  $response  An array of metadata about the available plugin update.
	 *
	 * @return 	void
	 *
	 * @since 	1.1.11
	 */
	public static function getUpdateMessage($data, $response)
	{
		// check whether the user is not running the PRO version
		if (!VikAppointmentsLicense::isPro())
		{
			// not a PRO version, check whether a license
			// key was registered
			if (VikAppointmentsLicense::getKey())
			{
				// The plugin registered a key; the customer
				// chose to let the license expires...
				// We need to display an alert.
				add_action('admin_footer', function() use ($data, $response)
				{
					// display layout
					echo JLayoutHelper::render(
						'html.license.update',
						array($data, $response),
						null,
						array('component' => 'com_vikappointments')
					);
				});
			}
		}
	}

	/**
	 * Helper method used to obtain the list of breaking
	 * changes registered within the wordpress options.
	 *
	 * @return 	array
	 *
	 * @since 	1.2.13
	 */
	public static function getBreakingChanges()
	{
		// get existing breaking changes
		$files = get_option('vikappointments_breaking_changes');
		// decode from JSON
		return (array) ($files ? json_decode($files, true) : null);
	}

	/**
	 * Helper method used to register a list of breaking
	 * changes within the wordpress options.
	 *
	 * @param 	array 	$files  The files to register.
	 *
	 * @return 	void
	 *
	 * @since 	1.2.13
	 */
	public static function registerBreakingChanges(array $files)
	{
		if ($files)
		{
			// get existing files
			$existing = static::getBreakingChanges();

			foreach ($files as $client => $list)
			{
				if (!$list)
				{
					// ignore in case the list is empty
					continue;
				}

				if (!isset($existing[$client]))
				{
					// no client set, register it right now
					$existing[$client] = (array) $list;
				}
				else
				{
					// client already set, merge existing with new ones (get rid of duplicates)
					$existing[$client] = array_values(array_unique(array_merge($existing[$client], (array) $list)));
				}
			}

			// register within an option the breaking changes
			update_option('vikappointments_breaking_changes', json_encode($existing));
		}
	}

	/**
	 * Helper method used to unregister a list of breaking
	 * changes within the wordpress options.
	 *
	 * @param 	string|array|null  $files  The file(s) to unregister.
	 *                                     Leave empty to clear all the files.
	 *
	 * @return 	void
	 *
	 * @since 	1.2.13
	 */
	public static function unregisterBreakingChanges($files = null)
	{
		if ($files)
		{
			$existing = static::getBreakingChanges();

			// scan all the provided files
			foreach ((array) $files as $file)
			{
				// search path under each client
				foreach ($existing as $client => $list)
				{
					// get index of the specified file
					$index = array_search($file, $list);

					if ($index === false)
					{
						// path not found, ignore
						continue;
					}

					// remove path at the specified index
					array_splice($existing[$client], $index, 1);

					if (!$existing[$client])
					{
						// remove client as there are no more pending overrides
						unset($existing[$client]);
					}
				}
			}

			if ($existing)
			{
				// update breaking changes in case the list is not empty
				update_option('vikappointments_breaking_changes', json_encode($existing));
			}
			else
			{
				// directly delete the breaking changes option otherwise
				static::unregisterBreakingChanges();
			}
		}
		else
		{
			// clear breaking changes
			delete_option('vikappointments_breaking_changes');
		}
	}

	/**
	 * Helper method used to show the breaking changes to
	 * the administrator. This method will have no effect
	 * in case the logged-in user is not an administrator.
	 *
	 * @return 	void
	 *
	 * @since 	1.2.13
	 */
	public static function showBreakingChanges()
	{
		// make sure the user is an administrator
		if (JFactory::getUser()->authorise('core.admin', 'com_vikappointments'))
		{
			// retieve breaking changes list, if any
			$bc = static::getBreakingChanges();

			if ($bc)
			{
				// use layout to render the warning message
				$warn = JLayoutHelper::render(
					'html.overrides.bc',
					['files' => $bc],
					null,
					['component' => 'com_vikappointments']
				);

				// enqueue warning to be displayed
				JFactory::getApplication()->enqueueMessage($warn, 'warning');
			}
		}
	}

	/**
	 * Method used to check for any installation message to show.
	 *
	 * @return 	void
	 */
	public static function handleMessage()
	{
		$app = JFactory::getApplication();

		// if we are in the admin section and the plugin has been activated
		if ($app->isAdmin() && get_option('vikappointments_onactivate') == 1)
		{
			// delete the activation flag to avoid displaying the message more than once
			delete_option('vikappointments_onactivate');

			?>
			<div class="notice is-dismissible notice-success">
				<p>
					<strong>Thanks for activating our plugin!</strong>
					<a href="https://vikwp.com" target="_blank">https://vikwp.com</a>
				</p>
			</div>
			<?php
		}
	}
}
