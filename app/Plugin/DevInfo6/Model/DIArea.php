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

class DIArea extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'UT_Area_en';
	
	public function import($levelMap) {
		$this->truncate();
		
		$AreaModel = ClassRegistry::init('Area');
		$areaList = $AreaModel->find('all', array('recursive' => 0));
		
		foreach($areaList as $obj) {
			$area = $obj['Area'];
			$model = array(
				'DIArea' => array(
					'Area_NId' => $area['id'],
					'Area_Parent_NId' => $area['parent_id'],
					'Area_ID' => $area['code'],
					'Area_Name' => $area['name'],
					'Area_GId' => String::uuid(),
					'Area_Level' => $levelMap[$area['area_level_id']],
					'Area_Map' => '',
					'Area_Block' => '',
					'Area_Global' => 0
				)
			);
			
			$this->create();
			$this->save($model);
		}
	}
}
