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

// version 1.1 - PDF Export of maintenance form added

require_once('errormask.php');

 /**
 * Class ModuleReports
 *
 * Front end module "Reports".
 * @package    Controller
 */
class ModuleReports extends Module
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

			$objTemplate->wildcard = '### REPORTS ###';
			$objTemplate->title = $this->headline;
			$objTemplate->id = $this->id;
			$objTemplate->link = $this->name;
			$objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

			return $objTemplate->parse();
		}
		
		return parent::generate();
	}

	/**
	 * Explain the status code for monitoring
	 * @return string
	 */
    protected function explainstatus ($i) {
		$s = "";
		if ($i <= 0) {
			switch ($i) {
				case 0: $s = "OK";
					break;
				case -1: $s = "ERROR";
					break;
				case -2: $s = "BUSY";
					break;
				case -3: $s = "NO DIALTONE";
					break;
				case -4: $s = "NO CARRIER";
					break;
				case -5: $s = "NO ANSWER";
					break;
				case -6: $s = "TIMEOUT";
					break;
				case -7: $s = "PROTOCOL ERROR";
					break;
				default: $s = "UNKNOWN";
			}
		} else {
			if ($i & A_CU_LOW_SUPPLY) { 
				$s .= "Alert: Cu supply is low, check electrodes. ";
			}
			if ($i & A_AG_LOW_SUPPLY) { 
				$s .= "Alert: Ag supply is low, check electrodes. ";
			}
			if ($i & A_CURCONT_ALERT) { 
				$s .= "Alert: Verify current controllers. ";
			}
			if ($i & N_CU_CONC_LOW) { 
				$s .= "Note: Cu concentration low. ";
			}
			if ($i & N_AG_CONC_LOW) { 
				$s .= "Note: Ag concentration low. ";
			}
			if ($i & N_CU_CONC_HIGH) { 
				$s .= "Note: Cu concentration high. ";
			}
			if ($i & N_AG_CONC_HIGH) { 
				$s .= "Note: Ag concentration high. ";
			}
			if ($i & N_FLOW_LOW) { 
				$s .= "Note: Water flow low, check flowmeters. ";
			}
			if ($i & N_FLOW_HIGH) { 
				$s .= "Note: Water flow high, check Sensor capacity. ";
			}
			if ($i & N_VERIFY_FLOWMETER) { 
				$s .= "Note: Check flowmeter, very low water volume. ";
			}
			if ($i & A_BIFIPRO_CAPACITY) { 
				$s .= "Alert: Sensor capacity insufficent. ";
			}
			if ($i & A_NEGATIVE) { 
				// Reset the string, just indicate values are negative
				$s = "Alert: Negative values, check mapping.";
			}
		}
		return ($s);
	}

    /**
	 * is the status code a warning (orange ball) or an alert (red ball)
     * warning = true, otherwise false
	 * @return boolean
	 */
	protected function iswarning ($i) {
		$s = true;
		if ($i <= 0) {
            $s = true;
        } else {
			if ($i & A_CU_LOW_SUPPLY) { 
				$s = false;
			}
			if ($i & A_AG_LOW_SUPPLY) { 
				$s = false;
			}
			if ($i & A_CURCONT_ALERT) { 
				$s = false;
			}
			if ($i & A_BIFIPRO_CAPACITY) { 
				$s = false;
			}
			if ($i & A_NEGATIVE) { 
				$s = false;
			}
		}
		return ($s);
	}
    
    /**
	 * Convert encoding
	 * @return String
	 * @param $strString String to convert
	 * @param $from charset to convert from
	 * @param $to charset to convert to
	 */
	public function convertEncoding($strString, $from, $to)
	{
		if (USE_MBSTRING)
		{
			@mb_substitute_character('none');
			return @mb_convert_encoding($strString, $to, $from);
		}
		elseif (function_exists('iconv'))
		{
			if (strlen($iconv = @iconv($from, $to . '//IGNORE', $strString)))
			{
				return $iconv;
			}
			else
			{
				return @iconv($from, $to, $strString);
			}
		}
		return $strString;
	}

    /**
	 * Create Form
     * Creates pdf form of the maintenance action
	 * @return nothing
	 * @param $id - system to be maintained
	 */
	protected function CreateForm($id)
	{
        // Export the form as PDF
        include(TL_ROOT.'/plugins/tcpdf/tcpdf.php');

        $objs = $this->Database->prepare("SELECT NextMaintenance.idsensor, NextMaintainDate as Date, Slot, Location, 
                CONCAT (street, ' ', housenumber, ', ', Location.city) As Address,
                Sensor.IsCooling, Sensor.IsPond,
                contactperson, telephone, MobileTel, Location.comments,
                tl_user.name as Who, tl_user.email as email
                FROM ((NextMaintenance LEFT JOIN Sensor ON NextMaintenance.idsensor = Sensor.idsensor)
                LEFT JOIN Location ON NextMaintenance.idLocation = Location.id)
                LEFT JOIN tl_user ON NextMaintenance.uid = tl_user.id
                WHERE IsFixed=1
                AND NextMaintenance.idsensor=?")->execute($id);
        if (!$objs->next()) {
                return ("Error");
        }
        
        if ($objs->IsCooling || $objs->IsPond) {
            $bCooling = true;
        } else {
            $bCooling = false;
        }
       
        // all data values are available as $objs->idsensor etc
        
        // create new PDF document
        // $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        // measurements in millimeter
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // set document information
        $pdf->SetCreator('TCPDF');
        $pdf->SetAuthor('Holland Watertechnology');
        $pdf->SetTitle('Maintenance Form');
        $pdf->SetSubject('<client name>');
        $pdf->SetKeywords('Holland, Watertechnology, BIFIPRO, Maintenance, Form');

        // set default header data
        // $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING);
        if ($bCooling) {
            $pdf->SetHeaderData('hwt.png', 40, 'Maintenance BIFIPRO®COOL', '');
        } else {
            $pdf->SetHeaderData('hwt.png', 40, 'Maintenance BIFIPRO®', '');
        }
        
        // set header and footer fonts
        // $pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
        // $pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));
        $pdf->setHeaderFont(Array('helvetica', '', 14));
        $pdf->setFooterFont(Array('helvetica', '', 10));

        // set default monospaced font
        // $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetDefaultMonospacedFont('courier');

        //set margins
        // $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        // $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        // $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);

        //set auto page breaks
        // $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        $pdf->SetAutoPageBreak(TRUE, 15);

        //set image scale factor
        // ratio used to adjust the conversion of pixels to user units
        // $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
        $pdf->setImageScale(1.25);

        //set some language-dependent strings
        // $pdf->setLanguageArray($l);

        // ---------------------------------------------------------

        // set default font subsetting mode
        $pdf->setFontSubsetting(true);

        // Set font
        // dejavusans is a UTF-8 Unicode font, if you only need to
        // print standard ASCII chars, you can use core fonts like
        // helvetica or times to reduce file size.
        $pdf->SetFont('helvetica', '', 10, '', true);

        // Add a page
        // This method has several options, check the source code documentation for more information.
        $pdf->AddPage();

$tbl = <<<EOD
<table cellspacing="0" cellpadding="1" border="0" frame="border" rules="rows">
<thead>
    <tr style="background-color:#C0C0C0">
        <th colspan="2"><b>Project Details</b></th>
    </tr>
</thead>
    <tr>
        <td width="25%">BIFIPRO&reg; Number</td>
        <td width="75%">$objs->idsensor</td>
    </tr>
    <tr>
        <td>Location</td>
        <td>$objs->Location</td>
    </tr>
    <tr>
        <td>Address</td>
        <td>$objs->Address</td>
    </tr>
    <tr>
        <td>Special remarks</td>
        <td>$objs->comments</td>
    </tr>
    <tr>
        <td>Date/time</td>
        <td>$objs->Date $objs->Slot</td>
    </tr>
</table>
<br>
<table cellspacing="0" cellpadding="1" border="0" frame="border" rules="rows">
<thead>
    <tr style="background-color:#C0C0C0">
        <th colspan="2"><b>Contactperson at location</b></th>
    </tr>
</thead>
    <tr>
        <td width="25%">name</td>
        <td width="75%">$objs->contactperson</td>
    </tr>
    <tr>
        <td>Telephone</td>
        <td>$objs->telephone $objs->MobileTel</td>
    </tr>
    <tr>
        <td>Special remarks</td>
        <td></td>
    </tr>
    <tr>
        <td>Ordernumber</td>
        <td></td>
    </tr>
</table>
<br>
EOD;
        // writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
        $pdf->writeHTML($tbl, true, false, false, false, '');
        
$prt1 = <<<EOD
<table cellspacing="0" cellpadding="1" border="0" frame="border" rules="rows">
<thead>
    <tr style="background-color:#C0C0C0">
        <th colspan="5"><b>Activities performed</b></th>
    </tr>
</thead>
    <tr>
        <td width="25%">Time</td>
        <td colspan="4" width="75%">From ___:___ to ___:___</td>
    </tr>
    <tr>
        <td>Watermeter m&sup3;</td>
EOD;

    if ($bCooling) {
        $prt2 ='<td>Suppletion:</td><td>_________</td><td>Drain:</td><td>_________</td>';
    } else {
        $prt2 ='<td colspan="4" width="75%">____________________</td>';
    }

$prt3 = <<<EOD
    </tr>
    <tr>
        <td>Electrodes</td>
        <td width="20%">Number Cu:</td>
        <td width="17%" align="left">_____</td>
        <td width="20%">Length Cu:</td>
        <td width="18%" align="left">_____ cm</td>
    </tr>
    <tr>
        <td>Cleaned</td>
        <td width="20%">New weight Cu:</td>
        <td width="17%" align="left">_____ g</td>
        <td width="20%">Removed Cu:</td>
        <td width="18%" align="left">_____ g</td>
    </tr>
    <tr>
        <td>O Cu  O Ag</td>
        <td width="20%">Number Ag:</td>
        <td width="17%" align="left">_____</td>
        <td width="20%">Length Ag:</td>
        <td width="18%" align="left">_____ cm</td>
    </tr>
    <tr>
        <td></td>
        <td width="20%">New weight Ag:</td>
        <td width="17%" align="left">_____ g</td>
        <td width="20%">Removed Ag:</td>
        <td width="18%" align="left">_____ g</td>
    </tr>
    <tr>
        <td>Operation of flowmeters</td>
        <td colspan="2">O Correct</td>
        <td colspan="2">O Incorrect _____________________</td>
    </tr>
    <tr>
        <td>Flowmeters</td>
        <td>O Flushed</td>
        <td>O Cleaned</td>
        <td colspan="2">O Exchanged ___________________</td>
    </tr>
    <tr>
        <td>Valves</td>
        <td colspan="4">O All valves in correct position</td>
    </tr>
    <tr>
        <td>Connectors</td>
        <td colspan="4">O All connectors correct</td>
    </tr>
    <tr>
        <td>Display Program</td>
        <td>O Correct</td>
        <td colspan="3">O Incorrect: ___________________</td>
    </tr>
    <tr>
        <td>Sticker &lsquo;type BP&rsquo; applied</td>
        <td>O Yes</td>
        <td colspan="3">O No, because: ___________________</td>
    </tr>
    <tr>
        <td colspan="5">During first maintenance, inspect entire building with the customers Technical Department, just as
        during installation. Remove aerators (perlators) and take photographs.</td>
     </tr>
</table>
EOD;
        $tbl = $prt1 . $prt2 . $prt3;
        // writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
        $pdf->writeHTML($tbl, true, false, false, false, '');
        
        if ($bCooling) {
            // stuff for cooling tower only
$html = <<<EOD
<br>
<table cellspacing="0" cellpadding="1" border="0" frame="border" rules="rows">
<thead>
    <tr style="background-color:#C0C0C0">
        <th colspan="5"><b>Conductivity Sensor</b></th>
    </tr>
</thead>
    <tr>
        <td>Conductivity Sensor</td>
        <td width="20%">EC:</td>
        <td width="17%" align="left">_____ mS/cm</td>
        <td width="20%">Temperature</td>
        <td width="18%" align="left">_____ &deg;C</td>
    </tr>
    <tr>
        <td>Cleaned?</td>
        <td colspan="2">O Yes</td>
        <td colspan="2">O No</td>
    </tr>
    <tr>
        <td>If cleaned, how dirty?</td>
        <td width="20%">O Clean</td>
        <td width="17%" align="left">O Deposit</td>
        <td width="20%">O Biofilm</td>
        <td width="18%" align="left">O ____________</td>
    </tr>
    <tr>
        <td>Replaced?</td>
        <td colspan="1">O No</td>
        <td colspan="3">O Yes, because: _______________________</td>
    </tr>
    <tr>
        <td>Replaced?</td>
        <td colspan="1">O No</td>
        <td colspan="3">O Yes, because: _______________________</td>
    </tr>
    <tr>
        <td>Sensor Certified?</td>
        <td width="20%">O Yes</td>
        <td width="17%">O No</td>
        <td colspan="2">O Verified with separate instrument</td>
    </tr>
    <tr>
        <td>Calibration needed?</td>
        <td width="20%">O No</td>
        <td colspan="3">O Yes, deviation: _________________________</td>
    </tr>
    <tr>
        <td>Calibration possible?</td>
        <td width="20%">O Yes</td>
        <td colspan="3">O No, needs replacement</td>
    </tr>
    <tr>
        <td>Sensor replacement?</td>
        <td width="20%">O Direct</td>
        <td colspan="3">O After approval or order. Estimated Date: ____________</td>
    </tr>
    <tr>
        <td colspan="5">After sensor maintenance please verify</td>
    </tr>
    <tr>
        <td>Valves</td>
        <td colspan="4">O All valves in correct position</td>
    </tr>
    <tr>
        <td>Connectors</td>
        <td colspan="4">O All connectors of sensor correctly attached</td>
    </tr>
</table>
EOD;
        // writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
        $pdf->writeHTML($html, true, false, false, false, '');
        $pdf->AddPage();

        // now the real cooling tower stuff
$html = <<<EOD
<br>
<table cellspacing="0" cellpadding="1" border="0" frame="border" rules="rows">
<thead>
    <tr style="background-color:#C0C0C0">
        <th colspan="6"><b>Cooling tower</b></th>
    </tr>
</thead>
    <tr>
        <td width="20%">GEA number:</td>
        <td colspan="5">________________________</td>
    </tr>
    <tr>
        <td>Visual inspection?</td>
        <td colspan="2" width="32%">O Yes</td>
        <td colspan="3" width="48%">O No</td>
    </tr>
    <tr>
        <td width="20%">State of the slats?</td>
        <td width="16%">O Clean</td>
        <td width="16%">O Deposit</td>
        <td width="16%">O Biofilm</td>
        <td width="16%">O Foam</td>
        <td width="16%">O ____________</td>
    </tr>
    <tr>
        <td width="20%">State of the basin (sides)?</td>
        <td width="16%">O Clean</td>
        <td width="16%">O Deposit</td>
        <td width="16%">O Biofilm</td>
        <td width="16%">O Foam</td>
        <td width="16%">O ____________</td>
    </tr>
    <tr>
        <td width="20%">Internally?</td>
        <td width="16%">O Clean</td>
        <td width="16%">O Deposit</td>
        <td width="16%">O Biofilm</td>
        <td width="16%">O Foam</td>
        <td width="16%">O ____________</td>
    </tr>
    <tr>
        <td>Overall state?</td>
        <td colspan="2">O Clean</td>
        <td colspan="3">O Dirty: __________________________</td>
    </tr>
    <tr>
        <td>Tech. Dept informed?</td>
        <td colspan="2">O Yes</td>
        <td colspan="3">O No, were not present.</td>
    </tr>
    <tr>
        <td>If yes, agreement?</td>
        <td colspan="3">O Coolingtower will be cleaned completely</td>
        <td colspan="2">O The fouling will be removed</td>
    </tr>
    <tr>
        <td width="20%">When</td>
        <td colspan="2">Date: ___/___/______</td>
        <td colspan="3">Please inform HWT upon completion</td>
    </tr>
</table>
EOD;
        // writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
        $pdf->writeHTML($html, true, false, false, false, '');

        $pdf->SetFont('helvetica', '', 8, '', true);
        // add explanation

$html = <<<EOD
<br>
<table cellspacing="0" cellpadding="1" border="0" frame="border" rules="rows">
<thead>
    <tr style="background-color:#C0C0C0">
        <th colspan="1"><b>Explanation of maintenance procedure BIFIPRO&reg;COOL</b></th>
    </tr>
</thead>
    <tr>
        <td>
        <ol>
<li>Please consult the maintenance instructions for details on changing and cleaning electrodes.</li>
<li>Remove, clean and certify the conductivity sensor in the water circulation system.
<ul>
<li>If the sensor is dirty, remove the material with water or with a Q-tip.</li>
<li>If the sensor has scale (calcium-deposits) carefully clean it in a solution of acid
(hydrochloric acid or nitric acid): briefly insert the sensor in the acid, then remove and rinse with clean water. Repeat until all scale is removed.</li>
<li>After this procedure the sensor needs to be calibrated. Insert the sensor in the reference liquid, and verify if
the value indicated agrees with the reference value. If not the sensor needs calibration. Refer to the sensor documentation
for the correct procedure.</li> 
</ul></li>
<li>Inspect the coolingtower itself visually:        
<ul>
<li>Verify is the slats have deposit or biofilm;</li>
<li>Verify if the inside is clean (the walls and bottom of the circulation or buffer tank);</li>
<li>Are there contaminants (leaves, dead insects, animals, algae) in, on or around the coolingtower;</li> 
</ul></li>
<li>If there are problems, please inform the customers Technical Department and recommend that they clean the cooling tower.
Ask them to inform Holland Watertechnology when this is completed.</li>
        </ol></td>
    </tr>
</table>
EOD;
        // writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
        $pdf->writeHTML($html, true, false, false, false, '');
        } // if $bCooling

        // regular content again

        $pdf->SetFont('helvetica', '', 10, '', true);

$tbl = <<<EOD
<br>
<table cellspacing="0" cellpadding="1" border="1" frame="border" rules="rows">
<thead>
    <tr style="background-color:#C0C0C0">
        <th colspan="2"><b>Remarks or changes</b></th>
    </tr>
</thead>
    <tr>
        <td colspan="2" width="100%" height="100"></td>
    </tr>
    <tr>
        <td width="25%" height="65">Follow up actions?</td>
        <td width="75%">O No<br>O Already performed<br>O Yes, Who: ________________
        <br>When: O Within 2 days    O Within 1 week   O At next maintenance</td>
    </tr>
</table>
<br>
<table cellspacing="0" cellpadding="1" border="1" frame="border" rules="rows">
<thead>
    <tr style="background-color:#C0C0C0">
        <th><b>Signature TD/Reception</b></th>
        <th><b>Signature Maintenance Engineer</b></th>
    </tr>
</thead>
    <tr>
        <td width="50%">name: </td>
        <td width="50%">name: $objs->Who</td>
    </tr>
    <tr>
        <td width="50%">Company: </td>
        <td width="50%">Company: </td>
    </tr>
    <tr>
        <td width="50%" height="36">Signature: </td>
        <td width="50%">Signature:</td>
    </tr>
    <tr>
        <td width="50%">Date: </td>
        <td width="50%">Date: </td>
    </tr>
</table>

EOD;

        // writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
        $pdf->writeHTML($tbl, true, false, false, false, '');

        $pdf->SetFont('helvetica', '', 6, '', true);
        
        // Set some content to print
        $tbl = <<<EOD
<table cellspacing="0" cellpadding="1" border="0">
    <tr>
        <td width="70%"><u>Note:</u>
<ul>
<li>Collect signature from Technical Department or Reception.</li>
<li>In case of problems, contact the office.</li>
<li>Verify operation of machine after restart.</li>
<li><i>Hand original form to administration in the office.</i></li>
</ul></td>
        <td width="30%">Entered by:<br>Date:<br>Signed Administration: .........</td>
    </tr>
</table>
<br>
EOD;

        // writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
        $pdf->writeHTML($tbl, true, false, false, false, '');
        
        // Bigger actions again
        $pdf->SetFont('helvetica', '', 10, '', true);
        $i = 0;
        
        // Now retrieve the Action records
        $objs = $this->Database->prepare ("SELECT Actionlog.Comment, From_UnixTime(Actionlog.Created) AS Date, tl_user.name
        FROM Sensor JOIN Actionlog ON (Actionlog.pid=Sensor.id) JOIN tl_user ON (tl_user.id=Actionlog.uid)
        WHERE Sensor.idsensor=?
        ORDER BY Actionlog.created DESC")->limit(10)->execute($id);
        
        // Add the action records to the page
        while ($objs->next()) {
            $i++;
            if ($i == 1) {
                $pdf->AddPage();
$tbl = <<<EOD
<br>
<table cellspacing="0" cellpadding="1" border="1">
<thead>
    <tr style="background-color:#C0C0C0">
        <th colspan="3"><b>Actionlog</b></th>
    </tr>
    <tr>
    <th width="15%">Date</th>
    <th width="15%">Who</th>
    <th width="70%">Action</th>
    </tr>
</thead>
EOD;
            } // end of if
            
            // add each record
            $rec = '<tr><td width="15%">'. $objs->Date . '</td>';
            $rec .= '<td width="15%">'. $objs->name . '</td>';
            $rec .= '<td width="70%">'. str_replace("[nbsp]", " ", $objs->Comment). '</td></tr>'; 
            $tbl .= $rec;
    
        } // while
        
        if ($i > 0) {
            // we have stuff to print
            $tbl .= '</table><br>';
            // writeHTML($html, $ln=true, $fill=false, $reseth=false, $cell=false, $align='')
            $pdf->writeHTML($tbl, true, false, false, false, '');
        }

        // Close and output PDF document
        // This method has several options, check the source code documentation for more information.
        $pdf->Output($id.'.pdf', 'I');
        exit;
		return $strString;
	}

    
    
    /**
     * Class ContentTable
     * PvE: 2012-03-02
     * This is the standard ContentTable element... we just reverse engineer the way the table gets created using a query
     */
	protected function compile()
	{
		global $objPage;
        $bContaoUser = false;
        $bNoReport = false;
        
		// Does this user have any access rights at all
        $this->import('FrontendUser', 'User');
		// First we find out who is logged in... and what their e-mail address is...
		if (!FE_USER_LOGGED_IN && !BE_USER_LOGGED_IN) {
			$bNoReport = true;
		} else {
            $username = $this->User->username;
            $objUser = $this->Database->prepare("SELECT email FROM tl_member WHERE username=?")->limit(1)->execute($username);
            // $objUser->email contains the email address... we have the special case of a holland environment or hwt address
            // first make it lower case
            $objUser->email = strtolower($objUser->email);

            // Simplify the code with a nested list
            if (strpos($objUser->email, '@hollandenvironment.com') || strpos($objUser->email, '@hollandwatertechnology.com') || BE_USER_LOGGED_IN) {
                $bHWTuser = true;
            } else {
                // we may have to do something clever with an empty table
                $bNoReport = true;
            }
        }

        $this->import('Session'); 
        $this->import('Input');
        $this->import('String');
        $this->import('String');
        
		$report = $this->Input->get('item');
        $bExport = false;
        if ($this->Input->get('act') == 'export') {
            $bExport = true;
        }
 
        if ($report == 'pdf') {
            // if a pdf is requested, show a link
            $id = $this->Input->get('id');
            $this->CreateForm ($id);
        }
 
        // if no report show the error page
        if ($bNoReport) {
            $this->strTemplate = 'mod_reports_error';
            $this->Template = new FrontendTemplate ($this->strTemplate);
            $this->Template->report = $report;
            $this->Template->user = $username;
            if (!$bHWTuser) {
                $this->Template->report = "accessible";
            }
            return '';
        }

        // default settings for our tables
        $this->thead = true;
        $this->tfoot = false;
        $this->tleft = false;
        $this->sortable = true;
        
        // implement the reports to create a table...
        // get an array of $rows[]
        $rows = array();
        $align = array();
        switch ($report) { 
            case "mapping":
                    // first set up the field names
                    $rows[0] = array (
                        0 => 'idMapping',
                        1 => 'Description',
                        2 => 'Total',
                    );
                    $fields = 3;
                    // set the alignment of the fields
                    for ($j = 0; $j < $fields; $j++) {
                        $align[$j] = 'left';
                        $type[$j] = 'd';        // type can be s for string, d for decimal, or f for float
                    }
                    $type[1] = 's';
                    $i = 1;
                    // perform the query
                    $objs = $this->Database->prepare("SELECT Mappings.idMapping, Description, count(Sensor.idsensor) AS Total
                                                      FROM Mappings, Sensor
                                                      WHERE Sensor.idMapping = Mappings.idMapping
                                                      GROUP BY Mappings.idMapping")->execute();
                    // get the values
                    while ($objs->next()) {
                        for ($j = 0; $j < $fields; $j++) {
                            $rows[$i][$j]=$objs->$rows[0][$j];
                        }
                        $i++;
                    }
                    // Set-up general info
                    $this->summary = 'Mapping list';
                    $this->headline = 'Mapping list';
                    break;
            case "cuagusage":
                    // first set up the field names
                    $rows[0] = array (
                        0 => 'idsensor',
                        1 => 'Flow_m3',
                        2 => 'Th_Cu_g',
                        3 => 'PLC_Cu_g',
                        4 => 'Diff_Cu',
                        5 => 'Th_Ag_g',
                        6 => 'PLC_Ag_g',
                        7 => 'Diff_Ag',
                        8 => 'SetRatio',
                        9 => 'CalcRatio',
                    );
                    $fields = 10;
                    // set the alignment of the fields
                    for ($j = 0; $j < $fields; $j++) {
                        $align[$j] = 'right';
                        $type[$j] = 'f';        // type can be s for string, d for decimal, or f for float
                    }
                    $type[0] = 's';
                    $align[0] = 'left';
                    $i = 1;
                    // perform the query
                    $objs = $this->Database->prepare("SELECT DISTINCT Sensor.idsensor, Sensor.id,
                                                        Flow_m3, Theoretical_Cu_g as Th_Cu_g, PLC_Cu_g,
                                                        (Theoretical_Cu_g-PLC_Cu_g)/Theoretical_Cu_g*100 as Diff_Cu,
                                                        Theoretical_Ag_g as Th_Ag_g, PLC_Ag_g,
                                                        (Theoretical_Ag_g-PLC_Ag_g)/Theoretical_Ag_g*100 as Diff_Ag,
                                                        Calculatedlog.set_ratio_Cu_Ag as SetRatio,
                                                        Calculatedlog.calc_ratio_Cu_Ag as CalcRatio
                                                        FROM AnnualMaintenance, Calculatedlog, Sensor 
                                                        WHERE AnnualMaintenance.Year=Year(now()) AND AnnualMaintenance.Flow_m3 > 0
                                                        AND AnnualMaintenance.idsensor = LEFT(Calculatedlog.idsensor, 5)
                                                        AND Sensor.idsensor = Calculatedlog.idsensor
                                                        AND Calculatedlog.ts = Sensor.LastMonitorSuccess
                                                        AND Sensor.IsActive = 1
                                                        ORDER BY idsensor")->execute();
                    // get the values
                    while ($objs->next()) {
                        for ($j = 0; $j < $fields; $j++) {
                            if ($rows[0][$j] == 'idsensor') {
                                // make it a link... but only if not for export
                                if (!$bExport) {
                                    $rows[$i][$j]= '<a href="index.php/Sensors/item/'.$objs->id.'.html">'.$objs->$rows[0][$j].'</a>';
                                } else {
                                    $rows[$i][$j]= $objs->$rows[0][$j];
                                }
                            } else {
                                // round and neatly format the value, with trailing zero's
                                $rows[$i][$j]=sprintf ("%01.2f", round($objs->$rows[0][$j],2));
                            }
                        }
                        $i++;
                    }
                    // Set-up general info
                    $this->summary = 'CuAg list';
                    $this->headline = 'Actual versus Theoretical Copper / Silver usage';
                    break;
            case "failedcall":
                    // first set up the field names
                    $rows[0] = array (
                        0 => 'idsensor',
                        1 => 'idCustomer',
                        2 => 'Location',
                        3 => 'city',
                        4 => 'LastMonitorSuccess',
                        5 => 'Retries',
                        6 => 'LastStatusCode',
                    );
                    $fields = 7;
                    // set the alignment of the fields
                    for ($j = 0; $j < $fields; $j++) {
                        $align[$j] = 'left';
                        $type[$j] = 's';        // type can be s for string, d for decimal, or f for float
                    }
                    $type[5] = 'd';
                    $i = 1;
                    // perform the query
                    $objs = $this->Database->prepare("SELECT *
                                                      FROM LastMonitor
                                                      WHERE (LastMonitorSuccess + INTERVAL 168 HOUR) < now()
                                                      AND LastStatusCode <> 0
                                                      ORDER BY LastMonitorSuccess")->execute();
                    // get the values
                    while ($objs->next()) {
                        for ($j = 0; $j < $fields; $j++) {
                            if ($rows[0][$j] == 'idsensor') {
                                // make it a link... but only if not for export
                                if (!$bExport) {
                                    $rows[$i][$j]= '<a href="index.php/Sensors/item/'.$objs->$rows[0][$j].'.html">'.$objs->$rows[0][$j].'</a>';
                                } else {
                                    $rows[$i][$j]= $objs->$rows[0][$j];
                                }
                            } elseif ($rows[0][$j] == 'LastStatusCode') {
                                // add an explanation to it
                                $rows[$i][$j]= $objs->$rows[0][$j] . ' ' . $this->explainstatus(intval($objs->$rows[0][$j]));
                            } else {
                                // round and neatly format the value, with trailing zero's
                                $rows[$i][$j]=$objs->$rows[0][$j];
                            }
                        }
                        $i++;
                    }
                    // Set-up general info
                    $this->summary = 'Failed Call';
                    $this->headline = 'Systems not monitored in the last 168 hours';
                    break;
            case "monitoringerrors":
                    // first set up the field names
                    $rows[0] = array (
                        0 => 'idsensor',
                        1 => 'idCustomer',
                        2 => 'Location',
                        3 => 'city',
                        4 => 'LastMonitorSuccess',
                        5 => 'LastStatusCode',
                    );
                    $fields = 6;
                    // set the alignment of the fields
                    for ($j = 0; $j < $fields; $j++) {
                        $align[$j] = 'left';
                        $type[$j] = 's';        // type can be s for string, d for decimal, or f for float
                    }
                    $i = 1;
                    // perform the query
                    $objs = $this->Database->prepare("SELECT *
                                                      FROM LastMonitor
                                                      WHERE (LastMonitorSuccess + INTERVAL 168 HOUR) > now()
                                                      AND LastStatusCode > 0
                                                      ORDER BY LastMonitorSuccess")->execute();
                    // get the values
                    while ($objs->next()) {
                        for ($j = 0; $j < $fields; $j++) {
                            if ($rows[0][$j] == 'idsensor') {
                                // make it a link... but only if not for export
                                if (!$bExport) {
                                    $rows[$i][$j]= '<a href="index.php/Sensors/item/'.$objs->$rows[0][$j].'.html">'.$objs->$rows[0][$j].'</a>';
                                } else {
                                    $rows[$i][$j]= $objs->$rows[0][$j];
                                }
                            } elseif ($rows[0][$j] == 'LastStatusCode') {
                                // add an explanation to it
                                $rows[$i][$j]= $objs->$rows[0][$j] . ' ' . $this->explainstatus(intval($objs->$rows[0][$j]));
                            } else {
                                // round and neatly format the value, with trailing zero's
                                $rows[$i][$j]=$objs->$rows[0][$j];
                            }
                        }
                        $i++;
                    }
                    // Set-up general info
                    $this->summary = 'Monitoring Errors';
                    $this->headline = 'Systems with monitoring errors / warnings in the last 168 hours';
                    break;
            case "monitoringfine":
                    // first set up the field names
                    $rows[0] = array (
                        0 => 'idsensor',
                        1 => 'idCustomer',
                        2 => 'Location',
                        3 => 'city',
                        4 => 'LastMonitorSuccess',
                        5 => 'LastStatusCode',
                    );
                    $fields = 6;
                    // set the alignment of the fields
                    for ($j = 0; $j < $fields; $j++) {
                        $align[$j] = 'left';
                        $type[$j] = 's';        // type can be s for string, d for decimal, or f for float
                    }
                    $i = 1;
                    // perform the query
                    $objs = $this->Database->prepare("SELECT *
                                                      FROM LastMonitor
                                                      WHERE (LastMonitorSuccess + INTERVAL 168 HOUR) > now()
                                                      AND LastStatusCode = 0
                                                      ORDER BY LastMonitorSuccess")->execute();
                    // get the values
                    while ($objs->next()) {
                        for ($j = 0; $j < $fields; $j++) {
                            if ($rows[0][$j] == 'idsensor') {
                                // make it a link... but only if not for export
                                if (!$bExport) {
                                    $rows[$i][$j]= '<a href="index.php/Sensors/item/'.$objs->$rows[0][$j].'.html">'.$objs->$rows[0][$j].'</a>';
                                } else {
                                    $rows[$i][$j]= $objs->$rows[0][$j];
                                }
                            } elseif ($rows[0][$j] == 'LastStatusCode') {
                                // add an explanation to it
                                $rows[$i][$j]= $objs->$rows[0][$j] . ' ' . $this->explainstatus(intval($objs->$rows[0][$j]));
                            } else {
                                // round and neatly format the value, with trailing zero's
                                $rows[$i][$j]=$objs->$rows[0][$j];
                            }
                        }
                        $i++;
                    }
                    // Set-up general info
                    $this->summary = 'Monitoring Correct';
                    $this->headline = 'Systems with no problems in the last 168 hours';
                    break;
            case "mtm":
            case "mnm":
                    // first set up the field names
                    $rows[0] = array (
                        0 => 'idsensor',
                        1 => 'Date',
                        2 => 'Location',
                        3 => 'Address',
                        4 => 'Who',
                        5 => 'AgPerc',
                        6 => 'Silver',
                        7 => 'CuPerc', 
                        8 => 'Copper' ,
                    );
                    $fields = 9;
                    // set the alignment of the fields
                    for ($j = 0; $j < $fields; $j++) {
                        $align[$j] = 'left';
                        $type[$j] = 's';        // type can be s for string, d for decimal, or f for float
                    }
                    $type[5] = 'd';
                    $align[5] = 'right';
                    $type[7] = 'd';
                    $align[7] = 'right';
                    $i = 1;
                    // perform the query
                    if ($report == 'mtm') { 
                        // modify the query
                        $offset=0;
                    } else {
                        $offset=1;
                    }
                    $objs = $this->Database->prepare("SELECT NextMaintenance.idsensor, NextMaintainDate as Date, Location, 
							CONCAT (street, ' ', housenumber, ', ', Location.city) As Address,
							tl_user.name as Who,
							Ag_perc_maint as AgPerc,
							CONCAT (Sensor.Nr_Ag_Electrodes,'x', Sensor.Ag_El_length,'cm') AS Silver,
							Cu_perc_maint as CuPerc,
							CONCAT (Sensor.Nr_Cu_Electrodes,'x', Sensor.Cu_El_length,'cm') As Copper
							FROM ((NextMaintenance LEFT JOIN Sensor ON NextMaintenance.idsensor = Sensor.idsensor)
							LEFT JOIN Location ON NextMaintenance.idLocation = Location.id)
							LEFT JOIN tl_user ON NextMaintenance.uid = tl_user.id
							WHERE month(NextMaintainDate) = month(date_add(now(), INTERVAL ? MONTH))
							ORDER BY NextMaintainDate")->execute($offset);
                    // get the values
                    while ($objs->next()) {
                        for ($j = 0; $j < $fields; $j++) {
                            if ($rows[0][$j] == 'idsensor') {
                                // make it a link... but only if not for export
                                if (!$bExport) {
                                    $rows[$i][$j]= '<a href="index.php/Sensors/item/'.$objs->$rows[0][$j].'.html">'.$objs->$rows[0][$j].'</a>';
                                } else {
                                    $rows[$i][$j]= $objs->$rows[0][$j];
                                }
                            } else {
                                // round and neatly format the value, with trailing zero's
                                $rows[$i][$j]=$objs->$rows[0][$j];
                                
                            }
                        }
                        $i++;
                    }
                    // Set-up general info
                    if ($report == 'mtm') { 
                        $this->summary = 'Maintenance This Month';
                        $this->headline = 'Planned maintenance for this month';
                    } else {
                        $this->summary = 'Maintenance Next Month';
                        $this->headline = 'Planned maintenance for next month';
                    }
                    break;
            case "fixed":
                    // first set up the field names
                    $rows[0] = array (
                        0 => 'idsensor',
                        1 => 'Date',
                        2 => 'Slot',
                        3 => 'Location',
                        4 => 'Address',
                        5 => 'Who',
                     );
                    // only process 6 fields, pick up email only if it is for export
                    if ($bExport) {
                        $rows[0][6] = 'email';
                        $fields = 7;
                    } else {
                        $fields = 6;
                    }
                    // set the alignment of the fields
                    for ($j = 0; $j < $fields; $j++) {
                        $align[$j] = 'left';
                        $type[$j] = 's';        // type can be s for string, d for decimal, or f for float
                    }
                    $i = 1;
                    // perform the query
                    $objs = $this->Database->prepare("SELECT NextMaintenance.idsensor, NextMaintainDate as Date, Slot, Location, 
							CONCAT (street, ' ', housenumber, ', ', Location.city) As Address,
							tl_user.name as Who, tl_user.email as email
							FROM ((NextMaintenance LEFT JOIN Sensor ON NextMaintenance.idsensor = Sensor.idsensor)
							LEFT JOIN Location ON NextMaintenance.idLocation = Location.id)
							LEFT JOIN tl_user ON NextMaintenance.uid = tl_user.id
							WHERE IsFixed=1
							ORDER BY NextMaintainDate, Slot")->execute($offset);
                    // get the values
                    while ($objs->next()) {
                        for ($j = 0; $j < $fields; $j++) {
                            if ($rows[0][$j] == 'idsensor') {
                                // make it a link... but only if not for export
                                if (!$bExport) {
                                    $rows[$i][$j] = ' <a href="index.php/Reports/item/pdf/id/'.$objs->$rows[0][$j].'.html"><img src="/system/modules/sensor/html/iconPDF.gif" alt="Download form" /></a>';
                                    $rows[$i][$j] .= '<a href="index.php/Sensors/item/'.$objs->$rows[0][$j].'.html">'.$objs->$rows[0][$j].'</a>';
                                } else {
                                    $rows[$i][$j]= $objs->$rows[0][$j];
                                }
                            } else {
                                // round and neatly format the value, with trailing zero's
                                $rows[$i][$j]=$objs->$rows[0][$j];
                                
                            }
                        }
                        $i++;
                    }
                    // Set-up general info
                    $this->summary = 'Fixed Maintenance Dates';
                    $this->headline = 'Planned and Fixed maintenance';
                    break;
                case "mat0":
                case "mat1":
                    // first set up the field names
                    $rows[0] = array (
                        0 => 'idsensor',
                        1 => 'Date',
                        2 => 'AgPerc',
                        3 => 'AgDays',
                        4 => 'Silver',
                        5 => 'AgOrd',
                        6 => 'CuPerc',
                        7 => 'CuDays',
                        8 => 'Copper',
                        9 => 'CuOrd',
                    );
                    $fields = 10;
                    $cuweight = 0;
                    $agweight = 0;
                    // set the alignment of the fields
                    for ($j = 0; $j < $fields; $j++) {
                        $align[$j] = 'right';
                        $type[$j] = 'd';        // type can be s for string, d for decimal, or f for float
                    }
                    $type[0] = 's';
                    $align[0] = 'left';
                    $type[1] = 's';
                    $align[1] = 'left';
                    $i = 1;
                    // perform the query
                    if ($report == 'mat0') { 
                        // modify the query
                        $offset=0;
                    } else {
                        $offset=1;
                    }
                    $objs = $this->Database->prepare("SELECT NextMaintenance.idsensor, NextMaintainDate as Date, 
							Ag_perc_maint as AgPerc,
                            FLOOR((NextMaintenance.Ag_El_weight_now - 88) / Ag_g_wk * 7) as AgDays,
							CONCAT (Sensor.Nr_Ag_Electrodes,'x', Sensor.Ag_El_length,'cm') AS Silver,
                            ((NextMaintenance.Ag_El_weight_now - 88) / Ag_g_wk * 7 < 180) * Sensor.Ag_El_weight as AgOrd,
							Cu_perc_maint as CuPerc,
                            FLOOR ((NextMaintenance.Cu_El_weight_now - 88) / Cu_g_wk * 7) as CuDays,
							CONCAT (Sensor.Nr_Cu_Electrodes,'x', Sensor.Cu_El_length,'cm') As Copper,
                            ((NextMaintenance.Cu_El_weight_now - 88) / Cu_g_wk * 7 < 180)  * Sensor.Cu_El_weight as CuOrd
							FROM NextMaintenance LEFT JOIN Sensor ON NextMaintenance.idsensor = Sensor.idsensor
							WHERE month(NextMaintainDate) = month(date_add(now(), INTERVAL ? MONTH))
							ORDER BY NextMaintainDate")->execute($offset);
                    // get the values
                    while ($objs->next()) {
                        for ($j = 0; $j < $fields; $j++) {
                            if ($rows[0][$j] == 'idsensor') {
                                // make it a link... but only if not for export
                                if (!$bExport) {
                                    $rows[$i][$j]= '<a href="index.php/Sensors/item/'.$objs->$rows[0][$j].'.html">'.$objs->$rows[0][$j].'</a>';
                                } else {
                                    $rows[$i][$j]= $objs->$rows[0][$j];
                                }
                            } else {
                                // round and neatly format the value, with trailing zero's
                                // $rows[$i][$j]=sprintf ("%01.2f", round($objs->$rows[0][$j],2));
                                $rows[$i][$j]=$objs->$rows[0][$j];
                            }
                        }
                        $cuweight += $rows[$i][9];
                        $agweight += $rows[$i][5];
                        $i++;
                    }
                    for ($j = 0; $j < $fields; $j++) {  // create empty summary row
                           $rows[$i][$j] = ' ';
                    }
                    $rows[$i][0] = "Total";
                    $rows[$i][4] = "Ag (g)";
                    $rows[$i][5] = $agweight;
                    $rows[$i][8] = "Cu (g)";
                    $rows[$i][9] = $cuweight;
                    // Set-up general info
                    // Set-up general info
                    if ($report == 'mat0') { 
                        $this->summary = 'Material Usage This Month';
                        $this->headline = 'Planned materials usage for this month';
                    } else {
                        $this->summary = 'Maintenance Usage Next Month';
                        $this->headline = 'Planned materials usage for next month';
                    }
                    break;
            default:
                // little menu with items 
                $rows[0] = array (
                    0 => 'Report',
                    1 => 'Description',
                );
                $rows[1] = array (
                    0 => '<a href="'.$this->addToUrl('&item=mapping').'">Mapping list</a>',
                    1 => 'List showing mappings and how many BIFIPRO systems use them.',
                );
                $rows[2] = array (
                    0 => '<a href="'.$this->addToUrl('&item=cuagusage').'">Copper Silver usage</a>',
                    1 => 'List showing copper and silver usage compared to theoretical values.',
                );
                $rows[3] = array (
                    0 => '<a href="'.$this->addToUrl('&item=failedcall').'">Failed Call list</a>',
                    1 => 'List showing BIFIPRO systems not monitored in the last week.',
                );
                $rows[4] = array (
                    0 => '<a href="'.$this->addToUrl('&item=monitoringerrors').'">Monitoring errors</a>',
                    1 => 'List showing BIFIPRO systems with monitoring errors in the last week.',
                );
                $rows[5] = array (
                    0 => '<a href="'.$this->addToUrl('&item=monitoringfine').'">Monitoring Correct</a>',
                    1 => 'List showing BIFIPRO systems which are all ok in the last week.',
                );
                $rows[6] = array (
                    0 => '<a href="'.$this->addToUrl('&item=mtm').'">Maintenance this month</a>',
                    1 => 'List maintenance visits planned for this month.',
                );
                $rows[7] = array (
                    0 => '<a href="'.$this->addToUrl('&item=mnm').'">Maintenance next month</a>',
                    1 => 'List maintenance visits planned for next month.',
                );
                $rows[8] = array (
                    0 => '<a href="'.$this->addToUrl('&item=mat0').'">Material this month</a>',
                    1 => 'List the amount of silver and copper needed to perform maintenance this month.',
                );
                $rows[9] = array (
                    0 => '<a href="'.$this->addToUrl('&item=mat1').'">Material next month</a>',
                    1 => 'List the amount of silver and copper needed to perform maintenance next month.',
                );
                $rows[10] = array (
                    0 => '<a href="'.$this->addToUrl('&item=fixed').'">Fixed maintenance</a>',
                    1 => 'List the fixed maintenance appointments.',
                );

                // Set-up general info
                $this->summary = 'Reports list';
                $this->headline = 'Reports list';
        }
        
		// $rows = deserialize($this->tableitems);
		$nl2br = ($objPage->outputFormat == 'xhtml') ? 'nl2br_xhtml' : 'nl2br_html5';
        
        if ($bExport) {
            if ($report == 'fixed') {
                // Export the table as a calendar
                require_once 'iCalcreator.class.php';

                $c = new vcalendar( array( 'unique_id'=>'sensor.com', 'filename'=>'sensor.ics' )); // initiate new CALENDAR
                // skip header row so start at $j=1
                $limit = count($rows);
                $j = 1;
                for ($j=1; $j<$limit; $j++)
                {
                    $e = & $c->newComponent( 'vevent' );                    // initiate a new EVENT
                    $e->setProperty( 'categories', 'maintenance' );         // categorize
                    $e->setProperty( 'dtstart', $rows[$j][1] . ' ' . substr($rows[$j][2], 0, 5));     // date as date time string
                    $e->setProperty( 'duration', 0, 0, 1 );                 // 1 hours
                    $e->setProperty( 'summary', $rows[$j][0] . ' maintenance' );  // describe the event, P00xx 
                    $e->setProperty( 'location', $rows[$j][3] . ' ' . $rows[$j][4]);  // locate the event, location, address
                    $e->setProperty( 'attendee', $rows[$j][6] );                      // add email address as attendee               
                    $a = & $e->newComponent( 'valarm' );           // initiate ALARM
                    $a->setProperty( 'action', 'DISPLAY' );        // set what to do
                    $a->setProperty( 'description', 'Maintenance alert' );   // describe alarm
                    $a->setProperty( 'trigger', array( 'hour' => 1 ));        // set trigger one hour before
                }

                /* alt. production */
                $c->returnCalendar();                       // generate and redirect output to user browser

                // alt. dev. and test 
                // echo nl2br( $c->createCalendar()) ;            // generate and get output in string, for testing?
                // echo "<br />\n\n";
                exit();

            } else {
                // Export the table in Excel, data is in rows with rows[0] as headers
                include(TL_ROOT.'/plugins/xls_export/xls_export.php');
                $strExpEncl = '"';
                $strExpSep = '';

                $xls = new xlsexport();
                $strXlsSheet = "Export";
                $xls->addworksheet($strXlsSheet);
                $intRowCounter = 0;
                $limit = count($rows);
                
                for ($j=0; $j<$limit; $j++)
                {
                    $intColCounter = -1;
                    foreach ($rows[$j] as $i=>$v)
                    {
                        $strname = $v;
                        $intColCounter++;

                        if (strlen($strname))
                        {
                            $strname = $this->String->decodeEntities($strname);
                        }
                        // convert UTF8 to cp1251 on CSV-/XLS-Export
                        $strname = $this->convertEncoding($strname, $GLOBALS['TL_CONFIG']['characterSet'], 'CP1252');
                        if ($j == 0)
                        {
                            $xls->setcell(array("sheetname" => $strXlsSheet,"row" => $intRowCounter, "col" => $intColCounter, "data" => $strname, "fontweight" => XLSFONT_BOLD, "vallign" => XLSXF_VALLIGN_TOP, "fontfamily" => XLSFONT_FAMILY_NORMAL));
                            $xls->setcolwidth($strXlsSheet,$intColCounter,0x1aff);
                        } else {
                            switch ($type[$i]) { 
                                case "d":
                                    $xls_type = CELL_FLOAT;
                                    break;
                                case "f":    
                                    $xls_type = CELL_FLOAT;
                                    break;
                                default:    
                                    $xls_type = CELL_STRING;
                            }
                            $xls->setcell(array("sheetname" => $strXlsSheet,"row" => $intRowCounter, "col" => $intColCounter, "data" => $strname, "type" => $xls_type, "vallign" => XLSXF_VALLIGN_TOP, "fontfamily" => XLSFONT_FAMILY_NORMAL));
                        }

                    } // foreach
                    $intRowCounter++;
                } // for
                $xls->sendfile("export_" . $report . "_" . date("Ymd_His") . ".xls");
                exit;
            } // else $report==fixed
        } // if $bExport
        
        // create the new reports template
        $this->Template = new FrontendTemplate ($this->strTemplate);

		$this->Template->id = 'table_' . $this->id;
		$this->Template->summary = specialchars($this->summary);
		$this->Template->useHeader = $this->thead ? true : false;
		$this->Template->useFooter = $this->tfoot ? true : false;
		$this->Template->useLeftTh = $this->tleft ? true : false;
		$this->Template->sortable = false;
		$this->Template->thousandsSeparator = $GLOBALS['TL_LANG']['MSC']['thousandsSeparator'];
		$this->Template->decimalSeparator = $GLOBALS['TL_LANG']['MSC']['decimalSeparator'];
        
        // add an export link
        $this->Template->export = '<a href="'.$this->addToUrl('&act=export&item='.$report).'">Export</a>';
        $this->Template->b = $bExport;

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