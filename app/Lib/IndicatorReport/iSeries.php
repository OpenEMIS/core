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

interface iSeries {

    public function setAttributes($attributes);
    public function getAttributes();
    public function setArea($area);
    public function getArea();
    public function setIndicator($name);
    public function getIndicator();
    public function getObservations();
    public function getObservationsCount();
    public function addObservation(Observation $obs);
    public function setSource($source);
    public function getSource();
    public function setUnit($unit);
    public function getUnit();

}