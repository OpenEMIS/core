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

class StaffBehaviour extends StaffAppModel {
	public $useTable = 'staff_behaviours';
	
	public $actsAs = array(
		'Excel' => array('header' => array('Staff' => array('identification_no', 'first_name', 'last_name'))),
		'ControllerAction2', 
		'DatePicker' => array('date_of_behaviour'),
		'TimePicker' => array('time_of_behaviour' => array('format' => 'h:i a'))
	);
	
	public $belongsTo = array(
		'Staff.Staff',
		'Staff.StaffBehaviourCategory',
		'InstitutionSite',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'modified_user_id',
			'type' => 'LEFT'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'fields' => array('first_name', 'last_name'),
			'foreignKey' => 'created_user_id',
			'type' => 'LEFT'
		)
	);
	
	public $validate = array(
		'title' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please enter a valid title'
			)
		),
		'staff_behaviour_category_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'message' => 'Please select an category'
			)
		)
	);

	/* Excel Behaviour */
	public function excelGetConditions() {
		if (!empty($this->controller)) { // via ControllerAction
			$id = CakeSession::read('InstitutionSite.id');
			$conditions = array('InstitutionSite.id' => $id);
		} else {
			$id = CakeSession::read('Staff.id');
			$conditions = array('Staff.id' => $id);
		}
		return $conditions;
	}
	/* End Excel Behaviour */
	
	public function beforeAction() {
		parent::beforeAction();
		
		$this->fields['institution_site_id']['type'] = 'hidden';
		$this->fields['institution_site_id']['value'] = $this->Session->read('InstitutionSite.id');
		$this->fields['staff_action_category_id']['type'] = 'hidden';
		$this->fields['staff_action_category_id']['value'] = 0;
		$this->fields['staff_behaviour_category_id']['type'] = 'select';
		$this->fields['title']['labelKey'] = 'name';
		
		if ($this->action == 'add' || $this->action == 'edit' || $this->action == 'view') {
			if ($this->Session->check($this->alias.'.staffId')) {
				$staffId = $this->Session->read($this->alias.'.staffId');
				
				$this->Staff->contain();
				$obj = $this->Staff->findById($staffId);
				
				$this->fields['staff_name']['visible'] = true;
				$this->fields['staff_name']['type'] = 'disabled';
				$this->fields['staff_name']['value'] = ModelHelper::getName($obj['Staff']);
				$this->fields['staff_name']['order'] = 0;
				$this->setFieldOrder('staff_name', 0);
				
				$this->fields['staff_id']['type'] = 'hidden';
				$this->fields['staff_id']['value'] = $staffId;
			} else {
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => get_class($this), 'show'));
			}
		}
		
		$categoryOptions = array();
		if ($this->action = 'add' || $this->action = 'edit') {
			$categoryOptions = $this->StaffBehaviourCategory->getList();
		} else {
			$categoryOptions = $this->StaffBehaviourCategory->getList();
		}
		$this->fields['staff_behaviour_category_id']['options'] = $categoryOptions;
		$this->setFieldOrder('staff_behaviour_category_id', 1);
		$this->setFieldOrder('date_of_behaviour', 2);
		$this->setFieldOrder('time_of_behaviour', 3);
		
		$this->Navigation->addCrumb('Behaviour - Staff');
		$this->InstitutionSiteStaff = ClassRegistry::init('InstitutionSiteStaff');
	}
	
	public function show() {
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		
		$this->InstitutionSiteStaff->contain(array(
			'Staff' => array('fields' => array('Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.middle_name', 'Staff.third_name', 'Staff.last_name')),
			'StaffType' => array('fields' => array('StaffType.name')),
			'StaffStatus' => array('fields' => array('StaffStatus.name'))
		));
		
		$data = $this->InstitutionSiteStaff->findAllByInstitutionSiteId($institutionSiteId);
		
		if (empty($data)) {
			$this->Message->alert('general.noData');
		}
		$this->setVar(compact('data'));
	}
	
	public function index($staffId = 0) {
		if ($this->controller->name == 'InstitutionSites') {
			$institutionSiteId = $this->Session->read('InstitutionSite.id');
			
			if (empty($staffId)) {
				if ($this->Session->check($this->alias.'.staffId')) {
					$staffId = $this->Session->read($this->alias.'.staffId');
				} else {
					return $this->redirect(array('action' => get_class($this), 'show'));
				}
			}
			
			if ($this->Staff->exists($staffId)) {
				$this->Session->write($this->alias.'.staffId', $staffId);
				$this->contain(array(
					'StaffBehaviourCategory' => array('fields' => array('StaffBehaviourCategory.name'))
				));
				$this->Staff->contain();
				$staff = $this->Staff->findById($staffId);
				$data = $this->findAllByStaffIdAndInstitutionSiteId($staffId, $institutionSiteId, array(), array('StaffBehaviour.date_of_behaviour'));
				
				$this->setVar(compact('data', 'staff'));
			} else {
				$this->Message->alert('general.notExists');
				return $this->redirect(array('action' => get_class($this), 'show'));
			}
		} else {
			$staffId = $this->Session->read('Staff.id');
		
			$this->contain(array(
				'InstitutionSite' => array('fields' => array('InstitutionSite.name')), 
				'StaffBehaviourCategory' => array('fields' => array('StaffBehaviourCategory.name'))
			));
			$data = $this->findAllByStaffId($staffId, array(), array('StaffBehaviour.date_of_behaviour'));
			$this->setVar(compact('data'));
		}
	}
}
