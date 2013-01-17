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
// Plotting is now with Javascript Flot library (http://www.flotcharts.org)
//
// </copyright>
// <author>Peter van Es</author>
// <version>1.2</version>
// <email>vanesp@escurio.com</email>
// <date>2013-01-01</date>

// Version changes: 1.2, 2013-01-01 - remove id from log records

 /**
 * Class ModuleSensors
 *
 * Front end module "Sensors".
 * @package    Controller
 */
class ModuleSensors extends Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_sensors';
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

			$objTemplate->wildcard = '### SENSORS LIST ###';
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
			$objSensors = $this->Database->prepare("SELECT id FROM Sensor ORDER BY id")->execute();
		} else {
			$objSensors = $this->Database->prepare("SELECT DISTINCT Sensor.id AS id FROM Sensor, Location, Customer WHERE (LOWER(Customer.email)=? OR LOWER(Location.email)=?) AND (Sensor.pid=Customer.id OR Sensor.pid=Location.id) AND Location.pid=Customer.id ORDER BY Sensor.id")->execute($objMember->email, $objMember->email);
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
			$objSensors = $this->Database->prepare("SELECT id FROM Sensor WHERE pid=? ORDER BY id")->execute($loc);
		} else {
			$objSensors = $this->Database->prepare("SELECT DISTINCT Sensor.id AS id FROM Sensor, Location, Customer
            WHERE (LOWER(Customer.email)=? OR LOWER(Location.email)=?)
            AND (Sensor.pid=Customer.id OR Sensor.pid=Location.id)
            AND Location.pid=Customer.id 
            AND Sensor.pid=?
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
		$monitoredurl = '<img src="/system/modules/sensor/html/Red.png" alt="Not recently Monitored" />';
		if ($last+6*60 >= $now) {
			// monitored within last 6 mins
			$monitoredurl = '<img src="/system/modules/sensor/html/Green.png" alt="Monitored within 6 mins" />';
		} else {
			if ($last+12*60 >= $now) {
				$monitoredurl = '<img src="/system/modules/sensor/html/Orange.png" alt="Monitored within 12 mins" />';
			}
		}
		
		// verify machine status
		if ($objSensors->lobatt == 1)  {
            // PvE:its a low battery
            $machineurl = '<img src="/system/modules/sensor/html/Red.png" alt="Battery dead" />';
		} else {
            $machineurl = '<img src="/system/modules/sensor/html/Green.png" alt="Battery ok" />';
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
	 * convert motion obj to array
	 * @param obj
	 * @return array
	 */
	protected function motion2arr($obj)
	{
		$newArray = array
		(
			'pid' => $obj->pid,
			'tstamp' => $this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'], $obj->tstamp),
		);
		return $newArray;
	}

	/**
	 * convert Action obj to array
	 * @param obj
	 * @return array
	 */
	protected function action2arr($obj)
	{
		$this->import('String');
	
		$newArray = array
		(
			'id' => $obj->id,
			'pid' => $obj->pid,
			'created' => $this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'], $obj->created),
			'uid' => $obj->uid,
            'name' => $obj->name,
			'comment' => $obj->comment,
		);
		return $newArray;
	}

	/**
	 * convert log obj to array
	 * note: not all values are present
	 * @param obj
	 * @return array
	 */
	protected function log2arr($obj, $qty)
	{
		// $val is a print out value... other values are from the records directly
		if ($qty != '' && $qty != 'Various') {
			$value = $obj->value . ' ' . $qty;
		} else {
			// it's a room node (put table in there)
			$value = 'L: '.$obj->light. ' %</td><td>RH: '.$obj->humidity. ' %</td><td>T: '.$obj->temp. ' &deg;C';
		}
		$newArray = array
		(
			'pid' => $obj->pid,
			'ts' => $this->parseDate($GLOBALS['TL_CONFIG']['datimFormat'], $obj->tstamp),
			'tstamp' =>  $obj->tstamp,
			'val' => $value,
			'value' => $obj->value,
			'light' => $obj->light,
			'humidity' => $obj->humidity,
			'temp' => $obj->temp,
			'year' => $obj->year,
			'month' => $obj->month,
			'day' => $obj->day,
			'hour' => $obj->hour,
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
	 * List one Sensor chart
	 * and select more detail info
	 * @param id value
     * @param yr (year of graph) -- if yr = 0, then current graph
     * @param $graph (string - graph type)
	 * @return array
	 */

	protected function listGraph($id, $timestamp, $graph)
	{
        $arrData = array();
        $bRoom = false;

        // Fetch Sensor data from the database
        $sensObjs = $this->Database->prepare ("SELECT * FROM Sensor WHERE id=?")->limit(1)->execute($id);

        // Put sensors into array
        while ($sensObjs->next())
        {
                $arrSensor = $this->sensor2arr($sensObjs);
        }

        if (strcmp($arrSensor['sensortype'], 'RNR') == 0) {
        	$bRoom = true;
        }
        
        if (!isset($timestamp)) {
            $endtime = time();
        	$starttime = $endtime - (24 * 3600);
        } else {
        	$starttime = $timestamp;
        	$endtime = $starttime + (24 * 3600);
        }

        if ((strcmp($graph, 'values') == 0) || (strcmp($graph, 'room') == 0)) {
            if ($bRoom) {
				// Now retrieve the Roomlog, if it is a room sensor, limit to last 24 items
				$objs = $this->Database->prepare ("SELECT * FROM Roomlog WHERE pid=? AND tstamp>=? AND tstamp<=? ORDER BY tstamp DESC")->execute($id, $starttime, $endtime);
			} else {
				// Retrieve the Sensorlog
				$objs = $this->Database->prepare ("SELECT * FROM Sensorlog WHERE pid=?  AND tstamp>=? AND tstamp<=? ORDER BY tstamp DESC")->execute($id, $starttime, $endtime);
			}
			if ($objs->last()) {
				$arrData[] = $this->log2arr($objs,'');
				while ($objs->prev()) {
						$arrData[] = $this->log2arr($objs,'');
				}
			}
		}

        if (strcmp($graph, 'motion') == 0) {
        	$bRoom = false;	// we don't want to show the bRoom record
			// Now retrieve the Motionlog
			$objs = $this->Database->prepare ("SELECT * FROM Motionlog WHERE pid=? AND tstamp>=? AND tstamp<=? ORDER BY tstamp")->execute($id, $starttime, $endtime);
			$inmotion = 0;	// state in motion?
			$currenttime = $starttime; 
			$newA = array
				(
					'tstamp' =>  $currenttime,
					'value' => $inmotion,
				);
			$arrData[] = $newA;
			while ($objs->next()) {
				// gather all data objects
				// we look at the last one... and assume motion lasts for 1 minutes (=60 secs)
				if ($inmotion == 1) {
					// we are in motion,
					if ($objs->tstamp <= ($currenttime + 60)) {
						// and we stay in motion so absorb this record
						$currenttime = $objs->tstamp;
					} else {
						// we were out of motion, so record that
						$inmotion = 0;
						$currenttime += 60;		// add 60 seconds
						$newA['tstamp'] = $currenttime;
						$newA['value'] = $inmotion;
						$arrData[] = $newA;
						// we were out of motion, so record that
						$inmotion = 0;
						$currenttime = $objs->tstamp - 1;		// right up to the moment
						$newA['tstamp'] = $currenttime;
						$newA['value'] = $inmotion;
						$arrData[] = $newA;
						// and now add the new motion record
						$inmotion = 1;
						$currenttime = $objs->tstamp;
						$newA['tstamp'] = $currenttime;
						$newA['value'] = $inmotion;
						$arrData[] = $newA;
					} // else timestamp
				} else {
					// we were out of motion, so record that
					$inmotion = 0;
					$currenttime = $objs->tstamp - 1;		// right up to the moment
					$newA['tstamp'] = $currenttime;
					$newA['value'] = $inmotion;
					$arrData[] = $newA;
					// and now add the new motion record
					$inmotion = 1;
					$currenttime = $objs->tstamp;
					$newA['tstamp'] = $currenttime;
					$newA['value'] = $inmotion;
					$arrData[] = $newA;
				}
			} // while
		} //if strcmp motion

            
		// Now we create the datasets for the graphs...
		$count = 0;
		
		foreach ($arrData as $obj) {
			$count++;
			// add  UTC offset to get real time
			$time = ($obj['tstamp']+$this->tzdelta(0)) * 1000;
			if ($bRoom) {                
				// Prepare for javascript...
				$set1[] = "[" . $time . "," . $obj['temp'] . "]";
				$set2[] = "[" . $time . "," . $obj['light'] . "]";
				$set3[] = "[" . $time . "," . $obj['humidity']."]";
			} else {
				$set1[] = "[" . $time . "," . $obj['value'] . "]";
			}
			
		}
        
        
        if ($bRoom) {    
        	$this->strTemplate = 'mod_stat_detail3';
    	} else {
    		// only one dataset
        	$this->strTemplate = 'mod_stat_detail1';
    	}
        $this->Template = new FrontendTemplate ($this->strTemplate);
        // Assign data to the template
        $this->Template->sensor = $arrSensor;
        $this->Template->bRoom = $bRoom;
        $this->Template->date = $timestamp;
        // Start building the title, first get timestamps of yesterday and tomorrow
        
        if ($timestamp == 0 or !isset($timestamp)) {
        	$yesterday = strtotime ("yesterday");
        	$title = '<a href="index.php/Sensors/item/'.$id.'/date/'.$yesterday.'/graph/'.$graph.'.html"><</a>&nbsp;';
            $title .= 'Last 24 hours &nbsp;'.$arrSensor['idsensor'].'&nbsp;'.$arrSensor['location'];
        } else {
        	$yesterday = strtotime ("yesterday", $timestamp);
        	$tomorrow = strtotime ("tomorrow", $timestamp);
        	$title = '<a href="index.php/Sensors/item/'.$id.'/date/'.$yesterday.'/graph/'.$graph.'.html"><</a>&nbsp;';
            $title .= 'Date '.date("l, d-m-Y",$timestamp);
        	$title .= '&nbsp;<a href="index.php/Sensors/item/'.$id.'/date/'.$tomorrow.'/graph/'.$graph.'.html">></a>&nbsp;';
            $title .= $arrSensor['idsensor'].'&nbsp;'.$arrSensor['location'];
        }
        

       // Create the appropriate graph
        if (strcmp($graph, 'values') == 0) {
            // values graphs
            if ($bRoom) {
				$this->Template->title = 'Room Node &nbsp;'.$title;
				$this->Template->js1 = '['.implode(",",$set1).']';                           // dataset 1
				$this->Template->js2 = '['.implode(",",$set2).']';                           // dataset 2
				$this->Template->js3 = '['.implode(",",$set3).']';                           // dataset 3
				$this->Template->l1 = "&deg;C";                           // legend 1
				$this->Template->l2 = "light";                           // legend 2
				$this->Template->l3 = "% RH";                           // legend 3
			} else {
				// single quantity
				$this->Template->title = 'Sensor &nbsp;'.$title;
				$this->Template->js1 = '['.implode(",",$set1).']';                           // dataset 1
				$this->Template->l1 = $arrSensor['location'] . ' ' . $arrSensor['sensorquantity'];    // legends for the dataset
			} // if bRoom
        } elseif (strcmp($graph, 'motion') == 0) {
				$this->Template->title = 'Motion Sensor &nbsp;'.$title;
				$this->Template->js1 = '['.implode(",",$set1).']';                           // dataset 1
				$this->Template->l1 = 'Motion detected';    // legends for the dataset
		} 

    }


	/**
	 * List one sensor
	 * and select more detail info
	 * @param id value
	 * @return array
	 */
	protected function listSensor($id)
	{
			$arrAction = array();
            $arrLog = array();
			
            $this->strTemplate = 'mod_sensor_detail';
			$this->Template = new FrontendTemplate ($this->strTemplate);

			// Fetch data from the database
			$objs = $this->Database->prepare ("SELECT Sensor.*, tl_user.name as name FROM Sensor, tl_user WHERE Sensor.id=? AND tl_user.id = Sensor.uid ")->limit(1)->execute($id);
	
			// Put sensors into array
			while ($objs->next())
			{
					$arrSensor = $this->sensor2arr($objs);
			}
			$this->Template->sensor = $arrSensor;
			$idsensor = $objs->id;
			$qty = $objs->sensorquantity;

            if ($objs->sensortype == "RNR") {
				// Now retrieve the Roomlog, if it is a room sensor, limit to last 32 items
				$objs = $this->Database->prepare ("SELECT * FROM HourlyRoomlog WHERE pid=? ORDER BY tstamp DESC")->limit(24)->execute($idsensor);
			} else {
				// Now retrieve the Roomlog, if it is a room sensor, limit to last 32 items
				$objs = $this->Database->prepare ("SELECT * FROM Sensorlog WHERE pid=? ORDER BY tstamp DESC")->limit(24)->execute($idsensor);
			}
		
			// Put logs into array
			while ($objs->next())
			{
					$arrLog[] = $this->log2arr($objs, $qty);
			}
			// Assign data to the template
			$this->Template->statuslog = $arrLog;		
	
			// Assign data to the template
			$this->Template->sensor = $arrSensor;
			// Now retrieve the Action records
			$objs = $this->Database->prepare ("SELECT Actionlog.*, tl_user.name as name FROM Actionlog, tl_user WHERE pid=? AND tl_user.id = Actionlog.uid ORDER BY created DESC")->limit(10)->execute($id);
	
			// Put statuses into array
			while ($objs->next())
			{
					$arrAction[] = $this->action2arr($objs);
			}
			// Assign data to the template
			$this->Template->actionlog = $arrAction;

	}

	
	/**
	 * Generate module
	 */
	protected function compile()
	{
		$arrSensors = array();

		// Select appropriate Sensors
		$this->sensorids = $this->accessSensors();
		$this->strTemplate = 'mod_sensors';
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
		        if (strlen($graph) == 0) {
					$this->listSensor($item);
				} else {
					$this->listGraph($item, $date, $graph);
				}
				
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
