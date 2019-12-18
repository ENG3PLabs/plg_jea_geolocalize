<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  Jea.Geolocalize
 *
 * @copyright   Copyright (C) 2007 - 2019 PHILIP Sylvain. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

/**
 * Geolocalize JEA Plugin
 */
class plgJeaGeolocalize extends JPlugin
{
	/**
	 * onBeforeSaveProperty method
	 *
	 * @param  string         $namespace  The form namespace
	 * @param  TableProperty  $row        The property Db row instance
	 * @param  boolean        $is_new     True if the property is new
	 *
	 * @return boolean  True on success
	 */
	public function onBeforeSaveProperty ($namespace, $row, $is_new)
	{
		if (empty($row->latitude) || empty($row->longitude))
		{
			$db = JFactory::getDbo();
			$language = JFactory::getLanguage();
			$langs = explode('-', $language->getTag());
			$lang = $langs[0];
			$region = $langs[1];
			$location = array();

			if (!empty($row->address))
			{
				$location[] = $row->address;
			}

			if (!empty($row->town_id))
			{
				$db->setQuery('SELECT `value` FROM #__jea_towns WHERE id = ' . (int) $row->town_id);
				$town = $db->loadResult();

				if (!empty($town))
				{
					$location[] = $town;
				}
			}

			$location[] = empty($region) ? $lang : $region;

			$response = $this->getGeocodeFromGoogle(implode(', ', $location));

			if (!empty($response->results))
			{
				$coords = $response->results[0]->geometry->location;

				$row->latitude = $coords->lat;
				$row->longitude = $coords->lng;
			}
			else
			{
				JFactory::getApplication()->enqueueMessage('Geolocalisation not found for this location : ' . implode(', ', $location), 'warning');
			}
		}

		return true;
	}

	/**
	 * Retrieves Geocoding information from Google
	 *
	 * @param   string  $location  address, city, state, etc.
	 * @return  \stdClass
	 */
	protected function getGeocodeFromGoogle ($location)
	{
		$params = JComponentHelper::getParams('com_jea');
		$key = $params->get('googlemap_api_key', '');

		$url = 'https://maps.googleapis.com/maps/api/geocode/json?key=' . $key . '&address=' . urlencode($location);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		return json_decode(curl_exec($ch));
	}
}
