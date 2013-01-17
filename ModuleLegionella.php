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
// <summary>config.php defines the sensor module in contao</summary>

// <summary>Legionella - Shows Legionella graphs for a location</summary>

 /**
 * Class ModuleLegionella
 *
 * Front end module "Legionella".
 * @package    Controller
 */
class ModuleLegionella extends Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_legionella';
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

			$objTemplate->wildcard = '### LEGIONELLA GRAPH ###';
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
	 * convert weekly obj to array
	 * @param obj
	 * @return array
	 */
	protected function weekly2arr($obj)
	{
       
        $newArray = array
		(
			'year' => $obj->year,
			'week' => $obj->week,
			'Delta_Flow_total_m3' => $obj->Delta_Flow_total_m3,
			'Flow_top_value_lmin' => $obj->Flow_top_value_lmin,
		);
		return $newArray;
	}

	/**
	 * convert legionella obj to array
	 * @param obj
	 * @return array
	 */
	protected function legio2arr($obj)
	{
        $newArray = array
		(
			'year' => $obj->year,
			'week' => $obj->week,
            'idx' => $obj->year*100 + $obj->week,   // an index like 201203
//          'date' => $this->parseDate($GLOBALS['TL_CONFIG']['dateFormat'], $obj->date),
//			change to month and two digit year only
            'date' => date("M y", $obj->date),
			'Legionella' => $obj->Legionella,
			'Colonies' => $obj->Colonies,
            'Cu' => $obj->Cu,
            'Ag' => $obj->Ag,
            'Temperature' => $obj->Temperature,
            'pH' => $obj->pH,
            'Conductivity' => $obj->Conductivity,
            'Hardness' => $obj->Hardness,
		);
		return $newArray;
	}

    /**
	 * convert obj to array
	 * @param obj
	 * @return array
	 */
	protected function loc2arr($objLocs)
	{
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
            'DirectTel' => $objLocs->DirectTel,
            'MobileTel' => $objLocs->MobileTel,
            'email' => $objLocs->email,
            'comments' => $objLocs->comments,
            'NrSamples'	=> $objLocs->NrSamples,
            'SampleFrequency'	=> $objLocs->SampleFrequency,
            'Laboratory'	=> $objLocs->Laboratory,
            'id'	=> $objLocs->id,
            'detailurl' => '<a href="'.$this->addToUrl('&item='.$objLocs->id).'">'. $objLocs->name . '</a>',
        );
        return $newArray;
	}

   
	/**
	 * List one Legionella chart
	 * and select more detail info
	 * @param id value
     * @param yr (year of graph) -- if yr = 0, then current graph
	 * @return array
	 */
	protected function listGraph($id, $yr, $graph)
	{
        $arrWeekly = array();
        $arrLegio = array();
        
        // Fetch Location data from the database
        $locobjs = $this->Database->prepare ("SELECT * FROM Location WHERE id=?")->execute($id);

        // Put location into array
        while ($locobjs->next())
        {
                $arrLocs = $this->loc2arr($locobjs);
        }

        // Now retrieve some essential information for descriptions, Sensor types etc
        $bifi = $this->Database->prepare ("SELECT id, idsensor, Location, El_Type, IsCooling, IsPond FROM Sensor WHERE Sensor.idLocation=?")->execute($id);
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
        
        // Now retrieve the Weekly log records, and sum for all Sensor's. If no year, get the last 52 weeks
/*        if ($yr == 0) {
            $objs = $this->Database->prepare ("SELECT year, week,
                                               SUM(Delta_Flow_total_m3) as Delta_Flow_total_m3,
                                               SUM(Flow_top_value_lmin) as Flow_top_value_lmin
                                               FROM Weeklylog
                                               JOIN Sensor
                                               WHERE Weeklylog.idsensor=Sensor.idsensor
                                               AND Sensor.idLocation=?
                                               GROUP BY year, week
                                               ORDER BY ts DESC")->limit(52)->execute($id);
            // Now we have weeklylog objects for graphing
            // since the order is reversed, go to the last row first, and then find each previous record
            if ($objs->last()) {
                $arrWeekly[] = $this->weekly2arr($objs);
                while ($objs->prev()) {
                        $arrWeekly[] = $this->weekly2arr($objs);
                }
            }
        } else {
            $objs = $this->Database->prepare ("SELECT year, week,
                                               SUM(Delta_Flow_total_m3) as Delta_Flow_total_m3,
                                               SUM(Flow_top_value_lmin) as Flow_top_value_lmin
                                               FROM Weeklylog
                                               JOIN Sensor
                                               WHERE Weeklylog.idsensor=Sensor.idsensor
                                               AND Sensor.idLocation=? AND year=?
                                               GROUP BY year, week
                                               ORDER BY week ASC")->execute($id, $yr);
            // Now we have weeklylog objects for graphing
            while ($objs->next()) {
                    $arrWeekly[] = $this->weekly2arr($objs);
            }
        }

        // Now we create the datasets for the graphs...
        $flow = array();        // this the the Delta_Flow_total_m3
        $maxflow = array();     // this is the Flow_top_value_lmin
        
        foreach ($arrWeekly as $obj) {
            $count++;
            $week[] = $obj['week'];
            if ($obj['week'] > $maxweek) $maxweek = $obj['week'];
            if ($obj['week'] < $minweek) $minweek = $obj['week'];
            
            $flow[] = $obj['Delta_Flow_total_m3'];
            if ($obj['Delta_Flow_total_m3'] > $mxflow) $mxflow = $obj['Delta_Flow_total_m3'];
            $maxflow[] = $obj['Flow_top_value_lmin'];
            if ($obj['Flow_top_value_lmin'] > $maxmaxflow) $maxmaxflow = $obj['Flow_top_value_lmin'];
         }
*/
        
        // Now retrieve the Samples records, calculating the averages for this location. 
        $objs = $this->Database->prepare ("SELECT year(from_unixtime(date)) as year, week(from_unixtime(date)) as week,
                                           date,
                                           Avg(Legionella) as Legionella,
                                           Avg(Colonies) as Colonies,
                                           Avg(Cu) as Cu,
                                           Avg(Ag) as Ag,
                                           Avg(Temperature) as Temperature,
                                           Avg(pH) as pH,
                                           Avg(Conductivity) as Conductivity,
                                           Avg(Hardness) as Hardness
                                           FROM Samples
                                           WHERE idLocation=?
                                           GROUP BY date
                                           ORDER BY date DESC")->execute($id);
        // Now we have objects for graphing
        // since the order is reversed, go to the last row first, and then find each previous record
        if ($objs->last()) {
            $arrLegio[] = $this->legio2arr($objs);
            while ($objs->prev()) {
                    $arrLegio[] = $this->legio2arr($objs);
            }
        }

        $legionella = array();
        $colonies = array();
        $cu = array();
        $ag = array();
        $t = array();
        $ph = array();
        $cond = array();
        $hard = array();
        
        
        // Now we need to use a Cubic Spline function to create data for the missing weeks...
        $i = 0;
        foreach ($arrLegio as $obj) {
            $xdata[] = $i * 10;
            $week[] = $obj['date'];
            $yleg[] = $obj['Legionella'];
            $ycol[] = $obj['Colonies'];
            $ycu[] = $obj['Cu'];
            $yag[] = $obj['Ag'];
            $yt[] = $obj['Temperature'];
            $yph[] = $obj['pH'];
            $ycond[] = $obj['Conductivity'];
            $yhard[] = $obj['Hardness'];
            $i++;
        }
        // number of points on graph
        $count = 50;
 
        $this->strTemplate = 'mod_legionella_detail';
        $this->Template = new FrontendTemplate ($this->strTemplate);
        // Assign data to the template
        $this->Template->location = $arrLocs;
        $this->Template->year = $yr;
        $selector = '';
        if ($i > 3) {
            $selector .= '<a href="'.$this->addToUrl('&item='.$id.'&graph=legsmth').'">Legionella smoothed</a>&nbsp;';
        }
        if ($i > 1) {
            $selector .= '<a href="'.$this->addToUrl('&item='.$id.'&graph=legraw').'">Legionella</a>&nbsp;';
        }
        $selector .= '<a href="'.$this->addToUrl('&item='.$id.'&graph=other').'">Temperature, Ag and Cu</a>&nbsp;';
        if ($bCooling) {
            $selector .= '<a href="'.$this->addToUrl('&item='.$id.'&graph=qual').'">Water Quality</a>&nbsp;';
        }
        $this->Template->links = $selector;

        // Start building the title
        $title = 'Average of all samples|'.$arrLocs['name'].',&nbsp;'.$arrLocs['city'];
 
        if (strcmp($graph, 'legsmth') == 0) {
            // the smoothed legionella graph
            require('Spline.php');
            $spline_leg = new Spline($xdata,$yleg);
            // For the new data set we want $count points to get a smooth curve.
            list($wk1,$legionella) = $spline_leg->Get($count);
            $spline_col = new Spline($xdata,$ycol);
            // For the new data set we want $count points to get a smooth curve.
            list($wk2,$colonies) = $spline_col->Get($count);

            // the legionella graph
            $this->Template->title = 'Legionella (smoothed)|'.$title;
            $this->Template->r_max = round(max($legionella)+5, -1);               // maximum y value
            // $this->Template->r_min = round(min($legionella)-5, -1);               // maximum y value
            $this->Template->r_min = 0;               // maximum y value
            $this->Template->y_max = round(max($colonies)+5, -1);         // maximum r value
            // $this->Template->y_min = round(min($colonies)-5, -1);         // maximum r value
            $this->Template->y_min = 0;         // maximum r value
            $y_range = $this->Template->y_max - $this->Template->y_min;
            $r_range = $this->Template->r_max - $this->Template->r_min;
            // scale the datasets so that the max value equals around 100
            // scale and shift to 0-100
            foreach ($legionella as &$value) {
                $value = max(0, round(($value+abs($this->Template->r_min)) / $r_range * 100, 1));
                // PvE: don't let go below 0
            }
            foreach ($colonies as &$value) {
                $value = max(0, round(($value+abs($this->Template->y_min)) / $y_range * 100, 1));
                // PvE: don't let go below 0
            }
            // determine at what point 100 kve/l is, and create a gradient fill for that
            // here the scale is 0.0-1.0, so no multiplication x 100
            $limit = round((100+abs($this->Template->r_min)) / $r_range, 3);
            // colour 1, white, colour 2, orange
            $this->Template->colors = array("FFFFFF", $limit, "F5BCA9", $limit*2);    

            $this->Template->week = $week;                                // the x axis, by week, but only of the real sample dates
            $this->Template->ds2 = $legionella;                           // dataset 1
            $this->Template->ds1 = $colonies;
            $this->Template->legend = array("colonies (n/ml)", "legionella (kve)");    // legends for the dataset
            $this->Template->x_unit = array("date");                // units for the dataset
            $this->Template->r_unit = array("kve");                  // units for the dataset
            $this->Template->y_unit = array("n/ml");                  // units for the dataset
        } elseif (strcmp($graph, 'other') == 0) {
            // show temperature, cu and ag
            require('Spline.php');
            $spline_temp = new Spline($xdata,$yt);
            list($wk1,$t) = $spline_temp->Get($count);
            $spline_cu = new Spline($xdata,$ycu);
            list($wk2,$cu) = $spline_cu->Get($count);
            $spline_ag = new Spline($xdata,$yag);
            list($wk2,$ag) = $spline_ag->Get($count);

            // the legionella graph
            $this->Template->title = 'Temperature, Ag and '.$eltype.' |'.$title;
            $this->Template->r_max = round(max($t)+5, -1);               // maximum r value
            // $this->Template->r_min = round(min($t)-5, -1);               // minimum r value
            $this->Template->r_min = 0;               // minimum r value
            $this->Template->y_max = round(max($cu)+5, -1);         // maximum y value
            // $this->Template->y_min = round(min($cu)-5, -1);         // maximum y value
            $this->Template->y_min = 0;         // maximum y value
            $y_range = $this->Template->y_max - $this->Template->y_min;
            $r_range = $this->Template->r_max - $this->Template->r_min;
            // scale the datasets so that the max value equals around 100
            // scale and shift to 0-100
            foreach ($t as &$value) {
                $value = round(($value+abs($this->Template->r_min)) / $r_range * 100, 1);
            }
            foreach ($cu as &$value) {
                $value = round(($value+abs($this->Template->y_min)) / $y_range * 100, 1);
            }
            foreach ($ag as &$value) {
                $value = round(($value+abs($this->Template->y_min)) / $y_range * 100, 1);
            }
            $this->Template->week = $week;                                // the x axis, by week, but only of the real sample dates
            $this->Template->ds3 = $t; 
            $this->Template->ds2 = $ag; 
            $this->Template->ds1 = $cu;
            $this->Template->legend = array( $eltype." (&#181;g/l)", "Ag (&#181;g/l)", "Temperature");    // legends for the dataset
            $this->Template->x_unit = array("date");                // units for the dataset
            $this->Template->r_unit = array("&deg;C");                  // degrees celcius units for the dataset
            $this->Template->y_unit = array("&#181;g/l");                  // units for the dataset
        } elseif (strcmp($graph, 'qual') == 0) {
            // show pH, Conductivity and Hardness
            require('Spline.php');
            $spline_ph = new Spline($xdata,$yph);
            list($wk1,$ph) = $spline_ph->Get($count);
            $spline_cond = new Spline($xdata,$ycond);
            list($wk2,$cond) = $spline_cond->Get($count);
            $spline_hard = new Spline($xdata,$yhard);
            list($wk2,$hard) = $spline_hard->Get($count);

            // the legionella graph
            $this->Template->title = 'pH, Hardness, Conductivity |'.$title;
            // scale for dH is 0..30, overlay pH (0-14) op y as
            $this->Template->r_max = round(max($cond)+5, -1);               // maximum r value
            $this->Template->r_min = 0;               // minimum r value
            $this->Template->y_max = 30;         // maximum y value
            $this->Template->y_min = 0;         // maximum y value
            $y_range = $this->Template->y_max - $this->Template->y_min;
            $r_range = $this->Template->r_max - $this->Template->r_min;
            // scale the datasets so that the max value equals around 100
            // scale and shift to 0-100
            foreach ($cond as &$value) {
                $value = round(($value+abs($this->Template->r_min)) / $r_range * 100, 1);
            }
            foreach ($ph as &$value) {
                $value = round(($value+abs($this->Template->y_min)) / $y_range * 100, 1);
            }
            foreach ($hard as &$value) {
                $value = round(($value+abs($this->Template->y_min)) / $y_range * 100, 1);
            }
            $this->Template->week = $week;                                // the x axis, by week, but only of the real sample dates
            $this->Template->ds3 = $cond; 
            $this->Template->ds2 = $hard; 
            $this->Template->ds1 = $ph;
            $this->Template->legend = array( "pH", "Hardness", "Conductivity");    // legends for the dataset
            $this->Template->x_unit = array("date");                // units for the dataset
            $this->Template->r_unit = array("mS/cm");                  // conductivity
            $this->Template->y_unit = array("pH or dH");                  // units for the dataset
        } else {
            // default graph, unsmoothed legionella
            // $this->Template->scatter = true;
            $this->Template->title = 'Legionella (unsmoothed)|'.$title;
            $this->Template->r_max = round(max($yleg)+5, -1);               // maximum y value
            // $this->Template->r_min = round(min($yleg)+5, -1);               // maximum y value
            $this->Template->r_min = 0;               // maximum y value
            $this->Template->y_max = round(max($ycol)+5, -1);         // maximum r value
            // $this->Template->y_min = round(min($ycol)+5, -1);         // maximum r value
            $this->Template->y_min = 0;         // maximum r value
            $y_range = $this->Template->y_max - $this->Template->y_min;
            $r_range = $this->Template->r_max - $this->Template->r_min;
            // scale the datasets so that the max value equals around 100
            // scale and shift to 0-100
            foreach ($yleg as &$value) {
                $value = round(($value+abs($this->Template->r_min)) / $r_range * 100, 1);
            }
            foreach ($ycol as &$value) {
                $value = round(($value+abs($this->Template->y_min)) / $y_range * 100, 1);
            }
            // determine at what point 100 kve/l is, and create a gradient fill for that
            // here the scale is 0.0-1.0, so no multiplication x 100
            $limit = round((100+abs($this->Template->r_min)) / $r_range, 3);
            // colour 1, white, colour 2, orange
            $this->Template->colors = array("FFFFFF", $limit, "F5BCA9", $limit*2);    

            $this->Template->week = $week;                                // the x axis, by week, but only of the real sample dates
            $this->Template->ds2 = $yleg;                           // dataset 1
            $this->Template->ds1 = $ycol;
            $this->Template->legend = array("colonies (n/ml)", "legionella (kve)");    // legends for the dataset
            $this->Template->x_unit = array("date");                // units for the dataset
            $this->Template->r_unit = array("kve");                  // units for the dataset
            $this->Template->y_unit = array("n/ml");                  // units for the dataset
        }
    }

	
	/**
	 * Generate module
	 */
	protected function compile()
	{
		$arrStats = array();
		
		// Select appropriate Locations
		$this->locids = $this->accessLocations();

		// item is an Location id
        // year is the year....
        $item = $this->Input->get('item');
        $year = $this->Input->get('year');
        if (strlen($year) == 0) {
            // set default year
            $year = 0;
        }
        $graph = $this->Input->get('graph');
        if (strlen($graph) == 0) {
            // set default graph
            $graph = 'legsmth';
        }

        $this->strTemplate = 'mod_legionella';
        $this->Template = new FrontendTemplate ($this->strTemplate);
        
        // $this->log('Statistics '.$year.' strlen '.strlen($year),__METHOD__,'INFO');
		if ((strlen($item) != 0)) {
			// So first check if there is a location id
			if (is_array($this->locids) && (in_array($item, $this->locids, $strict = null))) {
				// ok, we are allowed to see that item... 
				$this->listGraph($item, $year, $graph);
			} else {
				// can not see this location,
				$this->strTemplate = 'mod_legionella_error';
				$this->Template = new FrontendTemplate ($this->strTemplate);
				$this->Template->id = $item;
                $this->Template->msg = "You do not have access to that record.";
				return '';
			}
		} else {
			// Return if there are no valid Locations's
			if (!is_array($this->locids) || count($this->locids) < 1)
			{
				// can not see this location,
				$this->strTemplate = 'mod_legionella_error';
				$this->Template = new FrontendTemplate ($this->strTemplate);
				$this->Template->id = $item;
                $this->Template->msg = "No valid locations found.";
				return '';
			}
			
			// For each of the allowed locations location id, select if there are samples
            foreach ($this->locids as $id) {
                $objs = $this->Database->prepare ("SELECT DISTINCT date
                                               FROM Samples
                                               WHERE idLocation=?
                                               ORDER BY date DESC")->execute($id);

                // Now retrieve some essential information for descriptions, Sensor types etc
                $bifi = $this->Database->prepare ("SELECT id, idsensor, Location, El_Type, IsCooling, IsPond FROM Sensor WHERE Sensor.idLocation=?")->execute($id);
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

                // Put selectors into a string
                $selector = '';
                // Only if we have more than 3 values, smooth it
                if ($objs->numRows > 3) {
                    $selector .= '<a href="'.$this->addToUrl('&item='.$id.'&graph=legsmth').'">Legionella smoothed</a>&nbsp;';
                }
                if ($objs->numRows > 1) {
                    $selector .= '<a href="'.$this->addToUrl('&item='.$id.'&graph=legraw').'">Legionella</a>&nbsp;';
                    $selector .= '<a href="'.$this->addToUrl('&item='.$id.'&graph=other').'">Temperature, Ag and Cu</a>&nbsp;';
                    if ($bCooling) {
                        $selector .= '<a href="'.$this->addToUrl('&item='.$id.'&graph=qual').'">Water Quality</a>&nbsp;';
                    }
                    $arrStats[] = array
                    (
                        'id' => $id,
                        'selector' => $selector,
                    );
                }

            } // foreach
        
			// Assign data to the template
			$this->Template->stats = $arrStats;
		} // if-else
	}
	
}

?>