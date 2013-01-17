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
// <date>12-09-2011</date>
// <summary>dca/Actionlog.php defines the Actionlog table</summary>


if (!defined('TL_ROOT')) die('You can not access this file directly!');

// Tabel Customer
$GLOBALS['TL_DCA']['Actionlog'] = array
(
	// Config
	'config' => array
	(
		'label'               => &$GLOBALS['TL_LANG']['Actionlog']['Actionlog'],
		'dataContainer'		  => 'Table',
		'ptable'			  => 'Sensor',
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
				'mode'                    => 4,
//				'fields'                  => array('id', 'tstamp'), // Elements sorted on id
				'headerFields'	  		  => array('idsensor', 'location'),
				'fields'                  => array('id', 'pid', 'uid', 'created'), // Elements sorted on id
//				'headerFields'	  		  => array('id', 'pid', 'uid', 'tstamp'),
				'panelLayout'             => 'sort, limit',
//				'panelLayout'             => 'limit',
				'disableGrouping'		  => true,
				'flag'                    => 11,
				// PvE: Actionlog added 11-10-2011
				'child_record_callback'   => array('Actionlog', 'listactions')
		),
		'label' => array
		(
				'fields'                  => array('tstamp', 'comment'), // Fields shown in the panel
				'showColumns'             => true,
                'format'                  => '<span style="color:#b3b3b3; padding-right:3px;">[%s]</span> %s',
                'maxCharacters'           => 96,
		),
		'operations' => array
		(
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Actionlog']['edit'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'copy' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Actionlog']['copy'],
				'href'                => 'act=copy',
				'icon'                => 'copy.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Actionlog']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Actionlog']['show'],
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
	'default'=> 'created, uid, Comment'
),


// Fields
'fields' => array
(
	'created' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Actionlog']['created'],
		'search'                  => false,
		'sorting'				  => true,
		'flag'					  => 9,
		'filter'				  => true,
		'default'				  => time(),
		'inputType'               => 'text',
		'eval'                    => array('rgxp'=>'datim', 'mandatory'=>true, 'datepicker'=>true, 'tl_class'=>'w50 wizard')
	),
	'uid' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Actionlog']['uid'],
		'search'                  => false,
		'sorting'			      => true,
//		'flag'					  => 11,
		'filter'				  => true,
		'exclude'                 => true,
		'default'                 => $this->User->id,
		'inputType'               => 'select',
		'foreignKey'	     	  => 'tl_user.name', 
		'eval'                    => array('mandatory'=>true, 'doNotCopy'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50')
	),
	'pid' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Actionlog']['pid'],
		'search'                  => false,
		'sorting'				  => false,
		'filter'				  => true,
		'exclude'                 => true,
		'inputType'               => 'select',
		'foreignKey'	     	  => 'Sensor.idsensor', 
		'eval'                    => array('mandatory'=>true, 'readonly'=> true, 'doNotCopy'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50')
	),
	'comment' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Actionlog']['comment'],
		'inputType'               => 'textarea',
		'eval'                    => array('tl_class'=>'clr',  'rte'=>'tinyMCE', 'allowHTML'=>true, 'mandatory'=>false, 'maxlength'=>4096)
	),

) // fields

);

/**
 * Class Actionlog
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Peter van Es 
 * @author     Peter van Es
 * @package    Controller
 */
class Actionlog extends Backend {
	
	/**
	 * List Descriptions belonging to a Sensor id
	 * @param array
	 * @return string
	 */
	public function listactions($arrRow)
	{
		$this->import('String');
		$s = $this->String->substrHtml($arrRow['comment'],80);
		
		return '<div class="limit_height block">
			<strong>' . $arrRow['pid'] . '&nbsp;' . $this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'], $arrRow['tstamp']) . '</strong> ' . $s . '</div>' . "\n";
	}
	
}

?>
