<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

OpenEMIS
Open Education Management Information System

Copyright © 2013 UNECSO.  This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by the Free Software Foundation
, either version 3 of the License, or any later version.  This program is distributed in the hope
that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
or FITNESS FOR A PARTICULAR PURPOSE.See the GNU General Public License for more details. You should
have received a copy of the GNU General Public License along with this program.  If not, see
<http://www.gnu.org/licenses/>.  For more information please wire to contact@openemis.org.
*/

require_once ("iSeries.php");
class Series implements iSeries{
    private $attributes = array();
    private $area;
    private $indicator;
    private $observations = array();
    private $source;
    private $subgroups = array();
    private $unit;

    public function __construct($indicator, $area, $source, $unit, $subgroup, $observations=array(), $attributes=array() ){
        $this->setIndicator($indicator);
        $this->setArea($area);
        $this->setSource($source);
        $this->setSubgroups($subgroup);
        $this->setUnit($unit);
        $this->setAttributes($attributes);
        $this->observations = $observations;
    }

    public function setAttributes($attributes)
    {
        $this->attributes = array_merge($this->attributes, $attributes);
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    public function setArea($area)
    {
        $this->area = $area;
    }

    public function getArea()
    {
        return $this->area;
    }

    public function setIndicator($name)
    {
        $this->indicator = $name;
    }

    public function getIndicator()
    {
        return $this->indicator;
    }

    public function getObservations()
    {
        return $this->observations;
    }

    public function getObservationsCount()
    {
        return sizeof($this->observations);
    }

    public function addObservation(Observation $obs)
    {
        array_push($this->observations,$obs);
    }

    public function setSource($source)
    {
        $this->source = $source;
    }

    public function getSource()
    {
        return $this->source;
    }

    /**
     * @param array $subgroups
     */
    public function setSubgroups($subgroups)
    {
        $this->subgroups = $subgroups;
    }

    /**
     * @return array
     */
    public function getSubgroups()
    {
        return $this->subgroups;
    }

    public function setUnit($unit)
    {
        $this->unit = $unit;
    }

    public function getUnit()
    {
        return $this->unit;
    }

}