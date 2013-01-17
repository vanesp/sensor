<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

ini_set('display_errors','1');

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


 /**
 * Class ModuleSamples
 *
 * Front end module "Samples".
 * @package    Controller
 */
class ModuleSamples extends Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_samples';
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

			$objTemplate->wildcard = '### SAMPLES LIST ###';
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
 * Note: the layout for samples we intend to achieve:
 * Dates:       Most Recent Date - Next Date - Next Date >>
 * Sample 1:    Ag val 1         - Ag val 2  - Ag val 3 
 * 				Cu val 1         - Cu Val 2  - Cu val 3
 *              Kol              - Kol       - Kol
 *				:				 - :		 - :
 * Sample 2:    Ag val 1         - Ag val 2  - Ag val 3 
 * 				Cu val 1         - Cu Val 2  - Cu val 3
 *              Kol              - Kol       - Kol
 *				:				 - :		 - :
 *				:				 - :		 - :
 *				:				 - :		 - :
 * Sample n:    Ag val 1         - Ag val 2  - Ag val 3 
 * 				Cu val 1         - Cu Val 2  - Cu val 3
 *              Kol              - Kol       - Kol
 *				:				 - :		 - :
 * 
**/

	/**
	 * convert obj to array
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
                'detailurl' => '',
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
                'detailurl' => '<a href="'.$this->addToUrl('&item='.$obj->id).'">'. $obj->id . '</a>',
            );
        }
		return $newArray;
	}



	/**
	 * List Samples for one location
	 * @param id value
	 * @return array
	 */
	protected function listSamples($id, $d)
	{
			// Fetch data from the database for Location $id
			// First figure out how many different sample dates we have, in descending order...
			$arrDates = array();
			$arrSamples = array();
			$arrSensors = array();
            
            // if $d is not null, then we need to make sure $d is in the array of dates
			if ($d == NULL) {
                // just get the last dates
                $samps = $this->Database->prepare ("SELECT DISTINCT Samples.date
                                                    FROM Samples
                                                    WHERE Samples.idLocation=?
                                                    ORDER BY Samples.date DESC")->execute($id);
            } else {
                // get the dates with the date indicated included
                $samps = $this->Database->prepare ("SELECT DISTINCT Samples.date
                                                    FROM Samples
                                                    WHERE Samples.idLocation=? AND Samples.date <= ?
                                                    ORDER BY Samples.date DESC")->execute($id, $d);
            }
            
			$dates = array();
			$i = 0;
 			// if we have more than 5, just take the last 5
			while ($samps->next() && $i<5)
			{
					$dates[$i] = $samps->date;
                    // add an edit link to the date
					// $arrDates[$i]['date'] = date('Y-m-d', $samps->date);
					$arrDates[$i]['date'] = '<a href="index.php/EditSamples/item/'.$id.'/date/'.$samps->date.'.html">'.date('Y-m-d', $samps->date).'</a>';
                    $i++;
			}
            $nrdates = $i;
            // if $nrdates = 0, we don't have any samples yet...
            
            // Retrieve the customer nr and the Sensor's related to this sample. The customer number so that we can edit it if needed, and 
            // the Sensor so we can determine if it is a cooling tower/pond or not.
			$cust = $this->Database->prepare ("SELECT Customer.id, Customer.idCustomer, Customer.name, Location.NrSamples
                                               FROM Customer, Location
                                               WHERE Location.id=? AND Customer.id = Location.pid")->limit(1)->execute($id);
			while ($cust->next())
			{
					$custidCust = $cust->idCustomer;
					$custid = $cust->id;
                    $custname = $cust->name;
                    $nrsamples = $cust->NrSamples;
			}

            // Retrieve the sample descriptions 
            $descriptions = array();
			$desc = $this->Database->prepare ("SELECT Description
                                               FROM SampleDescriptions
                                               WHERE pid=?
                                               ORDER BY idSample")->execute($id);
			while ($desc->next())
			{
					$descriptions[] = $desc->Description;
			}
            
            
			$bifi = $this->Database->prepare ("SELECT id, idsensor, Location, El_Type, IsCooling, IsPond FROM Sensor WHERE Sensor.idLocation=?")->execute($id);
			// if it is a cooling tower or pond, set the following to true;
			$bCooling = false;
            $eltype = 'Cu';
            $k = 0;
			while ($bifi->next())
			{
					if ($bifi->IsCooling || $bifi->IsPond) {
                        $bCooling = true;
                    }
                    $arrSensors[$k] = '<a href="index.php/Sensors/item/'.$bifi->id.'.html">'. $bifi->idsensor . '</a>&nbsp;'.$bifi->Location;
                    if ($bifi->El_Type != 'Cu') {
                        $eltype = $bifi->El_Type;
                    }
			}
            
            // check if we have sufficient samples for a graph
            $objs = $this->Database->prepare ("SELECT DISTINCT date
                                           FROM Samples
                                           WHERE idLocation=?
                                           ORDER BY date DESC")->execute($id);
            // Put selectors into a string
            $selector = '';
            // Only if we have more than 3 values, smooth it
            if ($objs->numRows > 3) {
                $selector .= '<a href="index.php/Legionella/item/'.$id.'/graph/legsmth.html">Legionella smoothed</a>&nbsp;';
            }
            if ($objs->numRows > 1) {
                $selector .= '<a href="index.php/Legionella/item/'.$id.'/graph/legraw.html">Legionella</a>&nbsp;';
                $selector .= '<a href="index.php/Legionella/item/'.$id.'/graph/other.html">Temperature, Ag and Cu</a>&nbsp;';
                if ($bCooling) {
                    $selector .= '<a href="index.php/Legionella/item/'.$id.'/graph/qual.html">Water Quality</a>&nbsp;';
                }
            } else {
                $selector = 'Legionella graphs are only available when there are more than one set of samples';
            }
            
/***            if ($i>0) {
                // Now we can use datelist in a query to determine acceptable records...	
                // Now retrieve the samples in idSample, date order
                $objs = $this->Database->execute ("SELECT Samples.*,  SampleDescriptions.Description
                                                   FROM SampleDescriptions LEFT JOIN Samples ON (Samples.idSample = SampleDescriptions.idSample)
                                                   WHERE SampleDescriptions.pid=".$id." 
                                                   AND Samples.date IN (". implode(',', array_map('intval', $dates)) . ") 
                                                   AND Samples.idLocation = SampleDescriptions.pid
                                                   ORDER BY SampleDescriptions.idSample, Samples.date DESC");

                // Put samples into array
                while ($objs->next())
                {
                        $arrSamples[] = $this->obj2arr($objs);
                        $j++;
                }
            }
****/ 
			
            // if we have samples at all
            $count = 0;
            // $j = 0 to max nrsamples... 
            // $i = Dates counter ($dates[$i]) to $nrdates
            for ($j=0; $j < $nrsamples; $j++) {
                for ($i=0; $i < $nrdates; $i++) {
                    // Now we can use the values to retrieve a samples record	
                    // Note, samples are listed from nr 1 to n
                    $objs = $this->Database->execute ("SELECT Samples.*,  SampleDescriptions.Description
                                                       FROM SampleDescriptions LEFT JOIN Samples ON (Samples.idSample = SampleDescriptions.idSample)
                                                       WHERE SampleDescriptions.pid=".$id." 
                                                       AND Samples.idLocation = SampleDescriptions.pid
                                                       AND Samples.date=".$dates[$i]."
                                                       AND SampleDescriptions.idSample=".($j+1));

                    // Put samples into array... there may be zero values
                    if ($objs->next())
                    {
                            $arrSamples[$j][$i] = $this->obj2arr($objs);
                            $count++;
                    } else {
                            $arrSamples[$j][$i] = $this->obj2arr(NULL);
                            $count++;
                    }
                    $arrSamples[$j][$i]['Description'] = $descriptions[$j];
                    
                } // for $i
            } // for $j

			if ($count == 0) {
				// there are no samples at all, but do show an edit link
				$this->strTemplate = 'mod_samples_nosamples';
				$this->Template = new FrontendTemplate ($this->strTemplate);
				$this->Template->id = $this->Input->get('item');
                $this->Template->addsample = '<a href="index.php/EditSamples/item/'.$id.'.html">Add sample</a>';
				return '';
			}
				
			// Assign data to the template
			$this->strTemplate = 'mod_samples_detail';
			$this->Template = new FrontendTemplate ($this->strTemplate);
			$this->Template->link = $selector;
			$this->Template->dates = $arrDates;
            $this->Template->eltype = $eltype;
			$this->Template->samples = $arrSamples;
            $this->Template->descriptions = $descriptions;
            $this->Template->addsample = '<a href="index.php/EditSamples/item/'.$id.'.html">Add sample</a>';
			$this->Template->nr_dates = $nrdates;
			$this->Template->nr_samples = $count;
			$this->Template->customer = '<a href="index.php/Customers/item/'. $custid . '">'. $custidCust . '</a>&nbsp;'. $custname;
			$this->Template->sensors = $arrSensors;
			$this->Template->bCooling = $bCooling;
	}

	
	/**
	 * Generate module
	 */
	protected function compile()
	{
		$arrCusts = array();

		
		// Select appropriate Locations
		$this->locids = $this->accessLocations();
		// item is an id, 

		if (strlen($this->Input->get('item')>0)) {
			$item = $this->Input->get('item');
			// $item is a location id
 				
			// check if item in the array of $this->locids, so that we have access
			if (in_array($item, $this->locids, $strict = null)) {
				// ok, we are allowed to see that item... 
                // did we get a date as well ?
                $date = NULL;
                if (strlen($this->Input->get('date')>0)) {
                    $date = $this->Input->get('date');
                }
				$this->listSamples($item, $date);
			} else {
				// can not see this location,
				$this->strTemplate = 'mod_samples_error';
				$this->Template = new FrontendTemplate ($this->strTemplate);
				$this->Template->id = $this->Input->get('item');
				return '';
			}
		} else {

			// Return if there are no locids
			if (!is_array($this->locids) || count($this->locids) < 1)
			{
				$this->strTemplate = 'mod_samples_error';
				$this->Template = new FrontendTemplate ($this->strTemplate);
				$this->Template->id = $this->Input->get('item');
				return '';
			}

			// For each of the allowed building ids, select the dates of the available samples
            foreach ($this->locids as $id) {
            
                // inner join to prevent data from appearing if Weeklylog is empty
                $objSmps = $this->Database->prepare("SELECT DISTINCT Samples.date, Location.name, Location.city
                									 FROM Samples, Location
                									 WHERE Samples.idLocation=?
                									 AND Location.id=Samples.idLocation
                									 ORDER BY Samples.date DESC")->execute($id);
                
                // Put Smps into a string
                $datestring = '';
                while ($objSmps->next()) {
                	$name = $objSmps->name;
                	$city = $objSmps->city;
                    $sampledate = $objSmps->date;
                    $datestring .= '<a href="'.$this->addToUrl('&item='.$id.'&date='.$sampledate).'">'. date('Y-m-d', $sampledate) . '</a>&nbsp;';
                }

                if (strlen($datestring) > 0) {
                    // and store the values, but only if there are some (i.e. datestring contains something)
                    $arrSamps[] = array
                    (
                        'id' => $id,
                        'name' => $name,
                        'city' => $city, 
                        'samples' => $datestring,
                    );
                }

            } // foreach
	
			// Assign data to the template
			$this->Template->samples = $arrSamps;
		} // if-else
	}

}

?>