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
 * Class ModuleStatistics
 *
 * Front end module "Statistics".
 * @package    Controller
 */
class ModuleStatistics extends Module
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'mod_stat';
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

			$objTemplate->wildcard = '### STATISTICS LIST ###';
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
		// $username = $GLOBALS['TL_USERNAME'];	// or we select on $this->User->username;
		$username = $this->User->username;
		$objUser = $this->Database->prepare("SELECT email FROM tl_member WHERE username=?")->limit(1)->execute($username);
		// $objUser->email contains the email address... we have the special case of a holland environment or hwt address
		// first make it lower case
		$objUser->email = strtolower($objUser->email);
		
		// simplify the above into a single query for performance reasons
		if (strpos($objUser->email, '@hollandenvironment.com') || strpos($objUser->email, '@hollandwatertechnology.com') || BE_USER_LOGGED_IN) {
			// we accept all sensors and we have a HWTuser
            $this->bContaoUser = true;
			$objSensors = $this->Database->prepare("SELECT id FROM Sensor ORDER BY id")->execute();
		} else {
			$objSensors = $this->Database->prepare("SELECT DISTINCT Sensor.id AS id FROM Sensor, Location, Customer WHERE (LOWER(Customer.email)=? OR LOWER(Location.email)=?  OR LOWER(Sensor.AlarmEmail)=?) AND (Sensor.pid=Customer.id OR Sensor.idLocation=Location.id) AND Location.pid=Customer.id ORDER BY Sensor.id")->execute($objUser->email, $objUser->email, $objUser->email);
		}

		while ($objSensors->next())
		{
			$arrSensors[] = $objSensors->id;
		}

		return $arrSensors;
	}
		

	/**
	 * convert weekly obj to array
	 * @param obj
	 * @return array
	 */
	protected function weekly2arr($obj)
	{
 
        if ($obj->Ag_top_value_calc > 0.001) {
            $ag_ratio = $obj->Ag_top_value_measured / $obj->Ag_top_value_calc * 100;
        } else {
            $ag_ratio = 1000;
        }
        if ($obj->Cu_top_value_calc > 0.001) {
            $cu_ratio = $obj->Cu_top_value_measured / $obj->Cu_top_value_calc * 100;
        } else {
            $cu_ratio = 1000;
        }
        
        $newArray = array
		(
			'idsensor' => $obj->idsensor,
			'year' => $obj->year,
			'week' => $obj->week,
			'Delta_Flow_total_m3' => $obj->Delta_Flow_total_m3,
			'Delta_mA_Cu_sec' => $obj->Delta_mA_Cu_sec,
			'Delta_mA_Ag_sec' => $obj->Delta_mA_Ag_sec,
			'Flow_top_value_lmin' => $obj->Flow_top_value_lmin,
			'Cu_top_value_calc' => $obj->Cu_top_value_calc,
			'Cu_top_value_measured' => $obj->Cu_top_value_measured,
			'Ag_top_value_calc' => $obj->Ag_top_value_calc,
			'Ag_top_value_measured' => $obj->Ag_top_value_measured,
			'Delta_Cu_g' => $obj->Delta_Cu_g,
			'Delta_Ag_g' => $obj->Delta_Ag_g,
			'Cu_conc' => $obj->Cu_conc,
			'Ag_conc' => $obj->Ag_conc,
			'Cu_cm_ratio' => $cu_ratio,
			'Ag_cm_ratio' => $ag_ratio,          
		);
		return $newArray;
	}

	/**
	 * convert Coolinglog obj to array
	 * @param obj
	 * @return array
	 */
	protected function cooling2arr($obj)
	{
        $newArray = array
		(
			'idsensor' => $obj->idsensor,
			'year' => $obj->year,
			'week' => $obj->week,
			'Delta_Flow_total_m3' => $obj->Delta_Flow_total_m3,
			'Ratio_SupplyDrain' => $obj->Ratio_SupplyDrain,
			'Ratio_ECktsup' => $obj->Ratio_ECktsup,
			'EC_drain' => $obj->EC_drain,
			'PLC_thickness' => $obj->PLC_thickness,
			'Delta_Flow_l' => $obj->Delta_Flow_l,
			'Delta_Supply' => $obj->Delta_Supply,
			'Delta_Drain' => $obj->Delta_Drain,          
		);
		return $newArray;
	}
    
    
   	/**
	 * convert obj to array
	 * @param obj
	 * @return array
	 */
	protected function obj2arr($objSensors)
	{
		$newArray = array
		(
			'idsensor' => $objSensors->idsensor,
			'Location' => $objSensors->Location,
			'SensorType' => $objSensors->SensorType,
   			'IsCooling' => $objSensors->IsCooling,
   			'IsPond' => $objSensors->IsPond,
			'El_Type' => $objSensors->El_Type,
			'id'	=> $objSensors->id,
			'Cu_eff'	=> $objSensors->Cu_eff,
			'Ag_eff'	=> $objSensors->Ag_eff,
			'detailurl' => '<a href="'.$this->addToUrl('&item='.$objSensors->id).'">'. $objSensors->id . '</a>',
		);
        
		return $newArray;
	}


	/**
	 * List one Sensor chart
	 * and select more detail info
	 * @param id value
     * @param yr (year of graph) -- if yr = 0, then current graph
     * @param $graph (string - graph type)
	 * @return array
	 */
	protected function listGraph($id, $yr, $graph)
	{
        $arrBifi = array();
        $arrWeekly = array();
        $arrCool = array();

        // Fetch Sensor data from the database
        $bpobjs = $this->Database->prepare ("SELECT * FROM Sensor WHERE idsensor=?")->limit(1)->execute($id);

        // Put sensors into array
        while ($bpobjs->next())
        {
                $arrBifi = $this->obj2arr($bpobjs);
        }

        if ((strcmp($graph, 'cuag') == 0) || (strcmp($graph, 'meas') == 0)
            || (strcmp($graph, 'conc') == 0) || (strcmp($graph, 'cmratio') == 0)
            || (strcmp($graph, 'water') == 0)) {
            // standard graph, use weeklylog
            
            // Now retrieve the Weekly log records. If no year, get the last 52 weeks
            if ($yr == 0) {
                $objs = $this->Database->prepare ("SELECT * FROM Weeklylog WHERE idsensor=? ORDER BY ts DESC")->limit(52)->execute($id);
                // Now we have weeklylog objects for graphing
                // since the order is reversed, go to the last row first, and then find each previous record
                if ($objs->last()) {
                    $arrWeekly[] = $this->weekly2arr($objs);
                    while ($objs->prev()) {
                            $arrWeekly[] = $this->weekly2arr($objs);
                    }
                }
            } else {
                $objs = $this->Database->prepare ("SELECT * FROM Weeklylog WHERE idsensor=? AND year=? ORDER BY week ASC")->execute($id, $yr);
                // Now we have weeklylog objects for graphing
                while ($objs->next()) {
                        $arrWeekly[] = $this->weekly2arr($objs);
                }
            }
            
            // Now we create the datasets for the graphs...
            $flow = array();        // this the the Delta_Flow_total_m3
            $ma_cu_sec = array();
            $ma_ag_sec = array();
            $cu_top_meas = array();
            $ag_top_meas = array();
            $cumflow = array();     // cumulative flow
            $cu_g = array();        // Delta_Cu_g
            $ag_g = array();        // Delta_Ag_g
            $cu_cm_ratio = array();
            $ag_cm_ratio = array();
            $cu_conc = array();
            $ag_conc = array();
            
            $cumflownow = 0.0;  // keep track of cumulative flow
            $count = 0;
            
            foreach ($arrWeekly as $obj) {
                $count++;
                $week[] = $obj['week'];
                $flow[] = $obj['Delta_Flow_total_m3'];
                if ($obj['Delta_Flow_total_m3'] > $mxflow) $mxflow = $obj['Delta_Flow_total_m3'];
                $cumflownow = $cumflownow + $obj['Delta_Flow_total_m3'];
                $cumflow[] = $cumflownow;
                $maxflow[] = $obj['Flow_top_value_lmin'];
                if ($obj['Flow_top_value_lmin'] > $maxmaxflow) $maxmaxflow = $obj['Flow_top_value_lmin'];
                $cu_g[] = $obj['Delta_Cu_g'];
                $ag_g[] = $obj['Delta_Ag_g'];
                $ma_cu_sec[] = $obj['Delta_mA_Cu_sec'];
                $ma_ag_sec[] = $obj['Delta_mA_Ag_sec'];
                $cu_cm_ratio[] = $obj['Cu_cm_ratio'];
                $ag_cm_ratio[] = $obj['Ag_cm_ratio'];
                $cu_top_meas[] = $obj['Cu_top_value_measured'];
                $ag_top_meas[] = $obj['Ag_top_value_measured'];

                /**** We used to calculate Cu_conc using weights and flows, now use the direct values from weeklylog 
                if ($obj['Delta_Flow_total_m3'] > 0.0) {
                    // prevent error messages on zero flow
                    $cu_conc[] = $obj['Delta_Cu_g'] / $obj['Delta_Flow_total_m3']*1000;
                    $ag_conc[] = $obj['Delta_Ag_g'] / $obj['Delta_Flow_total_m3']*1000;
                } else {
                    $cu_conc[] = $obj['Delta_Cu_g'] / $cumflownow * $count * 1000;
                    $ag_conc[] = $obj['Delta_Ag_g'] / $cumflownow * $count * 1000;
                }
                ****/
                
                // 2012-02-07
                // Remove efficiency as it is in the new way of calculating Cu_conc
                // Multiply times 1000 to go from mg/l to microg/l
                // $cu_conc[] = $obj['Cu_conc'] * $arrBifi['Cu_eff']*10;
                // $ag_conc[] = $obj['Ag_conc'] * $arrBifi['Ag_eff']*10;            
                $cu_conc[] = $obj['Cu_conc']*1000;
                $ag_conc[] = $obj['Ag_conc']*1000;            
            }
        } else {
            // it's a cooling tower or pond graph, use coolinglog
            // Now retrieve the Weekly log records. If no year, get the last 52 weeks
            if ($yr == 0) {
                $objs = $this->Database->prepare ("SELECT * FROM Coolinglog WHERE idsensor=? ORDER BY ts DESC")->limit(52)->execute($id);
                // Now we have weeklylog objects for graphing
                // since the order is reversed, go to the last row first, and then find each previous record
                if ($objs->last()) {
                    $arrCool[] = $this->cooling2arr($objs);
                    while ($objs->prev()) {
                            $arrCool[] = $this->cooling2arr($objs);
                    }
                }
            } else {
                $objs = $this->Database->prepare ("SELECT * FROM Coolinglog WHERE idsensor=? AND year=? ORDER BY week ASC")->execute($id, $yr);
                // Now we have weeklylog objects for graphing
                while ($objs->next()) {
                        $arrCool[] = $this->cooling2arr($objs);
                }
            }
            
            // Now we create the datasets for the graphs...
            $flow = array();        // this the the Delta_Flow_total_m3
            $Ratio_SupplyDrain = array();
            $Ratio_ECktsup = array();
            $EC_drain = array();
            $PLC_thickness = array();
            $Delta_Flow_l = array(); 
            $Delta_Supply = array();
            $Delta_Drain = array();
            $count = 0;
            
            foreach ($arrCool as $obj) {
                $count++;
                $week[] = $obj['week'];
                $flow[] = $obj['Delta_Flow_total_m3'];
                $Ratio_SupplyDrain[] = $obj['Ratio_SupplyDrain'];
                $Ratio_ECktsup[] = $obj['Ratio_ECktsup'];
                $EC_drain[] = $obj['EC_drain'];
                $PLC_thickness[] = $obj['PLC_thickness'];
                $Delta_Flow_l[] = $obj['Delta_Flow_l'];
                $Delta_Supply[] = $obj['Delta_Supply']/1000;	// convert from liters to m3
                $Delta_Drain[] = $obj['Delta_Drain']/1000;		// idem
            }
        }    

        
            
        // Set-up the template
        if ($this->bContaoUser) {
            $this->strTemplate = 'mod_stat_detail';
        } else {
            // show non HWT users an external template
            $this->strTemplate = 'mod_stat_detail_ext';
        }
        $this->Template = new FrontendTemplate ($this->strTemplate);
        // Assign data to the template
        $this->Template->sensor = $arrBifi;
        $this->Template->year = $yr;
        // Start building the title
        if ($yr == 0 or !isset($yr)) {
            $title = 'Last '.$count.' weeks|'.$arrBifi['idsensor'].'&nbsp;'.$arrBifi['Location'];
        } else {
            $title = 'Year '.$yr.'|'.$arrBifi['idsensor'].'&nbsp;'.$arrBifi['Location'];
        }

        // Create the appropriate graph
        if (strcmp($graph, 'cuag') == 0) {
            // the copper silver graph
            $this->Template->title = 'Cu and Ag consumption|'.$title;
            $this->Template->week = $week;                          // the x axis, by week
            $this->Template->y_max = round(max($flow)+5, -1);       // maximum y value
            $this->Template->r_max = round(max($cu_g)+5, -1);       // maximum r value
            // scale the datasets so that the max value equals around 100
            foreach ($flow as &$value) {
                $value = round ($value / $this->Template->y_max * 100, 1);
            }
            foreach ($cu_g as &$value) {
                $value = round ($value / $this->Template->r_max * 100, 1);
            }
            foreach ($ag_g as &$value) {
                $value = round ($value / $this->Template->r_max * 100, 1);
            }
            $this->Template->ds1 = $flow;                           // dataset 1
            $this->Template->ds2 = $cu_g;
            $this->Template->ds3 = $ag_g;
            $this->Template->legend = array("water flow (m&#179;/wk)", "cu (g/wk)", "ag (g/wk)");    // legends for the dataset
            $this->Template->x_unit = array("week");                // units for the dataset
            $this->Template->y_unit = array("m&#179;/wk");                  // units for the dataset
            $this->Template->r_unit = array("g/wk");                  // units for the dataset
        } 
        elseif (strcmp($graph, 'meas') == 0) {
            // the copper silver top value measured
            $this->Template->title = 'Cu and Ag max current|'.$title;
            $this->Template->week = $week;                          // the x axis, by week
            $this->Template->y_max = round(max($maxflow)+5, -1);       // maximum y value
            $this->Template->r_max = round(max($cu_top_meas)+5, -1);       // maximum r value
            // scale the datasets so that the max value equals around 100
            foreach ($maxflow as &$value) {
                $value = round ($value / $this->Template->y_max * 100, 1);
            }
            foreach ($cu_top_meas as &$value) {
                $value = round ($value / $this->Template->r_max * 100, 1);
            }
            foreach ($ag_top_meas as &$value) {
                $value = round ($value / $this->Template->r_max * 100, 1);
            }
            $this->Template->ds1 = $maxflow;                           // dataset 1
            $this->Template->ds2 = $cu_top_meas;
            $this->Template->ds3 = $ag_top_meas;
            $this->Template->legend = array("peakflow (l/min)", "cu max", "ag max");    // legends for the dataset
            $this->Template->x_unit = array("week");                // units for the dataset
            $this->Template->y_unit = array("l/min");                  // units for the dataset
            $this->Template->r_unit = array("mA");                  // units for the dataset
        } 
        elseif (strcmp($graph, 'conc') == 0) {
            // the copper silver concentration
            $this->Template->title = 'Cu and Ag Concentration|'.$title;
            // PvE: 2012-01-17 Only show efficiencies to our own users
//            if ($this->bContaoUser) {
//                $this->Template->title .= '|Cu eff: '.$arrBifi['Cu_eff'].'%&nbsp;&nbsp;Ag eff: '.$arrBifi['Ag_eff'].'%'; 
//            }

            $this->Template->week = $week;                          // the x axis, by week
            $this->Template->y_max = round(max($flow)+5, -1);       // maximum y value
            $this->Template->r_max = round(max($cu_conc)+5, -1);       // maximum r value
            // scale the datasets so that the max value equals around 100
            foreach ($flow as &$value) {
                $value = round ($value / $this->Template->y_max * 100, 1);
            }
            foreach ($cu_conc as &$value) {
                $value = round ($value / $this->Template->r_max * 100, 1);
            }
            foreach ($ag_conc as &$value) {
                $value = round ($value / $this->Template->r_max * 100, 1);
            }
            $this->Template->ds1 = $flow;                           // dataset 1
            $this->Template->ds2 = $cu_conc;
            $this->Template->ds3 = $ag_conc;
            $this->Template->legend = array("flow (m&#179;/wk)", "cu conc (&#181;g/l)", "ag conc (&#181;g/l)");    // legends for the dataset
            $this->Template->x_unit = array("week");                // units for the dataset
            $this->Template->y_unit = array("m&#179;/wk");                  // units for the dataset
            $this->Template->r_unit = array("&#181;g/l");                  // units for the dataset
        } 
        elseif (strcmp($graph, 'cmratio') == 0) {
            // the copper silver calculated versus measured ratio
            $this->Template->title = 'Calculated vs Measured ratio|'.$title;
            $this->Template->week = $week;                          // the x axis, by week
            $this->Template->y_max = round(max($maxflow)+5, -1);       // maximum y value
            $this->Template->r_max = round(max(max($cu_cm_ratio), max($ag_cm_ratio))+10, -1);       // maximum r value
            // scale the datasets so that the max value equals around 100
            foreach ($maxflow as &$value) {
                $value = round ($value / $this->Template->y_max * 100, 1);
            }
            foreach ($cu_cm_ratio as &$value) {
                $value = round ($value / $this->Template->r_max * 100, 1);
            }
            foreach ($ag_cm_ratio as &$value) {
                $value = round ($value / $this->Template->r_max * 100, 1);
            }
            $this->Template->ds1 = $maxflow;                           // dataset 1                          
            $this->Template->ds2 = $cu_cm_ratio;
            $this->Template->ds3 = $ag_cm_ratio;
            $this->Template->legend = array("peakflow (l/min)", "cu ratio", "ag ratio");    // legends for the dataset
            $this->Template->x_unit = array("week");                // units for the dataset
            $this->Template->y_unit = array("l/min");                  // units for the dataset
            $this->Template->r_unit = array("%");               // units for the dataset
        }            
        elseif (strcmp($graph, 'thick') == 0) {
            // the thickness graphs
            $this->Template->title = 'Thickness|'.$title;
            $this->Template->week = $week;                          // the x axis, by week
            $this->Template->y_max = 10;                            // maximum y value at 10
            // scale the datasets so that the max value equals around 100
            foreach ($Ratio_SupplyDrain as &$value) {
                $value = round ($value / $this->Template->y_max * 100, 1);
            }
            foreach ($Ratio_ECktsup as &$value) {
                $value = round ($value / $this->Template->y_max * 100, 1);
            }
            $this->Template->ds1 = $Ratio_SupplyDrain;                           // dataset 1
            $this->Template->ds2 = $Ratio_ECktsup;
            $this->Template->legend = array("sup/drain", "thickness EC_kt/EC_sup");    // legends for the dataset
            $this->Template->x_unit = array("week");                // units for the dataset
            $this->Template->y_unit = array("thickness");                  // units for the dataset
        }
        elseif (strcmp($graph, 'cond') == 0) {
            // the thickness graphs
            $this->Template->title = 'EC (conductivity) and Thickness |'.$title;
            $this->Template->week = $week;                          // the x axis, by week
            $this->Template->y_max = round(max($EC_drain)+200, -2);       // maximum y value
            $this->Template->r_max = 20;                            // maximum r value, thickness
            // scale the datasets so that the max value equals around 100
            foreach ($EC_drain as &$value) {
                $value = round ($value / $this->Template->y_max * 100, 1);
            }
            foreach ($PLC_thickness as &$value) {
                $value = round ($value / $this->Template->r_max * 100, 1);
            }
            $this->Template->ds1 = $EC_drain;                           // dataset 1
            $this->Template->ds2 = $PLC_thickness;
            $this->Template->legend = array("EC drain (&#181;S/cm)", "thickness plc");    // legends for the dataset
            $this->Template->x_unit = array("week");                // units for the dataset
            $this->Template->y_unit = array("&#181;S/cm");                  // units for the dataset
            $this->Template->r_unit = array("thickness");                  // units for the dataset
        } 
        elseif (strcmp($graph, 'drain') == 0) {
            // the drain and supply graphs
            $this->Template->title = 'Watersupply and Drain |'.$title;
            $this->Template->week = $week;                          // the x axis, by week
            // $this->Template->y_max = round(max($Delta_Flow_l)+200, -2);       // maximum y value
            $this->Template->y_max = round(max($flow)+10, -2);       // maximum y value
            /* keep on same axis for now
            $r1 = round(max($Delta_Supply)+5, -1);           // maximum r value
            $r2 = round(max($Delta_Drain)+5, -1);           // maximum r value
            $this->Template->r_max = max($r1, $r2);
            */ 
            // scale the datasets so that the max value equals around 100
            foreach ($flow as &$value) {
                $value = round ($value / $this->Template->y_max * 100, 1);
            }
            foreach ($Delta_Supply as &$value) {
                $value = round ($value / $this->Template->y_max * 100, 1);
            }
            foreach ($Delta_Drain as &$value) {
                $value = round ($value / $this->Template->y_max * 100, 1);
            }
            $this->Template->ds1 = $flow;                           // dataset 1
            $this->Template->ds2 = $Delta_Supply;
            $this->Template->ds3 = $Delta_Drain;
            $this->Template->legend = array("Total Flow (m&#179;/wk)", "Supply", "Drain");    // legends for the dataset
            $this->Template->x_unit = array("week");                // units for the dataset
            $this->Template->y_unit = array("m&#179;/wk");                  // units for the dataset
            // $this->Template->r_unit = array("m&#179;/wk");                  // units for the dataset
        } 
        else {
            // the water graph
            $this->Template->title = 'Water Consumption|'.$title;
            $this->Template->week = $week;                          // the x axis, by week
            $this->Template->y_max = round(max($flow)+5, -1);       // maximum y value
            $this->Template->r_max = round(max($maxflow)+5, -1);    // maximum r value
            // scale the datasets so that the max value equals around 100
            foreach ($flow as &$value) {
                $value = round ($value / $this->Template->y_max * 100, 1);
            }
            foreach ($maxflow as &$value) {
                $value = round ($value / $this->Template->r_max * 100, 1);
            }
            $this->Template->ds1 = $flow;                           // dataset 1                          
            $this->Template->ds2 = $maxflow;
            // $this->Template->ds3 = $cumflow;
            $this->Template->legend = array("water flow (m&#179;/wk)", "peakflow (l/min)");    // legends for the dataset
            $this->Template->x_unit = array("week");                // units for the dataset
            $this->Template->y_unit = array("m&#179;/wk");                  // units for the dataset
            $this->Template->r_unit = array("l/min");               // units for the dataset
        }            

    }

	
	/**
	 * Generate module
	 */
	protected function compile()
	{
		$arrStats = array();
		
		// Select appropriate Sensors
		$this->bifids = $this->accessSensors();

		// item is an idsensor...
        // year is the year....
        $item = $this->Input->get('item');
        $year = $this->Input->get('year');
        $graph = $this->Input->get('graph');
        if (strlen($graph) == 0) {
            // set default graph to water consumption
            $graph = 'water';
        }
        if (strlen($year) == 0) {
            // set default year
            $year = 0;
        }

       if ($this->bContaoUser) {
            $this->strTemplate = 'mod_stat';
        } else {
            // show non HWT users an external template
            $this->strTemplate = 'mod_stat_ext';
        }
        $this->Template = new FrontendTemplate ($this->strTemplate);
        
        // $this->log('Statistics '.$item.' strlen '.strlen($item),__METHOD__,'INFO');
        // $this->log('Statistics '.$year.' strlen '.strlen($year),__METHOD__,'INFO');
		if ((strlen($item) != 0)) {
			// So first check if there is a customer id, and if so, use that as $item
			$prow = $this->Database->execute("SELECT id FROM Sensor WHERE idsensor='". $item . "' LIMIT 1");
            // $this->log('Sensor rows '.$prow->numRows,__METHOD__,'INFO');
			if ($prow->numRows == 1) {
				$id = $prow->id;
			}
 			
			// check if item in the array of $this->bifids, so that we have access
			if (is_array($this->bifids) && (in_array($id, $this->bifids, $strict = null))) {
				// ok, we are allowed to see that item... 
				$this->listGraph($item, $year, $graph);
			} else {
				// can not see this sensor,
				$this->strTemplate = 'mod_stat_error';
				$this->Template = new FrontendTemplate ($this->strTemplate);
				$this->Template->id = $this->Input->get('item');
                $this->Template->msg = "You do not have access to that record.";
				return '';
			}
		} else {
			// Return if there are no valid Sensor's
			if (!is_array($this->bifids) || count($this->bifids) < 1)
			{
				$arrStats[] = array
				(
					'idsensor' => 'No valid Sensors found',
					'year' => '',
				);
				$this->Template->stats = $arrStats;
				return '';
			}
			
			// For each of the allowed sensor ids, select the years
            foreach ($this->bifids as $id) {
                // inner join to prevent data from appearing if Weeklylog is empty
                $objYrs = $this->Database->prepare("SELECT DISTINCT Weeklylog.year AS year, Weeklylog.idsensor AS idsensor, Sensor.id AS id FROM Sensor
                    JOIN Weeklylog
                    ON (Sensor.idsensor = Weeklylog.idsensor)
                    WHERE id=? 
                    ORDER BY idsensor ASC, year DESC")->execute($id);
                
                // Put Yrs into a string
                $years = '';
                while ($objYrs->next()) {
                    $bpid = $objYrs->idsensor;
                    $years .= '<a href="'.$this->addToUrl('&item='.$objYrs->idsensor.'&year='.$objYrs->year).'">'. $objYrs->year . '</a>&nbsp;';
                }

                if (strlen($years) > 0) {
                    // and store the values, but only if there are some (i.e. years contains something)
                    $arrStats[] = array
                    (
                        'id' => $id,
                        'idsensor' => $bpid,
                        'year' => $years,
                    );
                }

            } // foreach
        
			// Assign data to the template
			$this->Template->stats = $arrStats;
            $this->Template->headline = 'Statistics';
		} // if-else
	}
	
}

?>