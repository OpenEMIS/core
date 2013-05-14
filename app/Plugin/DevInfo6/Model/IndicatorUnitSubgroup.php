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

class IndicatorUnitSubgroup extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'UT_Indicator_Unit_Subgroup';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public function getPrimaryKey($indicator, $unit, $subgroup) {
		$id = 0;
		$modelName = 'IndicatorUnitSubgroup';
		$set = 'primaryKey';
		$name = implode(',', array($indicator, $unit, $subgroup));
		
		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			$first = $this->find('first', array(
				'conditions' => array(
						$modelName . '.Indicator_NId' => $indicator,
						$modelName . '.Unit_NId' => $unit,
						$modelName . '.Subgroup_Val_NId' => $subgroup
					)
				)
			);
			
			if(!$first) {
				$model = array(
					$modelName => array(
						'Indicator_NId' => $indicator,
						'Unit_NId' => $unit,
						'Subgroup_Val_NId' => $subgroup,
						'Min_Value' => 0,
						'Max_Value' => 0
					)
				);
				$this->create();
				$save = $this->save($model);
				$id = $save[$modelName]['id'];
			} else {
				$id = $first[$modelName]['IUSNId'];
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
}
