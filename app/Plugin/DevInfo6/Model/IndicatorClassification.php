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

class IndicatorClassification extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'UT_Indicator_Classifications_en';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public function getPrimaryKey($name, $type, $parent) {
		$id = 0;
		$modelName = 'IndicatorClassification';
		$set = 'primaryKey';
		
		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			$names = explode(' - ', $name);
			
			foreach($names as $n) {
				$conditions = array($modelName . '.IC_Name' => $n, $modelName . '.IC_Type' => $type);
				$first = $this->find('first', array('conditions' => $conditions));
				if(!$first) {
					$model = array(
						$modelName => array(
							'IC_Parent_NId' => $parent,
							'IC_GId' => String::uuid(),
							'IC_Name' => $n,
							'IC_Global' => 0,
							'IC_Info' => '',
							'IC_Type' => $type
						)
					);
					$this->create();
					$save = $this->save($model);
					$id = $save[$modelName]['id'];
				} else {
					$id = $first[$modelName]['IC_NId'];
				}
				$parent = $id;
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
	
	public function initSource($source) {
		$parentSource = 'OpenEMIS';
		$modelName = 'IndicatorClassification';
		
		$model = array(
			$modelName => array(
				'IC_Parent_NId' => -1,
				'IC_GId' => String::uuid(),
				'IC_Name' => $parentSource,
				'IC_Global' => 0,
				'IC_Info' => '',
				'IC_Type' => 'SR'
			)
		);
		
		$this->create();
		$save = $this->save($model);
	
		$model[$modelName]['IC_Parent_NId'] = $save[$modelName]['id'];
		$model[$modelName]['IC_GId'] = String::uuid();
		$model[$modelName]['IC_Name'] = $source;
		
		$this->create();
		$save = $this->save($model);
		return $save[$modelName]['id'];
	}
	
	public function initSector($sector) {
		$model = array(
			'IndicatorClassification' => array(
				'IC_Parent_NId' => -1,
				'IC_GId' => String::uuid(),
				'IC_Name' => $sector,
				'IC_Global' => 0,
				'IC_Info' => '',
				'IC_Type' => 'SC'
			)
		);
		
		$this->create();
		$save = $this->save($model);
		return $save['IndicatorClassification']['id'];
	}
}
