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
// Plotting is now with Javascript Flot library (http://www.flotcharts.org)
//
// </copyright>
// <author>Peter van Es</author>
// <version>1.2</version>
// <email>vanesp@escurio.com</email>
// <date>2013-12-06</date>

// Version 1.2, 2013-12-06 - changes for Contao 3.1.2


/**
 * Run in a custom namespace, so the class can be replaced
 */
namespace Contao;



 /**
 * Class ModuleElectricity
 *
 * Front end module "Sensors".
 * @package    Controller
 */
class ModuleElectricity extends \Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_electric';
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

			$objTemplate->wildcard = '### ' . utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['Electricity'][0]) . ' ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}
		
		return parent::generate();
	}


	/**
	 * Sort out which Sensor records we can access
	 * @param array
	 * @return array
	 */
	protected function accessSensors()
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
			// we accept all sensors and we have a HWTuser
            $this->bContaoUser = true;
			$objSensors = $this->Database->prepare("SELECT id FROM Sensor WHERE Sensortype='Electricity' ORDER BY id")->execute();
		} else {
			$objSensors = $this->Database->prepare("SELECT DISTINCT Sensor.id AS id
			                                        FROM Sensor, Location, Customer
			                                        WHERE (LOWER(Customer.email)=? OR LOWER(Location.email)=?)
			                                        AND (Sensor.pid=Customer.id OR Sensor.pid=Location.id)
			                                        AND Location.pid=Customer.id AND Sensortype='Electricity'
			                                        ORDER BY Sensor.id")->execute($objMember->email, $objMember->email);
		}

		while ($objSensors->next())
		{
			$arrSensors[] = $objSensors->id;
		}

		return $arrSensors;
	}

	/**
	 * Sort out which Sensor records we have at this location
	 * @param array
	 * @return array
	 */
	protected function locationSensors($loc)
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
			// we accept all sensors and we have a HWTuser
            $this->bContaoUser = true;
			$objSensors = $this->Database->prepare("SELECT id FROM Sensor WHERE pid=? AND Sensortype='Electricity' ORDER BY id")->execute($loc);
		} else {
			$objSensors = $this->Database->prepare("SELECT DISTINCT Sensor.id AS id FROM Sensor, Location, Customer
            WHERE (LOWER(Customer.email)=? OR LOWER(Location.email)=?)
            AND (Sensor.pid=Customer.id OR Sensor.pid=Location.id)
            AND Location.pid=Customer.id 
            AND Sensor.pid=?
            AND sensortype='Electricity'
            ORDER BY Sensor.id")->execute($objMember->email, $objMember->email, $loc);
		}

		while ($objSensors->next())
		{
			$arrSensors[] = $objSensors->id;
		}
		return $arrSensors;
	}

	/**
	 * convert obj to array
	 * @param obj
	 * @return array
	 */
	protected function sensor2arr($objSensors)
	{

		$this->import('String');
        
		// go and interpret values to add trafficlight system
		$last = $objSensors->tstamp;
		$now = time();					// return unix time
		$monitoredurl = '<img src="/system/modules/sensor/assets/Red.png" alt="Not recently Monitored" />';
		if ($last+6*60 >= $now) {
			// monitored within last 6 mins
			$monitoredurl = '<img src="/system/modules/sensor/assets/Green.png" alt="Monitored within 6 mins" />';
		} else {
			if ($last+12*60 >= $now) {
				$monitoredurl = '<img src="/system/modules/sensor/assets/Orange.png" alt="Monitored within 12 mins" />';
			}
		}
		
		// verify machine status
		if ($objSensors->lobatt == 1)  {
            // PvE:its a low battery
            $machineurl = '<img src="/system/modules/sensor/assets/Red.png" alt="Battery dead" />';
		} else {
            $machineurl = '<img src="/system/modules/sensor/assets/Green.png" alt="Battery ok" />';
		}
        
        // now retrieve the last measured value = current value for each sensor
        if ($objSensors->sensortype == 'RNR') {
        	// roomnode
			$objs = $this->Database->prepare ("SELECT * FROM Roomlog WHERE pid=? ORDER BY tstamp DESC")->limit(1)->execute($objSensors->id);
			$objs->next();
			$value = '@ '.$this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'], $objs->tstamp) .' L: '.$objs->light. ' % RH: '.$objs->humidity. ' % T: '.$objs->temp. ' &deg;C';
        } else {
        	// regular sensor
			$objs = $this->Database->prepare ("SELECT * FROM Sensorlog WHERE pid=? ORDER BY tstamp DESC")->limit(1)->execute($objSensors->id);
			$objs->next();
			$value = '@ '.$this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'], $objs->tstamp) .' '.$objs->value.' '.$objSensors->sensorquantity;
        }
        
		$newArray = array
		(
			'id' => $objSensors->id,
			'pid' => $objSensors->pid,
			'tstamp' => $this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'], $obj->tstamp),
			'name' => $objSensor->name,
			'uid' => $objSensors->uid,
			'idsensor' => $objSensors->idsensor,
			'idroom' => $objSensors->idroom,
			'lobatt' => $objSensors->lobatt,
			'location' => $objSensors->location,
			'comments' => $objSensors->comments,
			'sensortype' => $objSensors->sensortype,
			'sensorquantity' => $objSensors->sensorquantity,
			'datastream' => $objSensors->datastream,
			'sensorscale' => $objSensors->sensorscale,
			'cum_gas_pulse' => $objSensors->cum_gas_pulse,
			'cum_water_pulse' => $objSensors->cum_water_pulse,
			'cum_elec_pulse' => $objSensors->cum_elec_pulse,
			'highalarm' => $objSensors->highalarm,
			'lowalarm' => $objSensors->lowalarm,
			'detailurl' => '<a href="'.$this->addToUrl('&item='.$objSensors->id).'">'. $objSensors->idsensor . '</a>',
			'monitorimg' => $monitoredurl,
			'machineimg' => $machineurl,
			'currentvalue' => $value,
		);
        
		return $newArray;
	}


	/**
	 * convert Elec log obj to array
	 * @param obj
	 * @return array
	 */
	protected function elec2arr($obj)
	{
		// note: each count is one Wh consumed.
		// for graphing we only need the timestamp
		$newArray = array
		(
			'tstamp' => $obj->tstamp,
            'value' => $obj->value,		
		);
		return $newArray;
	}

	/**
	 * calculate the difference between current TZ and UTC
	 * taking into account Daylight Savings Time
	 * note: not all values are present
	 * @param obj
	 * @return hours
	 */
	protected function tzdelta ( $iTime = 0 ) { 
        if ( 0 == $iTime ) { $iTime = time(); } 
        $ar = localtime ( $iTime ); 
        $ar[5] += 1900; $ar[4]++; 
        $iTztime = gmmktime ( $ar[2], $ar[1], $ar[0], $ar[4], $ar[3], $ar[5]); 
        return ( $iTztime - $iTime ); 
	} 

	/**
	 * List one Electricity chart
	 * and select more detail info
	 * @param id value
     * @param yr (year of graph) -- if yr = 0, then current graph
     * @param $graph (string - graph type)
	 * @return array
	 */

	protected function listElec ($id, $timestamp, $graph)
	{
        $arrData = array();
        $isdaily = true;
        $nodata = false;                    // do we have no data yet
       
        if (!isset($graph)) {               // else monthly
            $graph='daily';
        }
        
        if (strcmp($graph, 'daily') == 0) {
            $isdaily = true;
        } else {
            $isdaily = false;
        }

        if (!isset($timestamp) || $timestamp == NULL) {
            if ($isdaily) {
                $nrdays = 1;
            } else {
                $nrdays = date("t");            // php trick to return number of days in current month
            }
            $endtime = time() - 3600; // current time minus one hour
        	$starttime = $endtime - ($nrdays * 24 * 3600);
        	$timestamp = $starttime;
        } else {
            if ($isdaily) {
                $nrdays = 1;
            } else {
                $a = getdate ($timestamp);
                $nrdays = cal_days_in_month (CAL_GREGORIAN, $a["mon"], $a["year"]);
            }
        	$starttime = $timestamp;
        	$endtime = $starttime + ($nrdays * 24 * 3600);
        }

        if ($isdaily) {
		    $objs = $this->Database->prepare ("SELECT tstamp, value FROM HourlyEleclog WHERE tstamp>=? AND tstamp<=? ORDER BY tstamp DESC")->execute($starttime, $endtime);
	    } else {
		    $objs = $this->Database->prepare ("SELECT tstamp, value FROM DailyEleclog WHERE tstamp>=? AND tstamp<=? ORDER BY tstamp DESC")->execute($starttime, $endtime);
	    } 
	    
	    // test if $objs exists at all... otherwise no data
	    
		if ($objs->count() > 0) {
		    if ($objs->last()) {
			    $arrData[] = $this->elec2arr($objs,'');
			    while ($objs->prev()) {
					$arrData[] = $this->elec2arr($objs,'');
				}
			}
		} else {
		    $nodata = true;
		}

		// Now we create the datasets for the graphs...
		$count = 0;
		
		foreach ($arrData as $obj) {
			$count++;
			// add  UTC offset to get real time
			$time = ($obj['tstamp']+$this->tzdelta(0)) * 1000;
			// Prepare for javascript...
			$set1[] = "[" . $time . "," . $obj['value'] . "]";
		}
        
        
       	$this->strTemplate = 'mod_elec';
        $this->Template = new FrontendTemplate ($this->strTemplate);
        // Assign data to the template
        $this->Template->date = $timestamp;
        $this->Template->sensorid = $id;
        // For debugging
        $this->Template->nrdays = $nrdays;
        $this->Template->start = $starttime;
        $this->Template->end = $endtime;
        
        
        if ($isdaily) {
            $previous = strtotime ("yesterday", $timestamp);
            $next = strtotime ("tomorrow", $timestamp);
        } else {
            $previous = strtotime ("first day of previous month", $timestamp);
            $next = strtotime ("first day of next month", $timestamp);
        }            

        // Start building the title, first get timestamps of yesterday and tomorrow
        
        if ($timestamp == 0 or !isset($timestamp)) {
        	$title = '<a href="index.php/Electricity/item/'.$id.'/date/'.$previous.'/graph/'.$graph.'.html"><</a>&nbsp;';
            $title .= 'Last 24 hours &nbsp;';
        } else {
        	$title = '<a href="index.php/Electricity/item/'.$id.'/date/'.$previous.'/graph/'.$graph.'.html"><</a>&nbsp;';
            $title .= 'Date '.date("l, d-m-Y",$timestamp);
        	$title .= '&nbsp;<a href="index.php/Electricity/item/'.$id.'/date/'.$next.'/graph/'.$graph.'.html">></a>&nbsp;';
        }
        

        // Create the appropriate graph
	    $this->Template->title = 'Electricity &nbsp;'.$title;
	    if ($nodata) {
		    $this->Template->js1 = '';  
	    } else {
		    $this->Template->js1 = '['.implode(",",$set1).']';                           // dataset 1
		}
		$this->Template->l1 = ' Usage W';    // legends for the dataset

    }

	
	/**
	 * Generate module
	 */
	protected function compile()
	{
		$arrSensors = array();

		// Select appropriate Sensors
		$this->sensorids = $this->accessSensors();
		$this->strTemplate = 'mod_elec_sensors';
		$this->Template = new FrontendTemplate ($this->strTemplate);

		// item is an idsensor...
        // date is the date....
        $item = $this->Input->get('item');
        $date = $this->Input->get('date');
        $graph = $this->Input->get('graph');


        // we may have a location... if so, collect Sensor's at that location only
        $location = $this->Input->get('location');
        if (strlen($location) != 0) {
            $this->sensorids = $this->locationSensors($location);
        }
        
		// item is either an id, or an idsensor... so check for both
        // $this->log('Sensor '.$item.' strlen '.strlen($item),__METHOD__,'INFO');
		if (strlen($item) != 0) {
			// MySQL compares strings and integers in such a way that '08-8004' equals id = 8... we can't use that
			// So first check if there is a customer id, and if so, use that as $item
			$prow = $this->Database->execute("SELECT id FROM Sensor WHERE id='". $item . "' LIMIT 1");
            // $this->log('Sensor rows '.$prow->numRows,__METHOD__,'INFO');
			if ($prow->numRows == 1) {
				$item = $prow->id;
			}
			
			// check if item in the array of $this->custids, so that we have access
			if (is_array($this->sensorids) && (in_array($item, $this->sensorids, $strict = null))) {
				// ok, we are allowed to see that item... check if it is a graph or just a listing 
				$this->listElec($item, $date, $graph);
			} else {
				// can not see this sensor,
				$this->strTemplate = 'mod_sensor_error';
				$this->Template = new FrontendTemplate ($this->strTemplate);
				$this->Template->id = $this->Input->get('item');
				return '';
			}
		} else {
			// Return if there are no Sensors
			if (!is_array($this->sensorids) || count($this->sensorids) < 1)
			{
				$arrSensors[] = array
				(
					'id' => 'No valid sensors found',
					'pid' => '',
					'tstamp' => '',
					'uid' => '',
					'name' => '',
					'idsensor' => '',
					'detailurl' => 'No valid sensors found',
				);
        
				$this->Template->sensors = $arrSensors;
				return '';
			}
			
			// Fetch data from the database
			$objSensors = $this->Database->execute("SELECT * FROM Sensor WHERE id IN (". implode(',', array_map('intval', $this->sensorids)) . ") ORDER BY idsensor");
		
			// Put sensors into array
			while ($objSensors->next())
			{
					$arrSensors[] = $this->sensor2arr($objSensors);
			}
	
			// Assign data to the template
			$this->Template->sensors = $arrSensors;
		} // if-else
	}
	
}

?>
