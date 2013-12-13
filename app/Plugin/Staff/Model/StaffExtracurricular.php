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

// App::uses('StudentsAppModel', 'Model');

class StaffExtracurricular extends StaffAppModel {
	public $belongsTo = array(
		'Staff',
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
		'name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Title.'
			)
		),
		'hours' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Hours.'
			)
		),
		'start_date' => array(
			'ruleNotLater' => array(
				'rule' => array('compareDate', 'end_date'),
				'message' => 'Start Date cannot be later than End Date'
			),
		)
	);
	
	public function compareDate($field = array(), $compareField = null) {
		$startDate = new DateTime(current($field));
		$endDate = new DateTime($this->data[$this->name][$compareField]);
		return $endDate > $startDate;
	}
	
	
	public function getAllList($type, $value){
		$options['conditions'] = array('StaffExtracurricular.'.$type=>$value);	
		$options['joins'] = array(
			array(
				'table' => 'extracurricular_types',
				'alias' => 'ExtracurricularType',
				'conditions' => array('ExtracurricularType.id = StaffExtracurricular.extracurricular_type_id')
			),
			array(
				'table' => 'school_years',
				'alias' => 'SchoolYears',
				'conditions' => array('SchoolYears.id = StaffExtracurricular.school_year_id')
			)
		);
		$options['fields'] = array('StaffExtracurricular.*', 'ExtracurricularType.name', 'SchoolYears.name', 'ModifiedUser.*', 'CreatedUser.*');
		
		$data = $this->find('all', $options);
		
		return $data;
	}
	
	public function autocomplete($search) {
		$search = sprintf('%%%s%%', $search);
		$data = $this->find('list', array(
			'recursive' => -1,
			'fields' => array('StaffExtracurricular.id', 'StaffExtracurricular.name'),
			'conditions' => array(
				'OR' => array(
					'StaffExtracurricular.name LIKE' => $search,
				)
			),
			'order' => array('StaffExtracurricular.name'),
			'group' => array('StaffExtracurricular.name')
		));
		return $data;
	}
}
?>
