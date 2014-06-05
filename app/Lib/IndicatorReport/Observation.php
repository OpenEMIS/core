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

require_once ("iObservation.php");
class Observation implements iObservation{
    private $value;
    private $classifications;
    private $denominator;
    private $footnote;
    private $timeperiod;
    private $attributes;

    public function __construct($value, $denominator, $footnote, $timeperiod, $classifications, $attributes){
        $this->setValue($value);
        $this->setDenominator($denominator);
        $this->setFootnote($footnote);
        $this->setTimeperiod($timeperiod);
        $this->setAttributes($attributes);
        $this->setClassifications($classifications);
    }

    public function setAttributes($attributes)
    {
        $this->attributes = $attributes;
    }

    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param mixed $classifications
     */
    public function setClassifications($classifications)
    {
        $this->classifications = $classifications;
    }

    /**
     * @return mixed
     */
    public function getClassifications()
    {
        return $this->classifications;
    }

    public function setDenominator($denominator)
    {
        $this->denominator = $denominator;
    }

    public function getDenominator()
    {
        return $this->denominator;
    }

    public function setFootnote($footnote)
    {
        $this->footnote = $footnote;
    }

    public function getFootnote()
    {
        return $this->footnote;
    }

    public function setTimeperiod($timeperiod)
    {
        $this->timeperiod = $timeperiod;
    }

    public function getTimeperiod()
    {
        return $this->timeperiod;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }


}