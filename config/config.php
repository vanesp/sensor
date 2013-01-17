<?php

// <copyright> Copyright (c) 2012-2013 All Rights Reserved,
// Escurio BV
// http://www.escurio.com/
//
// THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY 
// KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
// IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
// PARTICULAR PURPOSE.
//
// </copyright>
// <author>Peter van Es</author>
// <version>1.0</version>
// <email>vanesp@escurio.com</email>
// <date>2012-07-27</date>
// <summary>config.php defines the sensor module in contao</summary>

if (!defined('TL_ROOT')) die('You can not access this file directly!');

// Backend-Module
array_insert($GLOBALS['BE_MOD']['Sensor'], 0,
	array (
		'Customers' => array 
		(
			// PvE: SampleDescriptions added 11-10-2011
			'tables' => array('Customer', 'Location'),
			'icon' => 'system/modules/sensor/html/customer16.png'
		),

		'Sensors' => array 
		(
			'tables' => array('Sensor', 'Actionlog'),
			'icon' => 'system/modules/sensor/html/sensor16.png'
		),

		'Actions' => array 
		(
			'tables' => array('Actions'),
			'icon' => 'system/modules/sensor/html/Dashboard.png'
		),

	 )
);

// Frontend-Module
array_insert($GLOBALS['FE_MOD']['Sensor'], 0, array
(
	'Sensors'  => 'ModuleSensors',
	'Customers' => 'ModuleCustomers',
	'Locations' => 'ModuleLocations',
    'Statistics'   => 'ModuleStatistics',
    'Reports'   => 'ModuleReports',
));

/**
 * Cron jobs
 */
$GLOBALS['TL_CRON']['hourly'][] = array('Accumulate', 'Statistics');
?>
