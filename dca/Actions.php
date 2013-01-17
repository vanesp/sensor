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
// <summary>dca/Actions.php defines the Actionlog table... but this one shows actions without the Bifipro parent</summary>
// for this a view Actions has to be created... CREATE VIEW Actions AS select * from Actionlog;


if (!defined('TL_ROOT')) die('You can not access this file directly!');

// Tabel Customer
$GLOBALS['TL_DCA']['Actions'] = array
(
	// Config
	'config' => array
	(
		'label'               => &$GLOBALS['TL_LANG']['Actions']['Actionlog'],
		'dataContainer'		  => 'Table',
	//	'ptable'			  => 'Bifipro',
	//	'notEditable'		=> false,
	//	'switchToEdit'		=> true,
        'enableVersioning'  => true,
		'closed' => false 			// implicit
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
				'mode'                    => 2,
				'fields'                  => array('id', 'pid', 'uid', 'created'), // Elements sorted on id
				'panelLayout'             => 'search, sort; filter, limit',
				'disableGrouping'		  => true,
				'flag'                    => 11,
		),
		'label' => array
		(
                'fields'                  => array('created', 'pid', 'pid:sensor.id', 'pid:sensor.idsensor', 'uid:tl_user.name', 'comment'), // Fields shown in the panel
				'showColumns'             => true,
                'format'                  => '<span style="color:#b3b3b3; padding-right:3px;">[%s]</span> <a href="contao/main.php?do=Sensor&act=edit&id=%s">%s</a> <strong>%s</strong> %s %s',
                'maxCharacters'           => 300,
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Actions']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Actions']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Actions']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Actions']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)	// operations
	),		// list


// Palettes
'palettes' => array
(
	// palettes settings
//	'default'=> 'uid, Comment'
	'default'=> 'pid, created, uid, Comment'
),


// Fields
'fields' => array
(
	'created' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Actions']['created'],
		'search'                  => false,
		'sorting'				  => true,
		'flag'					  => 7,
		'filter'				  => true,
		'default'				  => time(),
		'inputType'               => 'text',
		'eval'                    => array('rgxp'=>'datim', 'mandatory'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard')
	),
	'uid' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Actions']['uid'],
		'search'                  => false,
		'sorting'				  => true,
		'flag'					  => 11,
		'filter'			      => true,
		'exclude'                 => true,
		'default'                 => $this->User->id,
		'inputType'               => 'select',
		'foreignKey'	     	  => 'tl_user.name', 
		'eval'                    => array('mandatory'=>true, 'doNotCopy'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50')
	),
	'pid' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Actions']['pid'],
		'search'                  => false,
		'sorting'				  => true,
		'flag'					  => 11,
		'filter'				  => true,
		'exclude'                 => true,
		'inputType'               => 'select',
		'foreignKey'	     	  => 'sensor.idsensor', 
		'eval'                    => array('mandatory'=>true, 'readonly'=> true, 'doNotCopy'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50')
	),
	'comment' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Actions']['comment'],
		'search'                  => true,
		'inputType'               => 'textarea',
		'eval'                    => array('tl_class'=>'clr',  'rte'=>'tinyMCE', 'allowHTML'=>true, 'mandatory'=>false, 'maxlength'=>4096)
	),

) // fields

);


?>
