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
// <summary>dca/Sensor.php defines the Sensor table</summary>

// version 1.3 - IP connection facilities added

if (!defined('TL_ROOT')) die('You can not access this file directly!');

// Table Sensor
$GLOBALS['TL_DCA']['Sensor'] = array
(
	// Config
	'config' => array
	(
		'dataContainer'		=> 'Table',
		'ctable' => array('Actionlog'),
		'notEditable'		=> false,
        'enableVersioning'  => true,
		'closed' => false 			// implicit
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
				'mode'                    => 0,
				'fields'                  => array('id'), // Elements sorted on id
				'headerFields'	  	      => array('id', 'location'),
				'panelLayout'             => 'filter,sort; search,limit',
				'flag'                    => 1
		),
		'label' => array
		(
				'fields'                  => array('id', 'pid', 'idsensor',  'location', 'lobatt'), // Fields shown in the panel
				'showColumns'             => true,
				'format'                  => '%s</td><td class="tl_file_list"><a href="contao/main.php?do=Customers&table=Location&id=%s">%s</a></td><td class="tl_file_list">%s'
		),
		'operations' => array
		(
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Sensor']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif',
//				'attributes'          => 'class="edit-header"'
				'attributes'          => 'class="contextmenu"'
			),
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Sensor']['edit'],
				'href'                => 'table=Actionlog',
				'icon'                => 'header.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Sensor']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Sensor']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Sensor']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)	// operations
	),		// list


// Palettes
'palettes' => array
(
	// palettes settings
	'default'               => '{Sensor_legend}, pid, idsensor, idroom, location, uid, comments;
					{monitoring_legend}, lobatt, sensortype, sensorquantity, sensorscale, datastream;
				    {details_legend}, cum_gas_pulse, cum_water_pulse, cum_elec_pulse;
                    {limits_legend}, highalarm, lowalarm'
),


// Fields
'fields' => array
(
	'pid' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['pid'],
		'search'                  => false,
		'sorting'				  => true,
		'flag'					  => 11,
		'filter'				  => true,
		'exclude'                 => true,
		'inputType'               => 'select',
		'foreignKey'	     	  => 'Location.name', 
		'eval'                    => array('mandatory'=>true, 'readonly'=> false, 'doNotCopy'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50')
	),
	'idsensor' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['idsensor'],
		'search'                  => true,
		'sorting'		  		  => true,
		'filter'		          => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'maxlength'=>15, 'tl_class'=>'w50')
	),
	'idroom' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['idroom'],
		'search'                  => true,
		'sorting'		          => true,
		'filter'		          => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'rgxp'=>'digit', 'maxlength'=>12, 'tl_class'=>'w50')
	),

	'location' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['location'],
		'search'                  => true,
		'sorting'				  => true,
		'filter'				  => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'minLength'=>3, 'maxlength'=>255, 'tl_class'=>'w50')
	),
	'uid' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['uid'],
		'inputType'               => 'select',
		'foreignKey'	     	  => 'tl_user.name', 
		'eval'                    => array('mandatory'=>true, 'doNotCopy'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50')
	),
	'comments' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['comments'],
		'inputType'               => 'textarea',
		'eval'                    => array('tl_class'=>'clr',  'rte'=>'tinyMCE', 'allowHTML'=>true, 'mandatory'=>false, 'maxlength'=>4096)
	),
	'lobatt' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['lobatt'],
		'search'                  => true,
		'sorting'		          => true,
		'filter'		          => true,
		'inputType'               => 'text',
		'eval'                    => array('readonly'=>true, 'tl_class'=>'w50')
	),
	'sensortype' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['sensortype'],
		'search'                  => false,
		'inputType'               => 'select',
		'options'				  => array('RNR', 'Gas', 'Electricity', 'Water', 'Temperature', 'Humidity', 'Light', 'Motion', 'Pressure', 'Rainfall', 'Windspeed', 'Winddirection'),
		'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50')
	),
	'sensorquantity' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['sensorquantity'],
		'search'                  => false,
		'sorting'				  => false,
		'filter'				  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'minLength'=>1, 'maxlength'=>8, 'tl_class'=>'w50')
	),
	'datastream' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['datastream'],
		'search'                  => false,
		'sorting'				  => false,
		'filter'				  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>false, 'minLength'=>1, 'maxlength'=>15, 'tl_class'=>'w50')
	),
	'sensorscale' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['sensorscale'],
		'search'                  => false,
		'default'				  => '1', 
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'rgxp'=>'digit', 'minLength'=>1, 'maxlength'=>15, 'tl_class'=>'w50')
	),
	'cum_gas_pulse' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['cum_gas_pulse'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('readonly'=>true, 'tl_class'=>'w50')
	),
	'cum_water_pulse' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['cum_water_pulse'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('readonly'=>true, 'tl_class'=>'w50')
	),
	'cum_elec_pulse' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['cum_elec_pulse'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('readonly'=>true, 'tl_class'=>'w50')
	),
	'highalarm' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['highalarm'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>false, 'rgxp'=>'digit', 'minLength'=>1, 'maxlength'=>15, 'tl_class'=>'w50')
	),
	'lowalarm' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Sensor']['lowalarm'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>false, 'rgxp'=>'digit', 'minLength'=>1, 'maxlength'=>15, 'tl_class'=>'w50')
	),
) // fields

);

/**
 * Class Sensor
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Peter van Es 
 * @author     Peter van Es
 * @package    Controller
 */
class Sensor extends Backend {
	
	/**
	 * getDate
	 * convert time stamp into date value
	 * @param string
	 * @return string
	 */
	public function getDate($varValue)
	{
		// $rgxp = $arrData['eval']['rgxp'];
		$rgxp = 'datim';

		// Convert the timestamps into date value (check the eval setting first -> #3063)
		if (($rgxp == 'date' || $rgxp == 'time' || $rgxp == 'datim') && $varValue != '')
		{
			$varValue = $this->parseDate($GLOBALS['TL_CONFIG'][$rgxp . 'Format'], $varValue);
		}

		return $varValue;
	}

	/**
	 * getTstamp
	 * 
	 * @param string
	 * @return string
	 */
	public function getTstamp($varValue)
	{
		// $rgxp = $arrData['eval']['rgxp'];
		$rgxp = 'datim';

		// Convert date formats into timestamps (check the eval setting first -> #3063)
		if (($rgxp == 'date' || $rgxp == 'time' || $rgxp == 'datim') && $varValue != '')
		{
			$objDate = new Date($varValue, $GLOBALS['TL_CONFIG'][$rgxp . 'Format']);
			$varValue = $objDate->tstamp;
		}

		return $varValue;
	}

}

?>
