<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-16

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

class VisualizerAreaLevel extends VisualizerAppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_area_level_en';
	public $alias = 'AreaLevel';
	
	public function getAreaLevelList(){
		$data = $this->find('list', array('fields' => array('Level_NId', 'Area_Level_Name')));
		return $data;
	}
	
	public function getAreaLevelUpto($arealevel_nid){
		$areaLevelData = $this->find('first', array('fields' => array('Area_Level'), 'conditions' => array('Level_NId '=>$arealevel_nid)));
		
		$data = $this->find('list', array('fields' => array('Level_NId', 'Area_Level_Name'), 'conditions' => array('Area_Level <= ' => $areaLevelData['AreaLevel']['Area_Level'])));
		return $data;
	}
	
	public function getAllAreaLevel(){
		$data = $this->find('all', array('fields' => array('Level_NId', 'Area_Level_Name','Area_Level')));
		return $data;
	}
}
