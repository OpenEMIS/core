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

class EducationFieldOfStudy extends AppModel {
	public $actsAs = array('ControllerAction2', 'Reorder');
	public $belongsTo = array(
		'EducationProgrammeOrientation',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id'
		)
	);
	public $hasMany = array('EducationProgramme');
	
	public $validate = array(
		'name' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a name'
			)
		),
		'education_programme_orientation_id' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select the programme orientation'
			)
		)
	);
	
	public $virtualFields = array(
		'fullname' => "CONCAT((SELECT name FROM `education_programme_orientations` WHERE id = EducationFieldOfStudy.education_programme_orientation_id), ' - ', EducationFieldOfStudy.name)"
	);
	
	public function beforeAction() {
		parent::beforeAction();
		$this->Navigation->addCrumb('Field of Study');
		
		$this->fields['order']['visible'] = false;
		$this->fields['education_programme_orientation_id']['type'] = 'select';
		$this->fields['education_programme_orientation_id']['options'] = $this->EducationProgrammeOrientation->find('list', array('order' => 'order'));
		if ($this->action == 'add') {
			$this->fields['order']['type'] = 'hidden';
			$this->fields['order']['visible'] = true;
			$this->fields['order']['value'] = 0;
			$this->fields['visible']['type'] = 'hidden';
			$this->fields['visible']['value'] = 1;
		} else {
			$this->fields['visible']['type'] = 'select';
			$this->fields['visible']['options'] = $this->controller->Option->get('yesno');
		}
		
		$this->setVar('selectedAction', $this->alias);
	}
	
	public function index() {
		$data = $this->find('all', array('order' => $this->alias.'.order'));
		$this->setVar(compact('data'));
	}
	
	public function getList($visible=NULL) {
		$conditions = array();
		if(!is_null($visible)) {
			$conditions[$this->alias.'.visible'] = $visible;
		}
		$list = $this->find('all', array(
			'conditions' => $conditions,
			'order' => array('EducationProgrammeOrientation.order', $this->alias.'.order')
		));
		$data = array();
		foreach($list as $obj) {
			$fieldOfStudy = $obj['EducationFieldOfStudy'];
			$orientation = $obj['EducationProgrammeOrientation'];
			$data[$fieldOfStudy['id']] = __($orientation['name']) . ' - ' . $fieldOfStudy['name'];
		}
		return $data;
	}
}
