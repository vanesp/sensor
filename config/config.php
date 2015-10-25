<?php

// <copyright> Copyright (c) 2012-2014 All Rights Reserved,
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
// <version>2.0</version>
// <email>vanesp@escurio.com</email>
// <date>2013-12-15</date>
// <summary>config.php defines the sensor module in contao</summary>

// Backend-Module
array_insert($GLOBALS['BE_MOD']['sensor'], 0,
	array (
		'Customers' => array 
		(
			// PvE: SampleDescriptions added 11-10-2011
			'tables' => array('Customer', 'Location'),
			'icon' => 'system/modules/sensor/assets/customer16.png'
		),

		'Sensors' => array 
		(
			'tables' => array('Sensor', 'Actionlog'),
			'icon' => 'system/modules/sensor/assets/sensor16.png'
		),

		'Switches' => array 
		(
			'tables' => array('Switch', 'Actionlog'),
			'icon' => 'system/modules/sensor/assets/Switch16.png'
		),

		'Actions' => array 
		(
			'tables' => array('Actions'),
			'icon' => 'system/modules/sensor/assets/Dashboard.png'
		),

	 )
);

// Frontend-Module
array_insert($GLOBALS['FE_MOD']['sensor'], 0, array
(
	'Sensors'  => 'ModuleSensors',
	'Electricity' => 'ModuleElectricity',
	'Gas' => 'ModuleGas',
	'Locations' => 'ModuleLocations',
));

/**
 * Cron jobs
 */
$GLOBALS['TL_CRON']['hourly'][] = array('Accumulate', 'Statistics');

?>
