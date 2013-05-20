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


class DIAreaLevel extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'UT_Area_Level_en';
	
	public function import() {
		$this->truncate();
		
		$levelMap = array();
		$AreaLevel = ClassRegistry::init('AreaLevel');
		$areaLevelList = $AreaLevel->find('all', array('recursive' => 0));
		
		foreach($areaLevelList as $obj) {
			$level = $obj['AreaLevel'];
			$levelMap[$level['id']] = $level['level'];
			$model = array(
				'DIAreaLevel' => array(
					'Level_NId' => $level['id'],
					'Area_Level' => $level['level'],
					'Area_Level_Name' => $level['name']
				)
			);
			
			$this->create();
			$this->save($model);
		}
		
		return $levelMap;
	}
}
