<?php if (!defined('TL_ROOT')) die('You can not access this file directly!');

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 * 
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 **/
 
// 
// <author>Peter van Es</author>
// <version>1.0</version>
// <email>vanes@hollandenvironment.com</email>
// <date>2012-02-27</date>
// <summary>Bezier</summary>

//------------------------------------------------------------------------
// CLASS Bezier
// Create a new data array from a number of control points
//------------------------------------------------------------------------
class Bezier {
    //
    // @author Thomas Despoix, openXtrem company
    // @license released under QPL
    // @abstract Bezier interoplated point generation,
    // computed from control points data sets, based on Paul Bourke algorithm :
    // http://local.wasp.uwa.edu.au/~pbourke/geometry/bezier/index2.html
    
    private $datax = array();
    private $datay = array();
    private $n=0;

    function __construct($datax, $datay, $attraction_factor = 1) {
        // Adding control point multiple time will raise their attraction power over the curve
        $this->n = count($datax);
        if( $this->n !== count($datay) ) {
                $this->log('Legionella Bezier: Number of X and Y coordinates must be the same',__METHOD__,'INFO');
                return;
        }
        $idx=0;
        foreach($datax as $datumx) {
            for ($i = 0; $i < $attraction_factor; $i++) {
                $this->datax[$idx++] = $datumx;
            }
        }
        $idx=0;
        foreach($datay as $datumy) {
            for ($i = 0; $i < $attraction_factor; $i++) {
                $this->datay[$idx++] = $datumy;
            }
        }
        $this->n *= $attraction_factor;
    }

    //
    // Return a set of data points that specifies the bezier curve with $steps points
    // @param $steps Number of new points to return
    // @return array($datax, $datay)
    
    function Get($steps) {
        $datax = array();
        $datay = array();
        for ($i = 0; $i < $steps; $i++) {
            list($datumx, $datumy) = $this->GetPoint((double) $i / (double) $steps);
            $datax[$i] = $datumx;
            $datay[$i] = $datumy;
        }
         
        $datax[] = end($this->datax);
        $datay[] = end($this->datay);
         
        return array($datax, $datay);
    }

    //
    // Return one point on the bezier curve. $mu is the position on the curve where $mu is in the
    // range 0 $mu < 1 where 0 is tha start point and 1 is the end point. Note that every newly computed
    // point depends on all the existing points
    // 
    // @param $mu Position on the bezier curve
    // @return array($x, $y)
    
    function GetPoint($mu) {
        $n = $this->n - 1;
        $k = 0;
        $kn = 0;
        $nn = 0;
        $nkn = 0;
        $blend = 0.0;
        $newx = 0.0;
        $newy = 0.0;

        $muk = 1.0;
        $munk = (double) pow(1-$mu,(double) $n);

        for ($k = 0; $k <= $n; $k++) {
            $nn = $n;
            $kn = $k;
            $nkn = $n - $k;
            $blend = $muk * $munk;
            $muk *= $mu;
            $munk /= (1-$mu);
            while ($nn >= 1) {
                $blend *= $nn;
                $nn--;
                if ($kn > 1) {
                    $blend /= (double) $kn;
                    $kn--;
                }
                if ($nkn > 1) {
                    $blend /= (double) $nkn;
                    $nkn--;
                }
            }
            $newx += $this->datax[$k] * $blend;
            $newy += $this->datay[$k] * $blend;
        }

        return array($newx, $newy);
    }
}

?>