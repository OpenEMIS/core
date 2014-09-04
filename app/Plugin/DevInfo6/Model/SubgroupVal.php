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

class SubgroupVal extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_subgroup_vals_en';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public function getPrimaryKey($name, $subgroupTypes) {
		$id = 0;
		$modelName = 'SubgroupVal';
		$set = 'primaryKey';
		$Subgroup = ClassRegistry::init('DevInfo6.Subgroup');
		$SubgroupValsSubgroup = ClassRegistry::init('DevInfo6.SubgroupValsSubgroup');

		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			$first = $this->find('first', array('conditions' => array($modelName . '.Subgroup_Val' => $name)));
			
			if(!$first) {
				$model = array(
					$modelName => array(
						'Subgroup_Val' => $name,
						'Subgroup_Val_GId' => String::uuid(),
						'Subgroup_Val_Global' => 0
					)
				);
				$this->create();
				$save = $this->save($model);
				$id = $save[$modelName]['id'];
				
				//$subgroupList = explode(' - ', $name);
				$subgroupList = explode(', ', $name);
				foreach($subgroupList as $subgroupName) {
					$subgroupType = $this->getSubgroupType($subgroupTypes, $subgroupName);
					if(!empty($subgroupType)){
						$subgroupId = $Subgroup->getPrimaryKey($subgroupName, $subgroupType);
					
						$model = array('SubgroupValsSubgroup' => array('Subgroup_Val_NId' => $id, 'Subgroup_NId' => $subgroupId));
						
						$SubgroupValsSubgroup->create();
						$SubgroupValsSubgroup->save($model);
					}
				}
			} else {
				$id = $first[$modelName]['Subgroup_Val_NId'];
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
	
	private function getSubgroupType($types, $subgroup) {
		/*foreach($types as $type => $list) {
			if(in_array($subgroup, $list)) {
				return array($type => $list['order']);
			}
		}
		return NULL;*/
		//JAMIE
		if(strrpos($subgroup, ": ")!==false){
			$subgroup = substr($subgroup, 0, strrpos($subgroup, ": "));
		}
		foreach($types as $type => $list) {
			if($list==$subgroup || ('All ' . inflector::pluralize($list)==$subgroup)){
				return array($list => ($type+1));
				break;
			}
		}

		return NULL;
	}
}
