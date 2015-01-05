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

class InstitutionSitePosition extends AppModel {
	public $actsAs = array('ControllerAction2');
   
	public $belongsTo = array(
		'Staff.StaffPositionTitle',
		'Staff.StaffPositionGrade',
		'ModifiedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'modified_user_id'
		),
		'CreatedUser' => array(
			'className' => 'SecurityUser',
			'foreignKey' => 'created_user_id'
		)
	);
	
	public $hasMany = array(
		'InstitutionSiteStaff' => array(
			'dependent' => true // for cascade deletes
		)
	);
	
	public $validate = array(
		'position_no' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a valid Number.'
			)
		),
		'staff_position_title_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Title.'
			)
		),
		'staff_position_grade_id' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a valid Grade.'
			)
		)
	);
	
	// Used by InstitutionSiteStaff.add
	public function getInstitutionSitePositionList($institutionId = false, $status = false) {
		$options['recursive'] = -1;
		$conditions = array();
		if ($institutionId !== false) {
			$conditions['institution_site_id'] = $institutionId;
		}
		if ($status !== false) {
			$conditions['status'] = $status;
		}
		if (!empty($conditions)) {
			$options['conditions'] = $conditions;
		}
		$data = $this->find('all', $options);
		$list = array();
		if (!empty($data)) {
			$staffOptions = $this->StaffPositionTitle->getList(1);
			foreach ($data as $obj) {
				$posInfo = $obj['InstitutionSitePosition'];
				$list[$posInfo['id']] = sprintf('%s - %s', $posInfo['position_no'], $staffOptions[$posInfo['staff_position_title_id']]);
			}
		}

		return $list;
	}
	
	public function beforeAction() {
		parent::beforeAction();
		$this->Navigation->addCrumb('Positions');
		$this->setVar(compact('contentHeader'));
		
		$this->fields['institution_site_id']['type'] = 'hidden';
		$this->fields['institution_site_id']['value'] = $this->Session->read('InstitutionSite.id');
		$this->fields['staff_position_title_id']['type'] = 'select';
		$this->fields['staff_position_grade_id']['type'] = 'select';
		$this->fields['type']['type'] = 'select';
		$this->fields['type']['options'] = $this->controller->Option->get('staffTypes');
		$this->fields['status']['type'] = 'select';
		$this->fields['status']['options'] = $this->controller->Option->get('status');
		
		if ($this->action == 'add') {
			$this->fields['staff_position_title_id']['options'] = $this->StaffPositionTitle->getList(1);
			$this->fields['staff_position_grade_id']['options'] = $this->StaffPositionGrade->getList(1);
		} else {
			$this->fields['staff_position_title_id']['options'] = $this->StaffPositionTitle->getList(1);
			$this->fields['staff_position_grade_id']['options'] = $this->StaffPositionGrade->getList(1);
		}
		
		if ($this->action == 'view') {
			$this->fields['current'] = array(
				'type' => 'element',
				'element' => '../InstitutionSites/InstitutionSitePosition/current',
				'order' => 10,
				'override' => true,
				'visible' => true
			);
			$this->fields['past'] = array(
				'type' => 'element',
				'element' => '../InstitutionSites/InstitutionSitePosition/past',
				'order' => 11,
				'override' => true,
				'visible' => true
			);
		}
		
		$this->setFieldOrder('staff_position_title_id', 2);
		$this->setFieldOrder('staff_position_grade_id', 3);
		$this->setFieldOrder('type', 4);
		$this->setFieldOrder('status', 5);
	}
	
	public function afterAction() {
		if ($this->action == 'view') {
			$id = $this->controller->viewVars['data'][$this->alias]['id'];
			$fields = array(
				'Staff.identification_no', 'Staff.first_name', 'Staff.middle_name', 'Staff.last_name',
				'InstitutionSiteStaff.id', 'InstitutionSiteStaff.start_date', 'InstitutionSiteStaff.end_date',
				'InstitutionSiteStaff.FTE', 'StaffStatus.name'
			);
			$this->InstitutionSiteStaff->recursive = 0;
			$current = $this->InstitutionSiteStaff->findAllByInstitutionSitePositionIdAndEndDate($id, null, $fields, array('InstitutionSiteStaff.start_date'));
			$past = $this->InstitutionSiteStaff->find('all', array(
				'fields' => $fields,
				'conditions' => array(
					'InstitutionSiteStaff.institution_site_position_id' => $id,
					'InstitutionSiteStaff.end_date IS NOT NULL'
				),
				'order' => array('InstitutionSiteStaff.start_date')
			));
			$this->setVar(compact('current', 'past'));
		}
		parent::afterAction();
	}
	
	public function index() {
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$this->unbindModel(array('belongsTo' => array('ModifiedUser', 'CreatedUser')));	
		$this->recursive = 0;
		$data = $this->findAllByInstitutionSiteId($institutionSiteId);
		$this->setVar(compact('data'));
	}
	
	public function staffEdit($staffId=0) {
		$id = $this->Session->read($this->alias . '.id');
		
		if ($this->InstitutionSiteStaff->exists($staffId)) {
			$this->recursive = 0;
			$position = $this->findById($id);
			$this->InstitutionSiteStaff->recursive = 0;
			$staff = $this->InstitutionSiteStaff->findById($staffId);
			
			$startDate = $staff['InstitutionSiteStaff']['start_date'];
			$endDate = $staff['InstitutionSiteStaff']['end_date'];
			$staffFields = $this->InstitutionSiteStaff->getFields();
			$staffFields['institution_site_id']['visible'] = false;
			$staffFields['institution_site_position_id']['type'] = 'disabled';
			$staffFields['institution_site_position_id']['value'] = $position['StaffPositionTitle']['name'];
			$staffFields['start_date']['type'] = 'disabled';
			$staffFields['start_date']['value'] = $startDate;
			$staffFields['staff_status_id']['type'] = 'select';
			$staffFields['staff_status_id']['options'] = $this->InstitutionSiteStaff->StaffStatus->getList(1);
			$staffFields['FTE']['visible'] = false;
			
			$date = new DateTime($startDate);
			$date->add(new DateInterval('P1D')); // plus 1 day
			$staffFields['end_date']['attr'] = array(
				'startDate' => $date->format('d-m-Y'),
				'data-date' => empty($endDate) ? '' : $date->format('d-m-Y')
			);
			
			if ($this->request->is(array('post', 'put'))) {
				$data = $this->request->data;
				//$data['InstitutionSiteStaff']['FTE'] = !empty($data['InstitutionSiteStaff']['FTE']) ? ($data['InstitutionSiteStaff']['FTE'] / 100) : NULL;
				$data['InstitutionSiteStaff']['institution_site_position_id'] = $id;
				
				$this->InstitutionSiteStaff->validator()->remove('search');
				$this->InstitutionSiteStaff->validator()->remove('FTE');
				//pr($data);
				if ($this->InstitutionSiteStaff->save($data)) {
					$this->Message->alert('general.edit.success');
					return $this->redirect(array('action' => $this->alias, 'view', $id));
				} else {
					$this->log($this->InstitutionSiteStaff->validationErrors, 'debug');
					$this->Message->alert('general.edit.failed');
				}
			} else {
				$this->request->data = $staff;
			}
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => $this->alias, 'view', $id));
		}
		$this->setVar(compact('id', 'staffId', 'staffFields'));
	}
	
	public function staffDelete($staffId) {
		$id = $this->Session->read($this->alias . '.id');
		if($this->InstitutionSiteStaff->delete($staffId)) {
			$this->Message->alert('general.delete.success');
		} else {
			$this->Message->alert('general.delete.failed');
		}
		return $this->redirect(array('action' => $this->alias, 'view', $id));
	}
}
