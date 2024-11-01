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
 * Currency helper cacheable trait.
 *
 * @since 1.7.6
 */
trait VAPCurrencyHelperCacheable
{
	/**
	 * Helper method used to internally cache a resource.
	 * The buffer will be always cached within the following folder:
	 * .../site/assets/cache/currency/
	 * 
	 * @param   string    $name      The name of the file to cache.
	 * @param   int       $lifetime  How long the cache file is considered valid (in seconds).
	 * @param   callable  $proxy     The callback to invoke when the cache cannot be pulled.
	 * 
	 * @return  string    The cached buffer.
	 */
	public function getCached(string $name, int $lifetime, $proxy)
	{
		// set up base path for cached files
		$path = VAPBASE . '/assets/cache/currency/';

		try
		{
			// make sure the folder exists, otherwise attempt to create it
			if (!JFolder::exists($path) && !JFolder::create($path))
			{
				// cache folder must be created manually...
				throw new RuntimeException('Unable to create the cache folder: ' . $path);
			}

			if (!JFile::exists($path . '/' . $name))
			{
				// response not cached yet
				throw new RuntimeException('The cached file does not exist');
			}

			// fetch last-modify timestamp
			$lastmodify = filemtime(JPath::clean($path . '/' . $name));

			if (time() - $lastmodify > $lifetime)
			{
				// the cache file is expired
				throw new RuntimeException('Cache file expired');
			}

			// read the contents from the cached file
			return file_get_contents(JPath::clean($path . '/' . $name));
		}
		catch (Exception $error)
		{
			// cache not found or expired, execute callback
			$response = call_user_func($proxy);

			// internally cache the response (do not care about the result)
			JFile::write($path . '/' . $name, (string) $response);

			// return the response to the caller
			return $response;
		}
	}
}
