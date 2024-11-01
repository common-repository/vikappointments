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

VAPLoader::import('libraries.backup.import.rule');

/**
 * Wraps the instructions used to restore a backup.
 * 
 * @since 1.7.1
 */
class VAPBackupImportDirector
{
	/**
	 * The path of the backup (folder).
	 * 
	 * @var string
	 */
	private $path;

	/**
	 * The minimum requireed version to use while restoring a backup.
	 * 
	 * @var string
	 */
	private $version;

	/**
	 * Class constructor.
	 * 
	 * @param 	string 	$path  The archive path.
	 */
	public function __construct($path)
	{
		$this->path = rtrim($path, DIRECTORY_SEPARATOR);
	}

	/**
	 * Sets the version of the manifest.
	 * 
	 * @param 	string  $version
	 * 
	 * @return 	self    This object to support chaining.
	 */
	public function setVersion($version)
	{
		$this->version = $version;

		return $this;
	}

	/**
	 * Executes the restore process.
	 * 
	 * @return 	void
	 * 
	 * @throws 	Exception
	 */
	public function process()
	{
		// obtain manifest object
		$manifest = $this->parseManifest();

		// make sure the backup version is compatible with the current one
		if (!$this->validateVersion($manifest))
		{
			// the backup version is higher than the current one
			throw new Exception('The backup version is not compatible with the current one.', 500);
		}

		$dispatcher = VAPFactory::getEventDispatcher();

		/**
		 * Trigger event to allow third party plugins to extend the backup import.
		 * This hook triggers before processing the import of an existing backup.
		 * 
		 * It is possible to throw an exception to prevent the import process.
		 * 
		 * @param 	object  $manifest  The backup manifest.
		 * @param 	string  $path      The path of the backup archive (uncompressed).
		 * 
		 * @return 	void
		 * 
		 * @since 	1.7.1
		 * 
		 * @throws 	Exception
		 */
		$dispatcher->trigger('onBeforeImportBackupVikAppointments', [$manifest, $this->path]);

		// execute the uninstallation rules
		$this->uninstall($manifest);

		// execute the installation rules
		$this->install($manifest);

		/**
		 * Trigger event to allow third party plugins to extend the backup import.
		 * This hook triggers after processing the import of an existing backup.
		 * 
		 * It is possible to throw an exception to prevent the import process.
		 * 
		 * @param 	object  $manifest  The backup manifest.
		 * @param 	string  $path      The path of the backup archive (uncompressed).
		 * 
		 * @return 	void
		 * 
		 * @since 	1.7.1
		 * 
		 * @throws 	Exception
		 */
		$dispatcher->trigger('onAfterImportBackupVikAppointments', [$manifest, $this->path]);
	}

	/**
	 * Helper method used to parse the manifest file contained
	 * within the backup archive.
	 * 
	 * @return 	object
	 * 
	 * @throws 	Exception
	 */
	protected function parseManifest()
	{
		// create file 
		$file = $this->path . DIRECTORY_SEPARATOR . 'manifest.json';

		// make sure the manifest exists
		if (!JFile::exists($file))
		{
			// file not found, check whether the package was manually compressed
			// on a desktop, where we should face a folder-in-folder behavior
			$folders = JFolder::folders($this->path);

			if ($folders)
			{
				// move the root within the first available folder
				$this->path .= DIRECTORY_SEPARATOR . $folders[0];

				// refresh manifest file path
				$file = $this->path . DIRECTORY_SEPARATOR . 'manifest.json';
			}
		}

		// make sure the manifest exists
		if (!JFile::exists($file))
		{
			// manifest not found
			throw new Exception('The backup does not include a manifest file', 404);
		}

		$manifest = '';

		// open file pointer
		$fp = fopen($file, 'r');

		while (!feof($fp))
		{
			// read buffer
			$manifest .= fread($fp, 8192);
		}

		// close file pointer
		fclose($fp);

		// decode the manifest
		$manifest = json_decode($manifest);

		if (!is_object($manifest))
		{
			throw new Exception('The backup manifest is not valid', 500);
		}

		return $manifest;
	}

	/**
	 * Checks whether the version of the backup is compatible with the current
	 * version of the software.
	 * 
	 * @param 	object   $manifest  The backup manifest.
	 * 
	 * @return 	boolean  True if compatible, false otherwise.
	 */
	protected function validateVersion($manifest)
	{
		if (empty($manifest->application) || $manifest->application != 'Vik Appointments')
		{
			// application is missing
			return false;
		}

		$application = $manifest->application;

		// get the identifier of the current platform
		$platform = VersionListener::getPlatform();

		// check whether the manifest specifies a custom version for the current platform
		if (isset($manifest->platforms->{$platform}))
		{
			// append platform to program name
			$application .= ' ' . $platform;

			// override manifest with specific instructions for the current platform
			$manifest = $manifest->platforms->{$platform};
		}

		if (empty($manifest->version))
		{
			// version not found
			return false;
		}

		// check whether the version signature has been specified by the backup
		if (isset($manifest->signature))
		{
			// validate version integrity
			$signature = md5($application . ' ' . $manifest->version);

			if ($signature !== $manifest->signature)
			{
				// the signature doesn't matche
				return false;
			}
		}

		// first of all, make sure the backup version is equals or higher than the
		// minimum required version
		if (version_compare($manifest->version, $this->version, '<'))
		{
			// the manifest version is lower than the minimum required version
			return false;
		}

		// then check whether the current version is equals of higher than the backup version
		return version_compare(VIKAPPOINTMENTS_SOFTWARE_VERSION, $manifest->version, '>=');
	}

	/**
	 * Executes the uninstallation queries.
	 * 
	 * @param 	object   $manifest  The backup manifest.
	 * 
	 * @return 	void
	 */
	protected function uninstall($manifest)
	{
		// look for uninstall queries
		if (!isset($manifest->uninstall))
		{
			// nothing to execute
			return;
		}

		$dbo = JFactory::getDbo();

		// iterate queries to clean any existing records
		foreach ((array) $manifest->uninstall as $q)
		{
			// launch query
			$dbo->setQuery($q);
			$dbo->execute();
		}
	}

	/**
	 * Executes the installation rules.
	 * 
	 * @param 	object   $manifest  The backup manifest.
	 * 
	 * @return 	void
	 */
	protected function install($manifest)
	{
		// look for installers
		if (!isset($manifest->installers))
		{
			// nothing to install...
			return;
		}

		$dispatcher = VAPFactory::getEventDispatcher();

		// iterate installers
		foreach ((array) $manifest->installers as $install)
		{
			if (empty($install->role))
			{
				// install role not found, cannot proceed
				throw new Exception('Missing import backup role', 500);
			}

			// extract import data from rule
			$data = isset($install->data) ? $install->data : null;

			/**
			 * Trigger event to allow third party plugins to implement at runtime new import backup rules.
			 * 
			 * It is possible to throw an exception to abort the import process.
			 * 
			 * @param 	string   $role      The identifier of the import rule.
			 * @param 	mixed    $options   The instructions of the backup import rule.
			 * 
			 * @return 	boolean  True in case the rule has been dispatched, false (or null) to let the 
			 *                   system uses one of the pre-installed rules.
			 * 
			 * @since 	1.7.1
			 * 
			 * @throws 	Exception
			 */
			$executed = $dispatcher->is('onExecuteImportBackupRuleVikAppointments', [$install->role, $data]);

			// check whether the rule has been already dispatched by a plugin
			if (!$executed)
			{
				// dispatch one of the system rules
				$this->loadRule($install->role)->execute($data);
			}
		}
	}

	/**
	 * Creates a new import rule.
	 * 
	 * @param 	string 	$rule  The identifier of the rule to create.
	 * 
	 * @return 	VAPBackupImportRule
	 * 
	 * @throws 	Exception
	 */
	public function loadRule($rule)
	{
		// attempt to load the import rule
		if (!VAPLoader::import('libraries.backup.import.rule.' . $rule))
		{
			// rule not found
			throw new Exception(sprintf('Cannot load [%s] import rule', $rule), 404);
		}

		// build class name
		$classname = preg_replace("/_/", ' ', $rule);
		$classname = preg_replace("/\s+/", '', ucwords($classname));
		$classname = 'VAPBackupImportRule' . $classname;

		// make sure the rule class exists
		if (!class_exists($classname))
		{
			// class not found
			throw new Exception(sprintf('Cannot find [%s] import rule class', $classname), 404);
		}

		// create new rule
		$rule = new $classname($this->path);

		// make sure we have a valid instance
		if (!$rule instanceof VAPBackupImportRule)
		{
			throw new Exception(sprintf('The import rule [%s] is not a valid instance', $classname), 500);
		}

		// create the rule
		return $rule;
	}
}