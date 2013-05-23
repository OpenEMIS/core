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

class IndicatorClassificationIUS extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'ut_indicator_classifications_ius';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public function getPrimaryKey($ICId, $IUSId) {
		$id = 0;
		$modelName = 'IndicatorClassificationIUS';
		$set = 'primaryKey';
		$name = implode(',', array($ICId, $IUSId));
		
		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			$conditions = array($modelName . '.IC_NId' => $ICId, $modelName . '.IUSNId' => $IUSId);
			$first = $this->find('first', array('conditions' => $conditions));
			
			if(!$first) {
				$model = array(
					$modelName => array(
						'IC_NId' => $ICId,
						'IUSNId' => $IUSId,
						'RecommendedSource' => 0,
						'IC_IUS_Order' => null,
						'IC_Label' => null
					)
				);
				$this->create();
				$save = $this->save($model);
				$id = $save[$modelName]['id'];
			} else {
				$id = $first[$modelName]['IC_IUSNId'];
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
}
