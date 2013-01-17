<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

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
// <summary>config.php defines the sensor module in contao</summary>

// <summary>ModuleCustomers shows customer information</summary>


 /**
 * Class ModuleCustomers
 *
 * Front end module "Customers".
 * @package    Controller
 */
class ModuleCustomers extends Module
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
			$objTemplate = new BackendTemplate('be_wildcard');

			$objTemplate->wildcard = '### CUSTOMER LIST ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}
		
		return parent::generate();
	}
	
	/**
	 * Sort out protected customer records
	 * @param array
	 * @return array
	 */
	protected function accessCustomers()
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
			// we accept all details, and we have a back end user
			$objCustomer = $this->Database->prepare("SELECT id FROM Customer ORDER BY id")->execute();
            $this->bContaoUser = true;
		} else {
			$objCustomer = $this->Database->prepare("SELECT id FROM Customer WHERE LOWER(email)=? ORDER BY id")->execute($objMember->email);
		}
		// objCustomer now has a list of customer id's which we are allowed to see	
		$arrCusts = array();

		while ($objCustomer->next())
		{
			$arrCusts[] = $objCustomer->id;
		}

		return $arrCusts;
	}



	/**
	 * convert obj to array
	 * @param obj
	 * @return array
	 */
	protected function obj2arr($objCusts)
	{
				$newArray = array
				(
					'name' => $objCusts->name,
					'street' => $objCusts->street,
					'housenumber' => $objCusts->housenumber,
					'postalcode' => $objCusts->postalcode,
					'city' => $objCusts->city,
					'country' => $objCusts->country,
					'contactperson' => $objCusts->contactperson,
					'telephone' => $objCusts->telephone,
					'email' => $objCusts->email,
					'id'	=> $objCusts->id,
					'detailurl' => '<a href="'.$this->addToUrl('&item='.$objCusts->id).'">'. $objCusts->name . '</a>',
				);
				return $newArray;
	}

	/**
	 * List one customer
	 * @param id value
	 * @return array
	 */
	protected function listCustomer($id)
	{
			// Fetch data from the database
			$objCusts = $this->Database->prepare ("SELECT * FROM Customer WHERE id=?")->limit(1)->execute($id);
			$this->strTemplate = 'mod_customer_detail';
			$this->Template = new FrontendTemplate ($this->strTemplate);
			
			// Put customers into array
			while ($objCusts->next())
			{
					$arrCusts = $this->obj2arr($objCusts);
			}
	
			// Assign data to the template
			$this->Template->customer = $arrCusts;
	}

	
    /**
     * Class ContentTable
     * PvE: 2012-03-02
     * This is the standard ContentTable element... we just reverse engineer the way the table gets created using a query
     */
	protected function compile()
	{
		global $objPage;
        $bNoReport = false;
		$arrCusts = array();
		
		// Select appropriate Customers
		$this->custids = $this->accessCustomers();
		// item is either an id, or an idCustomer... so check for both
        $this->import('Session'); 
        $this->import('Input');
        $item = $this->Input->get('item');

		if (isset($item)) {
 				
			// check if item in the array of $this->custids, so that we have access
			if (in_array($item, $this->custids, $strict = null)) {
				// ok, we are allowed to see that item... 
				$this->listCustomer($item);
                return;
			} else {
				// can see this customer,
                $bNoReport = true;
                $reason = 'No access to this item: '.$item;
			}
		} else {
			// Return if there are no Customers
			if (!is_array($this->custids) || count($this->custids) < 1)
			{
                $bNoReport = true;
                $reason = 'No valid customers.';
            }
        }

        // if no report show the error page
        if ($bNoReport) {
            $this->strTemplate = 'mod_reports_error';
            $this->Template = new FrontendTemplate ($this->strTemplate);
            $this->Template->user = $username;
            $this->Template->report = 'Customers';
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
            3 => 'contactperson',
            4 => 'email',
        );
        $fields = 5;
        // set the alignment of the fields
        for ($j = 0; $j < $fields; $j++) {
            $align[$j] = 'left';
        }
        $i = 1;
        // perform the query
 		$objs = $this->Database->execute("SELECT id, name, city, contactperson, email FROM Customer WHERE id IN (". implode(',', array_map('intval', $this->custids)) . ") ORDER BY id");
        // get the values
        while ($objs->next()) {
            for ($j = 0; $j < $fields; $j++) {
                $rows[$i][$j]=$objs->$rows[0][$j];
                if ($j == 0) {
                    // id field, make into link
                    $rows[$i][$j]='<a href="index.php/Customers/item/'.$objs->id.'.html">'.$objs->$rows[0][$j].'</a>';
                }    
            }
            $i++;
        }
        // Set-up general info
        $this->summary = 'Customers';
        $this->headline = 'Customers';
        
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
			$GLOBALS['TL_CSS'][] = TL_PLUGINS_URL . 'plugins/tablesort/css/tablesort.css|screen';
			$GLOBALS['TL_MOOTOOLS'][] = '<script src="' . TL_PLUGINS_URL . 'plugins/tablesort/js/tablesort.js"></script>';
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