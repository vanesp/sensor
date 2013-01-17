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
// <summary>Spline class, with smoothing, based on a Constrained Cubic Spline Interpolation</summary>

//------------------------------------------------------------------------
// CLASS Spline
// Create a new data array from an existing data array but with more points.
// The new points are interpolated using a cubic spline algorithm
// Based on CJC Kruger http://www.korf.co.uk/spline.pdf
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
    private $y2;             // 2:nd derivate of ydata
    private $delta;
    private $n=0;

    function __construct($xdata,$ydata) {
        $this->y2 = array();
        $this->delta = array();
        $this->xdata = $xdata;
        $this->ydata = $ydata;
        $this->gxx = array();
        $this->ggxx = array();

        $n = count($ydata);
        $this->n = $n;
        if( $this->n !== count($xdata) ) {
            // $this->log becomes echo
            echo 'Spline: Number of X and Y coordinates must be the same.';
            return;
        }

        $last = $n - 1;
        // first do boundary values....
        $delta[0] = 0;
        $this->y2[0] = 0;
        $delta[$last] = 0;
        $this->y2[$last] = 0;
        
        // Calculate first derivative for intermediate points
        for($k=1; $k < $last; ++$k) {
            for ($j=0; $j<=1; $j++) {
                $i = $k - 1 + $j;   // point under consideration
                if ($i == 0 || $i == $last) {
                    // if we are at the ends, set a large slope
                    $gxx[$j] = pow(10,30);
                } elseif ((($ydata[$i+1] - $ydata[$i]) == 0) || (($ydata[$i] - $ydata[$i-1])==0)) {
                    // 0 dy, no slope. Assume 0 dx does not happen (all x values increasing)
                    $gxx[$j] = 0;
                } elseif ((($xdata[$i+1] - $xdata[$i]) / ($ydata[$i+1] - $ydata[$i])) + (($xdata[$i] - $xdata[$i-1])/($ydata[$i] - $ydata[$i-1])) == 0) {
                    // Positive PLUS negative slope is 0... prevent division by 0
                    $gxx[$j] = 0;
                } elseif ((($ydata[$i+1] - $ydata[$i])* ($ydata[$i] - $ydata[$i-1])) < 0) {
                    // Pos AND neg slope, assume slope is 0 to prevent overshoot
                    $gxx[$j] = 0;
                } else {
                    // calculate an average slope for the point based on the connecting lines
                    $gxx[$j] = 2 / ($this->dxx($xdata[$i+1], $xdata[$i]) / ($ydata[$i+1] - $ydata[$i]) + $this->dxx($xdata[$i], $xdata[$i-1])/($ydata[$i] - $ydata[$i-1]));
                }
            } // end for $j
            
            // Reset the first derivative (slope) at the first and last point
            if ($k == 1) {
                // the first point has a 0 2nd derivative
                $gxx[0] = 3 / 2 * ($ydata[$k] - $ydata[$k-1]) / $this->dxx($xdata[$k], $xdata[$k-1]) - $gxx[1]/2;
            }
            if ($k == $last) {
                // the last point has a 0 2nd derivative
                $gxx[1] = 3 / 2 * ($ydata[$k] - $ydata[$k-1]) / $this->dxx($xdata[$k], $xdata[$k-1]) - $gxx[0]/2;
            }
            
            // Calculate the second derivative at the points
            $ggxx[0] = -2 * ($gxx[1]+ 2*$gxx[0])/$this->dxx($xdata[$k], $xdata[$k-1]) + 6 * ($ydata[$k] - $ydata[$k-1])/pow($this->dxx($xdata[$k], $xdata[$k-1]),2);
            $ggxx[1] = 2 * (2*$gxx[1]+ $gxx[0])/$this->dxx($xdata[$k], $xdata[$k-1]) - 6 * ($ydata[$k] - $ydata[$k-1])/pow($this->dxx($xdata[$k], $xdata[$k-1]),2);

            // Calculate the constants for the Cubic
            $D = 1/6*($ggxx[1]-$ggxx[0]) / $this->dxx($xdata[$k], $xdata[$k-1]);
            $C = 1/2*($xdata[$k] * $ggxx[0] - $xdata[$k-1] * $ggxx[1]) / $this->dxx($xdata[$k], $xdata[$k-1]);
            $B = ($ydata[$k] - $ydata[$k-1] - $C *($xdata[$k]*$xdata[$k] - $xdata[$k-1]*$xdata[$k-1]) - $D * (pow($xdata[$k], 3) - pow($xdata[$k-1], 3))) / $this->dxx($xdata[$k], $xdata[$k-1]);
            $A = $ydata[$k-1] - $B * $xdata[$k-1] - $C * $xdata[$k-1]*$xdata[$k-1] - $D * pow($xdata[$k-1], 3);
            $delta[$k] = $D;
            $this->y2[$k] = $C;
        } // end of for $k

        // Backward substitution
        //for( $j=$n-2; $j >= 0; --$j ) {
        //    $this->y2[$j] = $this->y2[$j]*$this->y2[$j+1] + $delta[$j];
        // }
    }

    // Calculate the difference between two numbers but prevent division by 0
    function dxx($x1, $x0) {
        $dxx = $x1 - $x0;
        if ($dxx == 0) {
            $dxx = pow(10,30);
        }
        return $dxx;
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

        // Calculate the constants for the Cubic
        $D = $this->delta[$k];
        $C = $this->y2[$k];
        $B = ($ydata[$k] - $ydata[$k-1] - $C *($xdata[$k]*$xdata[$k] - $xdata[$k-1]*$xdata[$k-1]) - $D * (pow($xdata[$k], 3) - pow($xdata[$k-1], 3))) / $this->dxx($xdata[$k], $xdata[$k-1]);
        $A = $ydata[$k-1] - $B * $xdata[$k-1] - $C * $xdata[$k-1]*$xdata[$k-1] - $D * pow($xdata[$k-1], 3);
 
        return $A + $B * $xpoint + $C * $xpoint * $xpoint + $D * $xpoint * $xpoint * $xpoint;
        
    }
}

?>