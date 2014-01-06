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
// <summary>dca/Switch.php defines the Switch table</summary>

// version 1.0


/*
CREATE TABLE `Switch` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `pid` int(11) DEFAULT NULL COMMENT 'Location.id',
  `tstamp` int(11) DEFAULT NULL,
  `uid` int(11) DEFAULT NULL COMMENT 'tl_user.id owner',
  `idswitch` varchar(15) DEFAULT '',
  `sensor_id` int(10) DEFAULT NULL COMMENT 'roomnode number',
  `description` varchar(80) DEFAULT NULL COMMENT 'description',
  `comments` varchar(4096) DEFAULT NULL COMMENT 'Comments time_on installation, remarks',
  `strategy` varchar(45) DEFAULT NULL,
  `command` varchar(80) DEFAULT NULL,
  `kaku` varchar(20) DEFAULT NULL,
  `time_on` varchar(8) DEFAULT NULL,
  `time_off` varchar(8) DEFAULT NULL,
  `state` varchar(8) DEFAULT NULL,
  `duration` int(11) DEFAULT NULL COMMENT 'Minutes',
  `olddim` int(1) DEFAULT '0' COMMENT 'Dimmable, old KAKU',
   PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Stores sensor machine details';*/

if (!defined('TL_ROOT')) die('You can not access this file directly!');

// Table Switch
$GLOBALS['TL_DCA']['Switch'] = array
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
				'fields'                  => array('id'), // Elements sorted time_on id
				'headerFields'	  	      => array('id', 'description'),
				'panelLayout'             => 'filter,sort; search,limit',
				'flag'                    => 1
		),
		'label' => array
		(
				'fields'                  => array('id', 'pid', 'description',  'strategy', 'kaku'), // Fields shown in the panel
				'showColumns'             => true,
				'format'                  => '%s</td><td class="tl_file_list"><a href="contao/main.php?do=Customers&table=Location&id=%s">%s</a></td><td class="tl_file_list">%s'
		),
		'operations' => array
		(
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Switch']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif',
//				'attributes'          => 'class="edit-header"'
				'attributes'          => 'class="contextmenu"'
			),
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Switch']['edit'],
				'href'                => 'table=Actionlog',
				'icon'                => 'header.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Switch']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Switch']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Switch']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)	// operations
	),		// list


// Palettes
'palettes' => array
(
	// palettes settings
	'default'               => '{Switch_legend}, pid, sensor_id, description, uid, comments;
					{activity_legend}, strategy, command, kaku, olddim, time_on, time_off, duration'
),


// Fields
'fields' => array
(
	'pid' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Switch']['pid'],
		'search'                  => false,
		'sorting'				  => true,
		'flag'					  => 11,
		'filter'				  => true,
		'exclude'                 => true,
		'inputType'               => 'select',
		'foreignKey'	     	  => 'Location.name', 
		'eval'                    => array('mandatory'=>true, 'readonly'=> false, 'doNotCopy'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50')
	),
	'sensor_id' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Switch']['sensor_id'],
		'search'                  => true,
		'sorting'		          => true,
		'inputType'               => 'select',
		'foreignKey'              => 'Sensor.location',
		'eval'                    => array('mandatory'=>true, 'rgxp'=>'digit', 'maxlength'=>12, 'tl_class'=>'w50')
	),

	'description' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Switch']['description'],
		'search'                  => true,
		'sorting'				  => true,
		'filter'				  => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'minLength'=>3, 'maxlength'=>255, 'tl_class'=>'w50')
	),
	'uid' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Switch']['uid'],
		'inputType'               => 'select',
		'foreignKey'	     	  => 'tl_user.name', 
		'eval'                    => array('mandatory'=>true, 'doNotCopy'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50')
	),
	'comments' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Switch']['comments'],
		'inputType'               => 'textarea',
		'eval'                    => array('tl_class'=>'clr',  'rte'=>'tinyMCE', 'allowHTML'=>true, 'mandatory'=>false, 'maxlength'=>4096)
	),
	'strategy' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Switch']['strategy'],
		'search'                  => false,
		'inputType'               => 'select',
		'options'				  => array('motion', 'sun', 'evening', 'time', 'light', 'simulate', 'event'),
		'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50')
	),
	'command' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Switch']['command'],
		'search'                  => false,
		'sorting'				  => false,
		'filter'				  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'minLength'=>1, 'maxlength'=>80, 'tl_class'=>'w50')
	),
	'kaku' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Switch']['kaku'],
		'search'                  => false,
		'sorting'				  => false,
		'filter'				  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>false, 'minLength'=>1, 'maxlength'=>20, 'tl_class'=>'w50')
	),
	'olddim' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Switch']['olddim'],
		'search'                  => false,
		'default'                 => '0',
		'inputType'               => 'select',
		'options'				  => array('0', '1'),
		'eval'                    => array('mandatory'=>true, 'tl_class'=>'w50')
	),
	'time_on' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Switch']['time_on'],
		'search'                  => false,
		'inputType'               => 'text',
		// 'rgxp'=>'time' does not work and has bade sideeffects... the time is stored as -3600 for 00:00, etc
		'eval'                    => array('mandatory'=>false, 'minLength'=>1, 'maxlength'=>8, 'tl_class'=>'w50')
	),
	'time_off' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Switch']['time_off'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>false, 'minLength'=>1, 'maxlength'=>8, 'tl_class'=>'w50')
	),
	'duration' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Switch']['duration'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>false, 'rgxp'=>'digit', 'minLength'=>1, 'maxlength'=>8, 'tl_class'=>'w50')
	),
) // fields

);



?>
