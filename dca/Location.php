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
// <summary>dca/Location.php defines the Location table</summary>

if (!defined('TL_ROOT')) die('You can not access this file directly!');

// Tabel Customer
$GLOBALS['TL_DCA']['Location'] = array
(
	// Config
	'config' => array
	(
	'label'               => &$GLOBALS['TL_LANG']['Location']['Location'],
	'dataContainer'		=> 'Table',
	'ptable' => 'Customer',
    'enableVersioning'  => true,
	'onsubmit_callback' => array
	(
			array('Location', 'updateCoordinates')
	),
    'notEditable'		=> false,
    'switchToEdit'		=> true,
	'closed' => false 			// implicit
	),

	// List
	'list' => array
	(
		'sorting' => array
		(
				'mode'                    => 4,
				'fields'                  => array('name'), // Elements sorted on id
				'headerFields'				  => array('name', 'city'),
				'panelLayout'             => 'sort',
				'disableGrouping'			  => true,
				'flag'                    => 11,
				'child_record_callback'   => array('Location', 'listLocations')
		),
		'label' => array
		(
				'fields'                  => array('name', 'city'), // Fields shown in the panel
				'showColumns'             => true,
				'format'                  => '%s %s'
		),
		'global_operations' => array
		(
			'all' => array
			(
				'label'               => &$GLOBALS['TL_LANG']['Location']['all'],
				'href'                => 'act=select',
				'class'               => 'header_edit_all',
				'attributes'          => 'onclick="Backend.getScrollOffset();"'
			)
		),
		'operations' => array
		(
				'editheader' => array
				(
					'label'               => &$GLOBALS['TL_LANG']['Location']['editheader'],
					'href'                => 'act=edit',
					'icon'                => 'edit.gif',
					'attributes'          => 'class="contextmenu"'
				),
//				'edit' => array
//				(
//					'label'               => &$GLOBALS['TL_LANG']['Location']['edit'],
//				PvE: SampleDescriptions added 11-10-2011
//			   'href'                => 'act=edit',
//				'href'                => 'table=SampleDescriptions',
//				'icon'                => 'header.gif',
//				'attributes'          => 'class="contextmenu"'
//				),
				'delete' => array
				(
					'label'               => &$GLOBALS['TL_LANG']['Location']['delete'],
					'href'                => 'act=delete',
					'icon'                => 'delete.gif',
					'attributes'          => 'onclick="if (!confirm(\'' . $GLOBALS['TL_LANG']['MSC']['deleteConfirm'] . '\')) return false; Backend.getScrollOffset();"'
				),
				'show' => array
				(
					'label'               => &$GLOBALS['TL_LANG']['Location']['show'],
					'href'                => 'act=show',
					'icon'                => 'show.gif'
				)
		)	// operations
	),		// list


// Palettes
'palettes' => array
(
	// palettes settings
	'default'               => '{location_legend}, pid, name;
    {address_legend}, street, housenumber, city, postalcode, country;
    {coordinates:hide}, latitude, longitude;
    {contact_legend},contactperson, email, telephone,  comments' 
),


// Fields
'fields' => array
(
	'pid' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Location']['pid'],
		'search'                  => false,
		'sorting'				  => true,
		'flag'					  => 11,
		'filter'				  => true,
		'exclude'                 => true,
		'inputType'               => 'select',
		'foreignKey'	     	  => 'Customer.name', 
		'eval'                    => array('mandatory'=>true, 'readonly'=> true, 'doNotCopy'=>true, 'includeBlankOption'=>true, 'tl_class'=>'w50')
	),
	'name' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Location']['name'],
		'search'                  => true,
		'sorting'		  		  => true,
		'filter'		  		  => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'minLength'=>3, 'maxlength'=>255)
	),

	'street' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Location']['street'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'minLength'=>3, 'maxlength'=>255, 'tl_class'=>'w50')
	),

	'housenumber' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Location']['housenumber'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>false, 'maxlength'=>45, 'tl_class'=>'w50')
	),

	'postalcode' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Location']['postalcode'],
		'search'                  => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>false, 'maxlength'=>45, 'tl_class'=>'w50')
	),

	'city' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Location']['city'],
		'search'                  => true,
		'sorting'					  => true,
		'filter'						  => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'maxlength'=>45, 'tl_class'=>'w50')
	),
	'country' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Location']['country'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'maxlength'=>45, 'tl_class'=>'clr')
	),
	'latitude' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Location']['latitude'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>false, 'rgxp'=>'digit', 'maxlength'=>20, 'tl_class'=>'w50')
	),
	'longitude' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Location']['longitude'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>false, 'rgxp'=>'digit', 'maxlength'=>20, 'tl_class'=>'w50')
	),
	'contactperson' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Location']['contactperson'],
		'search'                  => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>true, 'maxlength'=>45, 'tl_class'=>'w50')
	),

	'email' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Location']['email'],
		'search'                  => true,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>false, 'rgxp'=>'email', 'minLength'=>3, 'maxlength'=>45, 'tl_class'=>'w50')
	),
	'telephone' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Location']['telephone'],
		'search'                  => false,
		'inputType'               => 'text',
		'eval'                    => array('mandatory'=>false, 'maxlength'=>45, 'rgxp'=>'phone', 'tl_class'=>'w50')
	),
	'comments' => array
	(
		'label'                   => &$GLOBALS['TL_LANG']['Location']['comments'],
		'inputType'               => 'textarea',
		'eval'                    => array('tl_class'=>'clr', 'rte'=>'tinyMCE', 'allowHTML'=>true, 'mandatory'=>false, 'maxlength'=>4096)
	),
) // fields

);

/**
 * Class Location
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 * @copyright  Peter van Es 
 * @author     Peter van Es
 * @package    Controller
 */
class Location extends Backend {
	
	/**
	 * List Locations belonging to a Customer id
	 * @param array
	 * @return string
	 */
	public function listLocations($arrRow)
	{
		return '<div class="limit_height block">
			<p><strong>' . $arrRow['name'] . '</strong> (' . $arrRow['city'] . ', ' . $arrRow['contactperson']
			. ')</p></div>' . "\n";
	}


    private function getcoords ($street, $housenumber, $postalcode, $city, $country) {
        // retrieve coordinates belonging to a record using the Google API
        $q = 'json?address=' . urlencode ($street.' '.$housenumber.' '.$postalcode.' '.$city.' '.$country).'&sensor=false';
        $url = 'http://maps.googleapis.com/maps/api/geocode/' . $q;

        $ch = curl_init($url);
        
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt ($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt ($ch, CURLOPT_REFERER, 'http://google.com');

        if (($str = curl_exec ($ch)) === false) {
            return false;
        }
        if (curl_getinfo ($ch, CURLINFO_HTTP_CODE) == 200)
            return $str;
        else
            return curl_error ($ch);
    }

	
    /**
	 * updateCoordinates
	 * if the record has been changed we re-retrieve longitude and latitude from Google using the Google maps api
	 * this is done so that we can later calculate distances between locations
	 * @return array
	 */
	public function updateCoordinates(DataContainer $dc)
	{
		// Return if there is no active record (override all)
		if (!$dc->activeRecord)
		{
			return;
		}

		// Get the id of the Location
		$id = $dc->activeRecord->id;
		
		if ($id != NULL) 
		{
			$prow = $this->Database->prepare ("SELECT * FROM Location WHERE id=?")->execute($id);
            
            // Execute the Google Maps query using the Location data
            if (($s = $this->getcoords ($prow->street, $prow->housenumber, $prow->postalcode, $prow->city, $prow-country)) === false) {
                // address probably not ok
                return;
            }

            // decode the json structure
            $fetch = json_decode ($s);
            $geo_result = $fetch->results[0];
            $coordinates = $geo_result->geometry->location;

            // store the changed fields
			$arrSet['latitude'] = $coordinates->lat;
			$arrSet['longitude'] = $coordinates->lng;
            
            // standardise the address, but not the post code as Google does not always do that correctly
            // $address = $geo_result->address_components;
			// $arrSet['housenumber'] = $address[0]->long_name;
			// $arrSet['street'] = $address[1]->long_name;
			// $arrSet['city'] = $address[2]->long_name;
			
			// and update the Maintenance Record
			$this->Database->prepare("UPDATE Location %s WHERE id=?")->set($arrSet)->execute($dc->id);
		} // end if $id
	}
    
}
?>
