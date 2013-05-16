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

class SchoolYear extends AppModel {
	public function getAvailableYears($list = true, $order='DESC') {
		if($list) {
			$result = $this->find('list', array(
				'fields' => array('SchoolYear.id', 'SchoolYear.name'),
				'conditions' => array('SchoolYear.available' => 1),
				'order' => array('SchoolYear.name ' . $order)
			));
		} else {
			$result = $this->find('all', array(
				'conditions' => array('SchoolYear.available' => 1),
				'order' => array('SchoolYear.name ' . $order)
			));
		}
		return $result;
	}
	
	public function getYearList($type='name', $order='DESC') {
		$value = 'SchoolYear.' . $type;
		$result = $this->find('list', array(
			'fields' => array('SchoolYear.id', $value),
			'order' => array($value . ' ' . $order)
		));
		return $result;
	}
	
	public function getYearListValues($type='name', $order='DESC') {
		$value = 'SchoolYear.' . $type;
		$result = $this->find('list', array(
			'fields' => array($value, $value),
			'order' => array($value . ' ' . $order)
		));
		return $result;
	}
	
	public function getLookupVariables() {
		$modelName = get_class($this);
		
		$list = $this->find('all', array('order' => array('SchoolYear.name DESC')));
		$options = array();
		foreach($list as $obj) {
			$options[] = $obj['SchoolYear'];
		}
		$lookup = array('School Year' => array('model' => $modelName, 'options' => $options));
		return $lookup;
	}
}
