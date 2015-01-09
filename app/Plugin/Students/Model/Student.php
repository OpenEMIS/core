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

class Student extends StudentsAppModel {
	public $actsAs = array(
		'Excel',
		'Search',
		'TrackHistory' => array('historyTable' => 'Students.StudentHistory'),
		'CascadeDelete' => array(
			'cascade' => array(
				'Students.StudentAttachment',
				'Students.StudentCustomValue'
			)
		),
		'CustomReport' => array(
			'_default' => array('photo_name', 'photo_content')
		),
		'DatePicker' => array('date_of_birth'),
		'FileUpload' => array(
			array(
				'name' => 'photo_name',
				'content' => 'photo_content',
				'size' => '1MB',
				'allowEmpty' => true
			)
		)
	);

	public $belongsTo = array(
		'AddressArea' => array(
			'className' => 'Area',
			'foreignKey' => 'address_area_id'
		),
		'BirthplaceArea' => array(
			'className' => 'Area',
			'foreignKey' => 'birthplace_area_id'
		)
	);
	
	public $hasMany = array('InstitutionSiteStudentFee');
	
	public $validate = array(
		'first_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid First Name'
			)
		),
		'last_name' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Last Name'
			)
		),
		'identification_no' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid OpenEMIS ID'
			),
			'ruleUnique' => array(
        		'rule' => 'isUnique',
        		'required' => true,
        		'message' => 'Please enter a unique OpenEMIS ID'
		    )
		),
		'gender' => array(
			'ruleRequired' => array(
				'rule' => array('comparison', 'not equal', '0'),
				'required' => true,
				'message' => 'Please select a Gender'
			)
		),
		'address' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Address'
			)
		),
		'date_of_birth' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Date of Birth'
			),
			'ruleCompare' => array(
				'rule' => array('comparison', 'NOT EQUAL', '0000-00-00'),
				'required' => true,
				'message' => 'Please select a Date of Birth'
			),
			'ruleCompare' => array(
				'rule' => 'compareBirthDate',
				'message' => 'Date of Birth cannot be future date'
			)
		),
		'email' => array(
			'ruleRequired' => array(
				'rule' => 'email',
				'allowEmpty' => true,
				'message' => 'Please enter a valid Email'
			)
		)
	);

	/* Excel Behaviour */
	public function excelGetConditions() {
		$id = CakeSession::read('Student.id');
		$conditions = array('Student.id' => $id);
		return $conditions;
	}
	public function excelGetModels() {
		$models = array(
			array('model' => $this),
			array('model' => 'Students.StudentContact'),
			array('model' => 'Students.StudentIdentity'),
			array('model' => 'Students.StudentNationality'),
			array('model' => 'Students.StudentLanguage'),
			array('model' => 'Students.StudentComment'),
			array('model' => 'Students.StudentSpecialNeed'),
			array('model' => 'Students.StudentAward')
		);
		return $models;
	}
	/* End Excel Behaviour */

	public function compareBirthDate() {
		if(!empty($this->data[$this->alias]['date_of_birth'])) {
			$birthDate = $this->data[$this->alias]['date_of_birth'];
			$birthTimestamp = strtotime($birthDate);
			$todayDate=date("Y-m-d");
			$todayTimestamp = strtotime($todayDate);

			return $todayTimestamp >= $birthTimestamp;
		}
		return true;
	}
	
	public function paginate($conditions, $fields, $order, $limit, $page = 1, $recursive = null, $extra = array()) {
		return $this->getPaginate($conditions, $fields, $order, $limit, $page, $recursive, $extra);
	}
	
	public function paginateCount($conditions = null, $recursive = 0, $extra = array()) {
		return $this->getPaginateCount($conditions, $recursive, $extra);
	}
	
	// used by InstitutionSiteStudent
	public function autocomplete($search) {
		$search = '%' . $search . '%';
		
		$conditions = array(
			'OR' => array(
				$this->alias . '.identification_no LIKE' => $search,
				$this->alias . '.first_name LIKE' => $search,
				$this->alias . '.middle_name LIKE' => $search,
				$this->alias . '.last_name LIKE' => $search
			)
		);
		$options = array(
			'recursive' => -1,
			'conditions' => $conditions,
			'order' => array($this->alias . '.first_name')
		);
		
		$options['fields'] = array('id', 'first_name', 'last_name', 'middle_name', 'gender', 'identification_no', 'date_of_birth');
		$data = $this->find('all', $options);
		
		return $data;
	}
}
?>
