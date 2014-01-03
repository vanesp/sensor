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
// <date>2013-12-15</date>
// <summary>languages/en/Switch.php defines the Switch language</summary>

// Buttons
$GLOBALS['TL_LANG']['Switch']['new']= array('New Switch', 'Create a new Switch');
$GLOBALS['TL_LANG']['Switch']['edit']= array('Edit', 'Edit Switch Actionlog');
$GLOBALS['TL_LANG']['Switch']['editheader']= array('Edit', 'Edit Switch %s');
$GLOBALS['TL_LANG']['Switch']['delete']= array('Delete', 'Delete Switch %s');
$GLOBALS['TL_LANG']['Switch']['copy']= array('Copy', 'Copy Switch %s');
$GLOBALS['TL_LANG']['Switch']['show']= array('Show', 'Show Switch %s');

// Legends
$GLOBALS['TL_LANG']['Switch']['Switch_legend']= 'Switch';
$GLOBALS['TL_LANG']['Switch']['activity_legend']= 'Activation details';
$GLOBALS['TL_LANG']['Switch']['details_legend']= 'Switch details';
$GLOBALS['TL_LANG']['Switch']['limits_legend']= 'Alert limits';

// Fields
$GLOBALS['TL_LANG']['Switch']['pid']= array('Location id', 'Select the location');
$GLOBALS['TL_LANG']['Switch']['sensor_id']= array('Sensor id', 'Select the id of the sensor associated with this switch');
$GLOBALS['TL_LANG']['Switch']['uid']= array('Owner', 'Select the user responsible for this switch');
$GLOBALS['TL_LANG']['Switch']['description']= array('Description', 'Name of the switch');
$GLOBALS['TL_LANG']['Switch']['comments']= array('Comments... Not for actionlog items!', 'Enter any special remarks for this switch, no action log items!');
$GLOBALS['TL_LANG']['Switch']['strategy']= array('Strategy', 'Determine how this switch should be activated (time_on motion, or rising or setting of the sun, evening only, time on and off, light levels, or simulated presence.');
$GLOBALS['TL_LANG']['Switch']['command']= array('Command', 'Enter the command string to activate this switch, e.g. SendKAKU E1, or SendNewKAKU 2,. Do include the trailing comma');
$GLOBALS['TL_LANG']['Switch']['kaku']= array('Kaku', 'Klik-Aan-Klik-Uit identifier, e.g. E1 or e.g. 0x285E880');
$GLOBALS['TL_LANG']['Switch']['olddim']= array('Dimmer', 'Dimmer function for old KAKU switches (1=yes, 0=no)');
$GLOBALS['TL_LANG']['Switch']['time_on']= array('Time on', 'Time at which to switch on');
$GLOBALS['TL_LANG']['Switch']['time_off']= array('Time off', 'Time at which to switch off');
$GLOBALS['TL_LANG']['Switch']['duration']= array('Duration', 'Duration (in minutes) activation after an event');

?>
