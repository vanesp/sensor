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
// <summary>languages/en/Sensor.php defines the Sensor table</summary>

// Buttons
$GLOBALS['TL_LANG']['Sensor']['new']= array('New Sensor', 'Create a new Sensor');
$GLOBALS['TL_LANG']['Sensor']['edit']= array('Edit', 'Edit Sensor Actionlog');
$GLOBALS['TL_LANG']['Sensor']['editheader']= array('Edit', 'Edit Sensor %s');
$GLOBALS['TL_LANG']['Sensor']['delete']= array('Delete', 'Delete Sensor %s');
$GLOBALS['TL_LANG']['Sensor']['copy']= array('Copy', 'Copy Sensor %s');
$GLOBALS['TL_LANG']['Sensor']['show']= array('Show', 'Show Sensor %s');

// Legends
$GLOBALS['TL_LANG']['Sensor']['Sensor_legend']= 'Sensor';
$GLOBALS['TL_LANG']['Sensor']['monitoring_legend']= 'Monitoring details';
$GLOBALS['TL_LANG']['Sensor']['details_legend']= 'Sensor details';
$GLOBALS['TL_LANG']['Sensor']['limits_legend']= 'Alert limits';

// Fields
$GLOBALS['TL_LANG']['Sensor']['idsensor']= array('Sensor id', 'Enter the numeric identifier of this sensor (e.g. 1234)');
$GLOBALS['TL_LANG']['Sensor']['pid']= array('Location id', 'Select the customer location');
$GLOBALS['TL_LANG']['Sensor']['idroom']= array('idroom', 'Select the room id of the sensor (for RFB12 the node number)');
$GLOBALS['TL_LANG']['Sensor']['uid']= array('Owner', 'Select the user responsible for this sensor system');
$GLOBALS['TL_LANG']['Sensor']['location']= array('Location', 'Where this sensor is installed');
$GLOBALS['TL_LANG']['Sensor']['lobatt']= array('Battery low?', 'Status of battery, 1 means low');
$GLOBALS['TL_LANG']['Sensor']['comments']= array('Comments... Not for actionlog items!', 'Enter any special remarks for this sensor, e.g. maintenance info or GSM antenna location. No action log items!');
$GLOBALS['TL_LANG']['Sensor']['sensortype']= array('Type', 'Select the type of this sensor');
$GLOBALS['TL_LANG']['Sensor']['sensorquantity']= array('Quantity', 'Enter the quantity this sensor measures, e.g % humidity or C');
$GLOBALS['TL_LANG']['Sensor']['datastream']= array('Datastream', 'Enter the name of the datastream on https://cosm.com');
$GLOBALS['TL_LANG']['Sensor']['sensorscale']= array('Scale', 'Enter the number the raw value should be multiplied with to get the quantity');

$GLOBALS['TL_LANG']['Sensor']['cum_gas_pulse']= array('Gas pulse counts', 'Cumulative pulse counts for a gas meter');
$GLOBALS['TL_LANG']['Sensor']['cum_water_pulse']= array('Water pulse counts', 'Cumulative pulse counts for a water meter');
$GLOBALS['TL_LANG']['Sensor']['cum_elec_pulse']= array('Electricity pulse counts', 'Cumulative pulse counts for a electricity meter');
$GLOBALS['TL_LANG']['Sensor']['highalarm']= array('High alarm', 'Value above which to alert');
$GLOBALS['TL_LANG']['Sensor']['lowalarm']= array('Low alarm', 'Value below which to alert');

?>
