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

// <summary>Spline class</summary>


//------------------------------------------------------------------------
// CLASS Spline
// Create a new data array from an existing data array but with more points.
// The new points are interpolated using a cubic spline algorithm
// Description: Regression and statistical analysis helper classes
// Created:     2002-12-01
// Ver:         $Id: jpgraph_regstat.php 1131 2009-03-11 20:08:24Z ljp $
//
// Copyright (c) Asial Corporation. All rights reserved.
/* Invocation...

    // Original data points
    $xdata = array(1,3,5,7,9,12,15,17.1);
    $ydata = array(5,1,9,6,4,3,19,12);

    // Get the interpolated values by creating
    // a new Spline object.
    $spline = new Spline($xdata,$ydata);

    // For the new data set we want 40 points to
    // get a smooth curve.
    list($newx,$newy) = $spline->Get(50);
    
    // list assigns a number of variables in one go to an array

*/    
//------------------------------------------------------------------------

class Spline extends ContentImage {
    // 3:rd degree polynom approximation

    private $xdata,$ydata;   // Data vectors
    private $y2;   // 2:nd derivate of ydata
    private $n=0;

    function __construct($xdata,$ydata) {
        $this->y2 = array();
        $this->xdata = $xdata;
        $this->ydata = $ydata;

        $n = count($ydata);
        $this->n = $n;
        if( $this->n !== count($xdata) ) {
            // $this->log becomes echo
            echo 'Spline: Number of X and Y coordinates must be the same.';
            return;
        }

        // Natural spline 2:derivate == 0 at endpoints
        $this->y2[0]    = 0.0;
        $this->y2[$n-1] = 0.0;
        $delta[0] = 0.0;

        // Calculate 2:nd derivate
        for($i=1; $i < $n-1; ++$i) {
            $d = ($xdata[$i+1]-$xdata[$i-1]);
            if( $d == 0  ) {
                echo 'Spline: Invalid input data for spline. Two or more consecutive input X-values are equal. Each input X-value must differ since from a '.  'mathematical point of view it must be a one-to-one mapping, i.e. each X-value must correspond to exactly one Y-value. ';
                return;
            }
            $s = ($xdata[$i]-$xdata[$i-1])/$d;
            $p = $s*$this->y2[$i-1]+2.0;
            $this->y2[$i] = ($s-1.0)/$p;
            $delta[$i] = ($ydata[$i+1]-$ydata[$i])/($xdata[$i+1]-$xdata[$i]) -
            ($ydata[$i]-$ydata[$i-1])/($xdata[$i]-$xdata[$i-1]);
            $delta[$i] = (6.0*$delta[$i]/($xdata[$i+1]-$xdata[$i-1])-$s*$delta[$i-1])/$p;
        }

        // Backward substitution
        for( $j=$n-2; $j >= 0; --$j ) {
            $this->y2[$j] = $this->y2[$j]*$this->y2[$j+1] + $delta[$j];
        }
    }

    // Return the two new data vectors
    function Get($num=50) {
        $n = $this->n ;
        $step = ($this->xdata[$n-1]-$this->xdata[0]) / ($num-1);
        $xnew=array();
        $ynew=array();
        $xnew[0] = $this->xdata[0];
        $ynew[0] = $this->ydata[0];
        for( $j=1; $j < $num; ++$j ) {
            $xnew[$j] = $xnew[0]+$j*$step;
            $ynew[$j] = $this->Interpolate($xnew[$j]);
        }
        return array($xnew,$ynew);
    }

    // Return a single interpolated Y-value from an x value
    function Interpolate($xpoint) {

        $max = $this->n-1;
        $min = 0;

        // Binary search to find interval
        while( $max-$min > 1 ) {
            $k = ($max+$min) / 2;
            if( $this->xdata[$k] > $xpoint )
            $max=$k;
            else
            $min=$k;
        }

        // Each interval is interpolated by a 3:degree polynom function
        $h = $this->xdata[$max]-$this->xdata[$min];

        if( $h == 0  ) {
                echo 'Spline: Invalid input data for spline. Two or more consecutive input X-values are equal. Each input X-value must differ since from a '.  'mathematical point of view it must be a one-to-one mapping, i.e. each X-value must correspond to exactly one Y-value.';
                return;
        }

        $a = ($this->xdata[$max]-$xpoint)/$h;
        $b = ($xpoint-$this->xdata[$min])/$h;
        return $a*$this->ydata[$min]+$b*$this->ydata[$max]+
        (($a*$a*$a-$a)*$this->y2[$min]+($b*$b*$b-$b)*$this->y2[$max])*($h*$h)/6.0;
    }
}

?>