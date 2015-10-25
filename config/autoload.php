<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2013 Leo Feyer
 *
 * @package Sensor
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

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
// <version>1.1</version>
// <email>vanesp@escurio.com</email>
// <date>2015-10-23</date>

// Version 1.0, 2013-12-06 - changes for Contao 3.1.2
// Version 1.1, 2015-10-23 - Added ModuleGas

/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Classes
	'Contao\Accumulate'          => 'system/modules/sensor/classes/Accumulate.php',

	// Models

	// Modules
	'Contao\ModuleLocations'      => 'system/modules/sensor/modules/ModuleLocations.php',
	'Contao\ModuleElectricity'     => 'system/modules/sensor/modules/ModuleElectricity.php',
	'Contao\ModuleGas'     => 'system/modules/sensor/modules/ModuleGas.php',
	'Contao\ModuleSensors'     => 'system/modules/sensor/modules/ModuleSensors.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'mod_customer_detail'        => 'system/modules/sensor/templates',
	'mod_sensor_error'           => 'system/modules/sensor/templates',
	'mod_elec_sensors'         => 'system/modules/sensor/templates',
	'mod_elec'         => 'system/modules/sensor/templates',
	'mod_gas_sensors'         => 'system/modules/sensor/templates',
	'mod_gas'         => 'system/modules/sensor/templates',
	'mod_reports'       => 'system/modules/sensor/templates',
	'mod_reports_error'     => 'system/modules/sensor/templates',
	'mod_stat_detail1'       => 'system/modules/sensor/templates',
	'mod_stat_detail3'     => 'system/modules/sensor/templates',
	'mod_sensor_detail'       => 'system/modules/sensor/templates',
	'mod_sensors'          => 'system/modules/sensor/templates',
	'mod_stat_detail_save'      => 'system/modules/sensor/templates',
	'mod_location_detail'      => 'system/modules/sensor/templates',
));
