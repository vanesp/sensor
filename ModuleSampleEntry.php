<?php if (!defined('TL_ROOT')) die('You cannot access this file directly!');

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

// <date>01-02-2012</date>


/**
 * Class ModuleSampleEntry
 *
 * Front end module "SampleEntry".
 * @package    Controller
 */
class ModuleSampleEntry extends Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_sample_entry';
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

			$objTemplate->wildcard = '### SAMPLE ENTRY ###';
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
	 * convert Sample obj to array
	 * @param obj
	 * @return array
	 */
	protected function obj2arr($obj)
	{
	    if ($obj == NULL) {
            $newArray = array
            (
                'id'	=> '',
                'pid'	=> '',
                'tstamp'	=> '',
                'idLocation' => '',
                'idSample' => '',
                'Description' => '',
                'date' => '',
                'Cu' => '',
                'Ag' => '',
                'Legionella' => '',
                'Colonies' => '',
                'Temperature' => '',
                'Comment' => '',
                'pH' => '',
                'Conductivity' => '',
                'Hardness' => '',
            );
        } else {
            $newArray = array
            (
                'id'	=> $obj->id,
                'pid'	=> $obj->pid,
                'tstamp'	=> $obj->tstamp,
                'idLocation' => $obj->idLocation,
                'idSample' => $obj->idSample,
                'Description' => $obj->Description,
                'date' => date('Y-m-d', $obj->date),
                'Cu' => $obj->Cu,
                'Ag' => $obj->Ag,
                'Legionella' => $obj->Legionella,
                'Colonies' => $obj->Colonies,
                'Temperature' => $obj->Temperature,
                'Comment' => $obj->Comment,
                'pH' => $obj->pH,
                'Conductivity' => $obj->Conductivity,
                'Hardness' => $obj->Hardness,
            );
        }
		return $newArray;
	}
    
    
	/**
	 * Generate module
	 */
	protected function compile()
	{
		global $objPage;

        // PvE: stolen from ModulePersonalData.php
              
		$GLOBALS['TL_LANGUAGE'] = $objPage->language;
		$arrDescs = array();
		$arrFields = array();
        $arrData = array();

		$this->loadLanguageFile('Samples');
		$this->loadDataContainer('Samples');

		// Select appropriate Locations
		$this->Locids = $this->accessLocations();

        // we should have an item for editing 
        if (strlen($this->Input->get('item')>0)) {
            $item = $this->Input->get('item');
            // check if item in the array of $this->Locids
            if (is_array($this->Locids) && (!in_array($item, $this->Locids, $strict = null))) {
				// we are not allowed to see / edit that item 
                return;
            }
        }
        
        $bNew = false;
        // we should have a date for editing, else were creating a new record
        if (strlen($this->Input->get('date')>0)) {
            $date = $this->Input->get('date');
            // if date contains strings, convert it to a timestamp value
            // $rgxp = $arrData['eval']['rgxp'];
            $rgxp = 'date';
            // Convert date formats into timestamps (check the eval setting first -> #3063)
            if (($rgxp == 'date' || $rgxp == 'time' || $rgxp == 'datim'))
            {
                $objDate = new Date($date, $GLOBALS['TL_CONFIG'][$rgxp . 'Format']);
                $date = $objDate->tstamp;
            }
        } else {
            $date = time();
            $bNew = true;
        }
        
        // Now retrieve some essential information for descriptions, Sensor types etc
        $bifi = $this->Database->prepare ("SELECT id, idsensor, Location, El_Type, IsCooling, IsPond FROM Sensor WHERE Sensor.idLocation=?")->execute($item);
        // if it is a cooling tower or pond, set the following to true;
        $bCooling = false;
        $eltype = 'Cu';
        while ($bifi->next())
        {
                if ($bifi->IsCooling || $bifi->IsPond) {
                    $bCooling = true;
                }
                if ($bifi->El_Type != 'Cu') {
                    $eltype = $bifi->El_Type;
                }
        }

         // ensure arrDescs contains the correct descriptions
        $samps = $this->Database->prepare ("SELECT * FROM SampleDescriptions WHERE pid=? ORDER BY idSample")->execute($item);
        // Put samples into array... if we found no leave empty
        $nrSamples = $samps->numRows;
        while ($samps->next())
        {
                $arrDescs[] = $samps->Description;
        }
       
		if (strlen($this->$strTemplate))
		{
			$this->Template = new FrontendTemplate($this->$strTemplate);
			$this->Template->setData($this->arrData);
		}

		$this->Template->fields = '';
		$this->Template->tableless = $this->tableless;
		$this->Template->bCooling = $bCooling;
		$this->Template->eltype = $eltype;
		$doNotSubmit = false;

		$hasUpload = false;
        $bInserted = false;     // did we insert data?
		$i = 1;
        $row = 0;

		// First check if we have a form submitted
		if ($this->Input->post('FORM_SUBMIT') == 'hwt_sampleedit')
        {
            
        	// Validation has to be handled manually... first loop through the fields... at most we have 20
            // even though the official maximum is 14
	        for ($i = 1; $i <= 20; $i++) 
	        {
	        	// get the different values... from the form
                
                $id = intval ($this->Input->post('id_'.$i));
                // idLocation is not on the form but derived from the calling name
                $idLocation = $item;
                $pid = intval ($this->Input->post('pid_'.$i));
                $idSample = intval ($this->Input->post('idSample_'.$i));
                // note, date is only stored once on the form
                $date = $this->Input->post('date_1');
                // if date contains - and :, or / strings, convert it to a timestamp value
                // returns false if these chars are not found
                if (!strpbrk ($date, '-:/')) {
                    // value is ok
                } else {
                    $rgxp = 'date';
                    // Convert date formats into timestamps (check the eval setting first -> #3063)
                    if (($rgxp == 'date' || $rgxp == 'time' || $rgxp == 'datim'))
                    {
                        $objDate = new Date($date, $GLOBALS['TL_CONFIG'][$rgxp . 'Format']);
                        $date = $objDate->tstamp;
                    }
                }
                
                $Cu = floatval ($this->Input->post('Cu_'.$i));
                $Ag = floatval ($this->Input->post('Ag_'.$i));
                $Legionella = floatval ($this->Input->post('Legionella_'.$i));
                $Colonies = floatval ($this->Input->post('Colonies_'.$i));
                $Temperature = floatval ($this->Input->post('Temperature_'.$i));
                $Comment = $this->Input->post('Comment_'.$i);
                
                // quick check if we got values entered
                $bValues = false;
                if (strlen($this->Input->post('Cu_'.$i)) > 0 || strlen($this->Input->post('Legionella_'.$i)) > 0) {
                    $bValues = true;
                }
                
                // the following fields are set only if it is a cooling tower
                if ($bCooling) {
                    $pH = floatval ($this->Input->post('pH_'.$i));
                    $Conductivity = floatval ($this->Input->post('Conductivity_'.$i));
                    $Hardness = floatval ($this->Input->post('Hardness_'.$i));
                }

				// if there is data, then we update the database... but only if we have some data 
                // could do this check on $bNew too...
				if ($bValues && isset($pid) && isset($idSample)) 
				{
                    $bInserted = true;
					if ($id != 0) 
					{
						// it is an update
                        if (!$bCooling) {
                            $this->Database->prepare("UPDATE Samples
                                                   SET date=?, Cu=?, Ag=?, Legionella=?,
                                                   Colonies=?, Temperature=?, Comment=?,
                                                   tstamp=UNIX_TIMESTAMP(NOW()) WHERE id=?")
                                ->execute($date, $Cu, $Ag, $Legionella, $Colonies, $Temperature, $Comment, $id);
                        } else {
                            $this->Database->prepare("UPDATE Samples
                                                   SET date=?, Cu=?, Ag=?, Legionella=?,
                                                   Colonies=?, Temperature=?, Comment=?,
                                                   pH=?, Conductivity=?, Hardness=?,
                                                   tstamp=UNIX_TIMESTAMP(NOW()) WHERE id=?")
                                ->execute($date, $Cu, $Ag, $Legionella, $Colonies, $Temperature, $Comment, $pH, $Conductivity, $Hardness, $id);
                        }
                    } else {
                    	// adding a new record
                        if (!$bCooling) {
                            $this->Database->prepare("INSERT Samples
                                                   SET pid=?, idLocation=?, idSample=?, 
                                                   date=?, Cu=?, Ag=?, Legionella=?,
                                                   Colonies=?, Temperature=?, Comment=?,
                                                   tstamp=UNIX_TIMESTAMP(NOW())")
                                ->execute($pid, $idLocation, $idSample, $date, $Cu, $Ag, $Legionella, $Colonies, $Temperature, $Comment);
                        } else {
                            $this->Database->prepare("INSERT Samples
                                                   SET pid=?, idLocation=?, idSample=?, 
                                                   date=?, Cu=?, Ag=?, Legionella=?,
                                                   Colonies=?, Temperature=?, Comment=?,
                                                   pH=?, Conductivity=?, Hardness=?,
                                                   tstamp=UNIX_TIMESTAMP(NOW())")
                                ->execute($pid, $idLocation, $idSample, $date, $Cu, $Ag, $Legionella, $Colonies, $Temperature, $Comment, $pH, $Conductivity, $Hardness);
                        }
                    }
                } // if isset
            } // for record
            if ($bInserted) {
                $this->log('A new version of Samples for location ID '.$item.', date '.date("Y-m-d",$date).' has been created',__METHOD__,'INFO');
            }
            // redirect to a different page
            header('Location: http://www.sensor.com/index.php/Samples/item/'.$item.'/date/'.$date.'.html');
            exit();
		} // if submitted
        
        // First we collect all the data we need...
        // Now retrieve the samples in idSample, date order, but make sure we also catch non-existent ones...
        for ($i = 1; $i <= $nrSamples; $i++) {
            $objs = $this->Database->prepare("SELECT Samples.*
                                               FROM Samples
                                               WHERE Samples.idLocation=? AND Samples.date=? AND Samples.idSample=?")->execute($item, $date, $i);

            // Put samples into array
            if ($objs->next())
            {       
                    // the sample exists
                    $arrFields[$i-1] = $this->obj2arr($objs);
            } else {
                    // it does not exist
                    $arrFields[$i-1] = $this->obj2arr(NULL);
                    // set some values for the form
                    $arrFields[$i-1]['idLocation']= $item;
                    $arrFields[$i-1]['idSample']= $i;
                    $arrFields[$i-1]['date']= date('Y-m-d', $date);
            }
		}
        $this->Template->arrFields = $arrFields;
		$this->Template->nrFound = $nrSamples;
		$this->Template->nrSamples = $nrSamples;
        
		// Build form
        // note that we have a set of fields per record, and we have up to arrFields records...
        // and we can have up to 20 total sample descriptions (max 14 samples for over 1600 tappoints)
        // but here we know how many descriptions we have... nrSamples
        for ($i = 1; $i <= $nrSamples; $i++) 
        {
            // for each sample record
            // create an array of records
            // note that we can have variable variable names in php
            // if $a = 'hello'
            // and $$a = 12;
            // than $hello is the same as 12, as is ${$a}

            // handle these fields
            if ($bCooling) {
                // handle all fields, else a subset
                $this->fields = array ('id', 'pid', 'idSample', 'date', 'Cu', 'Ag', 'Legionella', 'Colonies', 'Temperature', 'Comment', 'pH', 'Conductivity', 'Hardness');
            } else {
                $this->fields = array ('id', 'pid', 'idSample', 'date', 'Cu', 'Ag', 'Legionella', 'Colonies', 'Temperature', 'Comment');
            }
            $id = $arrFields[$i-1][id];
            $pid = $arrFields[$i-1][pid];
            $idSample = $arrFields[$i-1][idSample];
            $date =  $arrFields[$i-1][date];
            $Cu = $arrFields[$i-1][Cu];
            $Ag = $arrFields[$i-1][Ag];
            $Legionella = $arrFields[$i-1][Legionella];
            $Colonies = $arrFields[$i-1][Colonies];
            $Temperature = $arrFields[$i-1][Temperature];
            $Comment = $arrFields[$i-1][Comment];
            $pH = $arrFields[$i-1][Cu];
            $Conductivity = $arrFields[$i-1][Conductivity];
            $Hardness = $arrFields[$i-1][Hardness];

            // Add the sample description to the template
            $this->Template->fields .= '<tr><td colspan="2"><h3>'.$arrDescs[$i-1].'</h3></td></tr>';
            
            foreach ($this->fields as $field)
            {
                $arrData = $GLOBALS['TL_DCA']['Samples']['fields'][$field];

                $bHidden = false;
                // on the first record only, add the date field
                if ($i > 1 && $field == 'date') 
                {
                    $bHidden = true;
                }
                
                // is it id, then it is a hidden field
                if ($field == 'id' || $field == 'pid' || $field == 'idSample')
                {
                    $bHidden = true;
                }
                
                // Map checkboxWizard to regular checkbox widget
                if ($arrData['inputType'] == 'checkboxWizard')
                {
                    $arrData['inputType'] = 'checkbox';
                }

                $strClass = $GLOBALS['TL_FFL'][$arrData['inputType']];

                // Continue if the class is not defined
                if (!$this->classFileExists($strClass))
                {
                    continue;
                }

                $arrData['eval']['tableless'] = $this->tableless;
                $arrData['eval']['required'] = $arrData['eval']['mandatory'];

                // here the field value must appear
                // get the field from the correct record name
                $field_name = $field . '_' . $i;
                if (!$bHidden) {
                    $objWidget = new $strClass($this->prepareForWidget($arrData, $field_name, $$field));
                    $objWidget->storeValues = true;
                    $objWidget->rowClass = 'row_' . $row . (($row == 0) ? ' row_first' : '') . ((($row % 2) == 0) ? ' even' : ' odd');
                    $temp = $objWidget->parse();
                    ++$row;
                } else {
                    // the field is hidden, create it ourselves
                    $temp = '<input type="hidden" name="'.$field_name.'" value="'.$$field.'">';
                }    

                $this->Template->fields .= $temp;
                // $arrFields[$arrData['eval']['feGroup']][$field] .= $temp;
                
             } // foreach field
		} // foreach sample

		$this->Template->rowLast = 'row_' . ++$i . ((($i % 2) == 0) ? ' even' : ' odd');
		$this->Template->enctype = $hasUpload ? 'multipart/form-data' : 'application/x-www-form-urlencoded';
		$this->Template->hasError = $doNotSubmit;
		$this->Template->formId = 'hwt_sampleedit';
		$this->Template->slabel = specialchars($GLOBALS['TL_LANG']['MSC']['saveData']);
		$this->Template->action = $this->getIndexFreeRequest();
		$this->Template->rowLast = 'row_' . $row . ((($row % 2) == 0) ? ' even' : ' odd');

	}
}

?>