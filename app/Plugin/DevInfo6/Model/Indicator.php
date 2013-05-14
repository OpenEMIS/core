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

class Indicator extends DevInfo6AppModel {
	public $useDbConfig = 'di6';
	public $useTable = 'UT_Indicator_en';
	public $dataSet = array(
		'primaryKey' => array()
	);
	
	public $info = "<?xml version=\"1.0\" encoding=\"UTF-8\"?><Indicator_Info><Row1><Fld_Name>Definition</Fld_Name><FLD_VAL><RowData><temp1>%s</temp1></RowData></FLD_VAL></Row1></Indicator_Info>";
	
	public function getPrimaryKey($name, $metadata) {
		$id = 0;
		$modelName = 'Indicator';
		$set = 'primaryKey';
		
		if(isset($this->dataSet[$set][$name])) {
			$id = $this->dataSet[$set][$name];
		} else {
			// check if the indicator is already in DevInfo database
			$first = $this->find('first', array('conditions' => array($modelName . '.Indicator_Name' => $name)));
			
			if(!$first) { // if not exists in DevInfo DB, create the indicator
				$uuid = String::uuid();
				$model = array(
					$modelName => array(
						'Indicator_Name' => $name,
						'Indicator_GId' => $uuid,
						'Indicator_Global' => 0
					)
				);
				
				if(strlen($metadata) > 0) {
					$model[$modelName]['Indicator_Info'] = sprintf($this->info, $metadata);
				}
				$this->create();
				$save = $this->save($model);
				$id = $save[$modelName]['id'];
			} else { // if exists, get the Id from DevInfo DB
				$id = $first[$modelName]['Indicator_NId'];
			}
			$this->dataSet[$set][$name] = $id;
		}
		return $id;
	}
}
