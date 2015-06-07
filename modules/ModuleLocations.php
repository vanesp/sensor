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
// <version>1.1</version>
// <email>vanesp@escurio.com</email>
// <date>2013-12-06</date>
// <summary>config.php defines the sensor module in contao</summary>

// <summary>ModuleLocations shows location information</summary>

// Version 1.1, 2013-12-06 - changes for Contao 3.1.2


/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace Contao;


 /**
 * Class ModuleLocations
 *
 * Front end module "Locations".
 * @package    Controller
 */
class ModuleLocations extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_reports';
    protected $bContaoUser = false;
	
	
	/**
	 * Display a wildcard in the back end
	 * @return string
	 */
	public function generate()
	{
		if (TL_MODE == 'BE')
		{
			$objTemplate = new \BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['Locations'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}
 
	
		return parent::generate();
	}


	/**
	 * Sort out which location records we can access
	 * @param array
	 * @return array
	 */
	protected function accessLocations()
	{

		$this->import('FrontendUser', 'User');
		// First we find out who is logged in... and what their e-mail address is...
		if (!FE_USER_LOGGED_IN && !BE_USER_LOGGED_IN)
		{
			return '';
		}
		$username = $this->User->username;
		$objMember = $this->Database->prepare("SELECT email FROM tl_member WHERE username=?")->limit(1)->execute($username);
		// $objUser->email contains the email address... we have the special case of a holland environment or hwt address
		// first make it lower case
		$objMember->email = strtolower($objMember->email);
		// check if that e-mail is in the user table
		$objUser = $this->Database->prepare("SELECT email FROM tl_user WHERE LOWER(email)=?")->limit(1)->execute($objMember->email);
		if ((strcmp($objUser->email, $objMember->email)==0) || BE_USER_LOGGED_IN) {
			// we accept all locations, and we have a back end user
			$objLocs = $this->Database->prepare("SELECT id FROM Location ORDER BY id")->execute();
            $this->bContaoUser = true;
		} else {
			$objLocs = $this->Database->prepare("SELECT Location.id AS id FROM Location, Customer WHERE (LOWER(Customer.email)=? OR LOWER(Location.email)=?) AND Location.pid=Customer.id ORDER BY Location.id")->execute($objMember->email, $objMember->email);
		}

		while ($objLocs->next())
		{
			$arrLocs[] = $objLocs->id;
		}

		return $arrLocs;
	}
    
	/**
	 * Sort out which location records belong to a customer
	 * @param array
	 * @return array
	 */
	protected function customerLocations($cust)
	{

		$this->import('FrontendUser', 'User');
		// First we find out who is logged in... and what their e-mail address is...
		if (!FE_USER_LOGGED_IN && !BE_USER_LOGGED_IN)
		{
			return '';
		}
		// $username = $GLOBALS['TL_USERNAME'];	// or we select on $this->User->username;
		$username = $this->User->username;
		$objUser = $this->Database->prepare("SELECT email FROM tl_member WHERE username=?")->limit(1)->execute($username);
		// $objUser->email contains the email address... we have the special case of a holland environment or hwt address
		// first make it lower case
		$objUser->email = strtolower($objUser->email);
		
		// Simplify the code with a nested list
		if (strpos($objUser->email, '@escurio.com') || BE_USER_LOGGED_IN) {
			// we accept all locations
            $this->bContaoUser = true;
			$objLocs = $this->Database->prepare("SELECT id FROM Location WHERE pid=? ORDER BY id")->execute($cust);
		} else {
			$objLocs = $this->Database->prepare("SELECT Location.id AS id FROM Location, Customer
            WHERE (LOWER(Customer.email)=? OR LOWER(Location.email)=?)
            AND Location.pid=Customer.id
            AND Location.pid=?
            ORDER BY Location.id")->execute($objUser->email, $objUser->email, $cust);
		}

		while ($objLocs->next())
		{
			$arrLocs[] = $objLocs->id;
		}

		return $arrLocs;
	}

    
	/**
	 * convert obj to array
	 * @param obj
	 * @return array
	 */
	protected function obj2arr($objLocs)
	{
		// calculate sunrise and sunset using php functions
		$zenith = 90+50/60;
		$offset = 1; // offset from UTC in NL
		$sunrise = date_sunrise (time(), SUNFUNCS_RET_STRING, $objLocs->latitude, $objLocs->longitude, $zenith, $offset);
		$sunset = date_sunset (time(), SUNFUNCS_RET_STRING, $objLocs->latitude, $objLocs->longitude, $zenith, $offset);
		
        $newArray = array
        (
            'name' => $objLocs->name,
            'street' => $objLocs->street,
            'housenumber' => $objLocs->housenumber,
            'postalcode' => $objLocs->postalcode,
            'city' => $objLocs->city,
            'country' => $objLocs->country,
            'contactperson' => $objLocs->contactperson,
            'telephone' => $objLocs->telephone,
            'email' => $objLocs->email,
            'comments' => $objLocs->comments,
            'id'	=> $objLocs->id,
            'latitude' => $objLocs->latitude,
            'longitude' => $objLocs->longitude,
            'sunrise' => $sunrise,
            'sunset' => $sunset,
            'detailurl' => '<a href="'.$this->addToUrl('&item='.$objLocs->id).'">'. $objLocs->name . '</a>',
        );

        // PvE: 2011-12-29 Only show comments to our own users
        if (!$this->bContaoUser) {
            $newArray['comments'] = '';
        }

        return $newArray;
	}


	/**
	 * List one customer
	 * @param id value
	 * @return array
	 */
	protected function listLocation($id)
	{
			// Fetch data from the database
			$objs = $this->Database->prepare ("SELECT * FROM Location WHERE id=?")->limit(1)->execute($id);
			$this->strTemplate = 'mod_location_detail';
			$this->Template = new FrontendTemplate ($this->strTemplate);
			
			// Put locations into array
			while ($objs->next())
			{
					$arrLoc = $this->obj2arr($objs);
			}
	
			// Assign data to the template
			$this->Template->location = $arrLoc;
	}
	
	/**
	 * Generate module
	 */
	protected function compile()
	{
		$arrLocs = array();
        $bNoReport = false;

		// Select appropriate Customers
		$this->locids = $this->accessLocations();

        // we may have a customer... if so, collect locations for that customer only

        $this->import('Session'); 
        $this->import('Input');
        $customer = $this->Input->get('customer');
        $item = $this->Input->get('item');

        if (isset($customer)) {
            $this->locids = $this->customerLocations($customer);
        }

		if (isset($item)) {
			// check if item in the array of $this->custids
			if (in_array($item, $this->locids, $strict = null)) {
				// ok, we are allowed to see that item... 
				$this->listLocation($item);
                return;
			} else {
				// can not see this location,
                $bNoReport = true;
                $reason = 'No access to this item: '.$item;
			}
		} else {
			// Return if there are no Locations
			if (!is_array($this->locids) || count($this->locids) < 1)
			{
                $bNoReport = true;
                $reason = 'No valid items.';
            }
		}

        // if no report show the error page
        if ($bNoReport) {
            $this->strTemplate = 'mod_reports_error';
            $this->Template = new FrontendTemplate ($this->strTemplate);
            $this->Template->user = $username;
            $this->Template->reports = 'Locations';
            $this->Template->reason = $reason;
            return '';
        }
        
        // default settings for our tables
        $this->strTemplate = 'mod_reports';

        $this->thead = true;
        $this->tfoot = false;
        $this->tleft = false;
        $this->sortable = true;
        
        // implement the reports to create a table...
        // get an array of $rows[]
        $rows = array();
        $align = array();
        // first set up the field names
        $rows[0] = array (
            0 => 'id',
            1 => 'name',
            2 => 'city',
            3 => 'sunrise',
            4 => 'sunset',
        );
        $fields = 5;
        // set the alignment of the fields
        for ($j = 0; $j < $fields; $j++) {
            $align[$j] = 'left';
        }
        $i = 1;
        // perform the query
		$objs = $this->Database->execute("SELECT * FROM Location WHERE id IN (". implode(',', array_map('intval', $this->locids)) . ") ORDER BY id");
        // get the values
        while ($objs->next()) {
			$arrLoc = $this->obj2arr($objs);
            for ($j = 0; $j < $fields; $j++) {
                $rows[$i][$j]=$arrLoc[$rows[0][$j]];
                if ($j == 0) {
                    // id field, make into link
                    $rows[$i][$j]= '<a href="index.php/Locations/item/'.sprintf("%d", $objs->$rows[0][$j]).'.html">'.sprintf("%d", $objs->$rows[0][$j]).'</a>';
                }    
            }
            $i++;
        }
        // Set-up general info
        $this->summary = 'Locations';
        $this->headline = 'Locations';
        
		// $rows = deserialize($this->tableitems);
		$nl2br = ($objPage->outputFormat == 'xhtml') ? 'nl2br_xhtml' : 'nl2br_html5';
        
        // create the new template
        $this->Template = new FrontendTemplate ($this->strTemplate);

        $this->Template->item = $item;
		$this->Template->id = 'table_' . $this->id;
		$this->Template->summary = specialchars($this->summary);
		$this->Template->useHeader = $this->thead ? true : false;
		$this->Template->useFooter = $this->tfoot ? true : false;
		$this->Template->useLeftTh = $this->tleft ? true : false;
		$this->Template->sortable = false;
		$this->Template->thousandsSeparator = $GLOBALS['TL_LANG']['MSC']['thousandsSeparator'];
		$this->Template->decimalSeparator = $GLOBALS['TL_LANG']['MSC']['decimalSeparator'];

		// Add the CSS and JavaScript files
		if ($this->sortable)
		{
			// $GLOBALS['TL_CSS'][] = TL_PLUGINS_URL . 'plugins/tablesort/css/tablesort.css|screen';
			// $GLOBALS['TL_MOOTOOLS'][] = '<script src="' . TL_PLUGINS_URL . 'plugins/tablesort/js/tablesort.js"></script>';
			// now part of jquery
			$this->Template->sortable = true;
		}

		$arrHeader = array();
		$arrBody = array();
		$arrFooter = array();

		// Table header
		if ($this->thead)
		{
			foreach ($rows[0] as $i=>$v)
			{
				// Set table sort cookie
				if ($this->sortable && $i == $this->sortIndex)
				{
					$co = 'TS_TABLE_' . $this->id;
					$so = ($this->sortOrder == 'descending') ? 'desc' : 'asc';

					if (!strlen($this->Input->cookie($co)))
					{
						setcookie($co, $i . '|' . $so, 0, '/');
					}
				}

				// Add cell
				$arrHeader[] = array
				(
                    'align'=> $align[$i],
					'class' => 'head_'.$i . (($i == 0) ? ' col_first' : '') . (($i == (count($rows[0]) - 1)) ? ' col_last' : ''),
					'content' => (($v != '') ? $nl2br($v) : '&nbsp;')
				);
			}

			array_shift($rows);
		}

		$this->Template->header = $arrHeader;
		$limit = $this->tfoot ? (count($rows)-1) : count($rows);

		// Table body
		for ($j=0; $j<$limit; $j++)
		{
			$class_tr = '';

			if ($j == 0)
			{
				$class_tr = ' row_first';
			}

			if ($j == ($limit - 1))
			{
				$class_tr = ' row_last';
			}

			$class_eo = (($j % 2) == 0) ? ' even' : ' odd';

			foreach ($rows[$j] as $i=>$v)
			{
				$class_td = '';

				if ($i == 0)
				{
					$class_td = ' col_first';
				}

				if ($i == (count($rows[$j]) - 1))
				{
					$class_td = ' col_last';
				}

				$arrBody['row_' . $j . $class_tr . $class_eo][] = array
				(
                    'align'=> $align[$i],
					'class' => 'col_'.$i . $class_td,
					'content' => (($v != '') ? $nl2br($v) : '&nbsp;')
				);
			}
		}

		$this->Template->body = $arrBody;

		// Table footer
		if ($this->tfoot)
		{
			foreach ($rows[(count($rows)-1)] as $i=>$v)
			{
				$arrFooter[] = array
				(
					'class' => 'foot_'.$i . (($i == 0) ? ' col_first' : '') . (($i == (count($rows[(count($rows)-1)]) - 1)) ? ' col_last' : ''),
					'content' => (($v != '') ? $nl2br($v) : '&nbsp;')
				);
			}
		}

		$this->Template->footer = $arrFooter;
	}
	
}

?>
