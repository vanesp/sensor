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
// <summary>dca/Customer.php defines the Customer table</summary>

if (!defined('TL_ROOT')) die('You can not access this file directly!');

// Tabel Customer
$GLOBALS['TL_DCA']['Customer'] = array
(
	// Config
	'config' => array
	(
		'label'               => &$GLOBALS['TL_LANG']['Customer']['customer'],
		'dataContainer'		=> 'Table',
//		'ptable' => '',
		'ctable' => array('Location'),
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
				'headerFields'	  			  => array('id', 'name', 'city'),
				'panelLayout'             => 'search,sort,filter,limit',
				'flag'                    => 1
		),
		'label' => array
		(
				'fields'                  => array('id', 'name', 'city'), // Fields shown in the panel
				'showColumns'             => true,
				'format'                  => '%s</td><td class="tl_file_list">%s</td><td class="tl_file_list">%s'
		),
		'operations' => array
		(
			'editheader' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Customer']['editheader'],
				'href'                => 'act=edit',
				'icon'                => 'edit.gif',
//				'button_callback'     => array('tl_calendar', 'editHeader'),
				'attributes'          => 'class="contextmenu"'
			),
			'edit' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Customer']['edit'],
//				'href'                => 'act=edit',
				'href'                => 'table=Location',
				'icon'                => 'header.gif',
				'attributes'          => 'class="contextmenu"'
			),
			'delete' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Customer']['delete'],
				'href'                => 'act=delete',
				'icon'                => 'delete.gif',
				'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
			),
			'show' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Customer']['show'],
				'href'                => 'act=show',
				'icon'                => 'show.gif'
			)
		)	// operations
	),		// list


// Palettes
'palettes' => array
(
	// palettes settings
	'default'               => '{customer_legend},id,  name;{address_legend}, street, housenumber, city, postalcode, country;{contact_legend},contactperson,telephone,email,comments'
),


// Fields
'fields' => array
(
	'name' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Customer']['name'],
		'search'                  => true,
		'sorting'					  => true,
		'filter'						  => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'minLength'=>3, 'maxlength'=>255, 'tl_class'=>'w50')
	),

	'street' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Customer']['street'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'minLength'=>3, 'maxlength'=>255, 'tl_class'=>'w50')
	),

	'housenumber' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Customer']['housenumber'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>false, 'maxlength'=>45, 'tl_class'=>'w50')
	),

	'postalcode' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Customer']['postalcode'],
		'search'                  => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'maxlength'=>45, 'tl_class'=>'w50')
	),

	'city' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Customer']['city'],
		'search'                  => true,
		'sorting'					  => true,
		'filter'						  => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'maxlength'=>45, 'tl_class'=>'w50')
	),

	'country' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Customer']['country'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'maxlength'=>45, 'tl_class'=>'w50')
	),

	'contactperson' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Customer']['contactperson'],
		'search'                  => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'maxlength'=>45, 'tl_class'=>'w50')
	),

	'telephone' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Customer']['telephone'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'maxlength'=>45, 'tl_class'=>'w50')
	),

	'email' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Customer']['email'],
		'search'                  => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'rgxp'=>'email', 'minLength'=>3, 'maxlength'=>45, 'tl_class'=>'w50')
	),
	'comments' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Customer']['comments'],
		'inputType'               => 'textarea',
		'eval'                    => array('tl_class'=>'clr', 'rte'=>'tinyMCE', 'allowHTML'=>true, 'mandatory'=>false, 'maxlength'=>4096)
	),

) // fields

);

?>
