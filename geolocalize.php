<?php
/**
 *
 * @package	Joomla
 * @subpackage	JEA
 * @copyright	Copyright (C) 2011 PHILIP Sylvain. All rights reserved.
 * @license		GNU/GPL, see LICENSE.txt
 * Joomla Estate Agency is free software. This version may have been modified pursuant to the
 * GNU General Public License, and as distributed it includes or is derivative
 * of works licensed under the GNU General Public License or other free or open
 * source software licenses.
 *
 */

defined( '_JEXEC' ) or die( 'Restricted access' );

jimport('joomla.event.plugin');

/**
 * Geolocalize JEA Plugin
 *
 * @package		Joomla
 * @subpackage	JEA
 * @since 		1.5
 */
class plgJeaGeolocalize extends JPlugin
{

    /**
     * onBeforeSaveProperty method
     *
     * Called before $row property save.
     *
     * @param string $namespace
     * @param TableProperties $row
     * @param boolean $is_new
     * @return boolean
     */
    public function onBeforeSaveProperty($namespace, $row, $is_new)
    {
        if (empty($row->latitude) || empty($row->longitude)) {

            $db = JFactory::getDbo();
            $language = JFactory::getLanguage();
            $langs  = explode('-', $language->getTag());

            $lang   = $langs[0];
            $region = $langs[1];

            $location = array();
            if (!empty($row->address)) {
                $location[] = $row->address;
            }
            if (!empty($row->town_id)) {
                $db->setQuery('SELECT `value` FROM #__jea_towns WHERE id = '. (int) $row->town_id);
                $town = $db->loadResult();
                if (!empty($town)) {
                    $location[] = $town;
                }
            }
            if (!empty($region)) {
                $location[] = $region;
            } else {
                $location[] = $lang;
            }

            $response = $this->getGeocodeFromGoogle(implode(', ', $location));
            if (!empty($response->results)) {
                $coords = $response->results[0]->geometry->location;

                $row->latitude  = $coords->lat;
                $row->longitude = $coords->lng;
            } else {
                JFactory::getApplication()->enqueueMessage('Geolocalisation not found for this location : '. implode(', ', $location), 'warning');
            }
        }
        return true;
    }


    /**
     * Retrieves Geocoding information from Google
     * @param string $location address, city, state, etc.
     * @return \stdClass
     */
    protected function getGeocodeFromGoogle($location) {
        $url = 'http://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($location).'&sensor=false';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return json_decode(curl_exec($ch));
    }

}
