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

App::uses('AppModel', 'Model');

class FinanceCategory extends AppModel {
	public $belongsTo = array('FinanceType');
	
	public function getLookupVariables() {
		$Nature = ClassRegistry::init('FinanceNature');
		$list = $Nature->findList();
		$lookup = array();
		
		foreach($list as $id => $name) {
			$lookup[$name] = array('model' => 'FinanceCategory', 'conditions' => array('finance_nature_id' => $id));
		}
		return $lookup;
	}
	
	public function findOptions($options=array()) {
		$Type = ClassRegistry::init('FinanceType');
		$items = array();
		
		$typeList = $Type->findList($options);
		foreach($typeList as $typeId => $typeName) {
			$conditions = array('finance_type_id' => $typeId);
			$items[$typeName] = array(
				'conditions' => $conditions,
				'options' => parent::findOptions(array('conditions' => $conditions))
			);
		}
		return $items;
	}
}
