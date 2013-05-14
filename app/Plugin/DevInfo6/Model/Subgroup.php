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

class Subgroup extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'UT_Subgroup_en';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public function getPrimaryKey($name, $subgroupType) {
		$id = 0;
		$modelName = 'Subgroup';
		$set = 'primaryKey';
		$SubgroupTypeModel = ClassRegistry::init('DevInfo6.SubgroupType');
		
		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			$first = $this->find('first', array('conditions' => array($modelName . '.Subgroup_Name' => $name)));
			
			if(!$first) {
				$type = key($subgroupType);
				$order = $subgroupType[$type];
				$typeId = $SubgroupTypeModel->getPrimaryKey($type, $order);
				$model = array(
					$modelName => array(
						'Subgroup_Name' => $name,
						'Subgroup_GId' => String::uuid(),
						'Subgroup_Type' => $typeId,
						'Subgroup_Global' => 0
					)
				);
				$this->create();
				$save = $this->save($model);
				$id = $save[$modelName]['id'];
			} else {
				$id = $first[$modelName]['Subgroup_NId'];
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
}
