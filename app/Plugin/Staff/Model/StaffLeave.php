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

class StaffLeave extends StaffAppModel {
	public $belongsTo = array(
		'StaffLeaveType',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);
	
	public $validate = array(
		'date_from' => array(
			'ruleNotLater' => array(
				'rule' => array('compareDate', 'date_to'),
				'message' => 'Date From cannot be later than Date To'
			),
			'ruleNoOverlap' => array(
				'rule' => array('checkOverlapDates'),
				'message' => 'Date range is exists, please check'
			)
		)
	);
	
	public function compareDate($field = array(), $compareField = null) {
		$startDate = new DateTime(current($field));
		$endDate = new DateTime($this->data[$this->name][$compareField]);
		return $endDate > $startDate;
	}
	
	public function checkOverlapDates($field = array()) {
		$data = $this->data[$this->name];
		$startDate = $data['date_from'];
		$endDate = $data['date_to'];
		
		$conditions = array(
			'OR' => array(
				array('date_from <=' => $startDate, 'date_to >=' => $startDate),
				array('date_from <=' => $endDate, 'date_to >=' => $endDate),
				array('date_from >=' => $startDate, 'date_from <=' => $endDate)
			),
			'StaffLeave.staff_id' => $data['staff_id']
		);
		
		if(isset($data['id'])) {
			$conditions['StaffLeave.id <>'] = $data['id'];
		}
		$check = $this->find('all', array('recursive' => -1, 'conditions' => $conditions));
		return empty($check);
	}
}
