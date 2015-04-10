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
	public $actsAs = array(
		'Excel',
		'ControllerAction2'
	);
   
	public $belongsTo = array(
		'InstitutionSite',
		'Staff.StaffPositionTitle',
		'Staff.StaffPositionGrade',
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

	/* Excel Behaviour */
	public function excelGetFieldLookup() {
		$alias = $this->alias;
		$lookup = array(
			"$alias.status" => array(0 => 'Inactive', 1 => 'Active'),
			"$alias.type" => array(0 => 'Non-Teaching', 1 => 'Teaching')
		);
		return $lookup;
	}
	public function excelGetOrder() {
		$order = array('InstitutionSitePosition.position_no');
		return $order;
	}
	/* End Excel Behaviour */
	
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
			$staffOptions = $this->StaffPositionTitle->getList(array('listOnly'=>true));
			foreach ($data as $obj) {
				$posInfo = $obj['InstitutionSitePosition'];
				$list[$posInfo['id']] = sprintf('%s - %s', 
					$posInfo['position_no'], 
					$staffOptions[$posInfo['staff_position_title_id']]);
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
			$this->fields['staff_position_title_id']['options'] = $this->StaffPositionTitle->getList();
			$this->fields['staff_position_grade_id']['options'] = $this->StaffPositionGrade->getList();
		} else {
			$this->fields['staff_position_title_id']['options'] = $this->StaffPositionTitle->getList();
			$this->fields['staff_position_grade_id']['options'] = $this->StaffPositionGrade->getList();
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
				'SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.third_name', 'SecurityUser.last_name',
				'InstitutionSiteStaff.id', 'InstitutionSiteStaff.start_date', 'InstitutionSiteStaff.end_date',
				'InstitutionSiteStaff.FTE', 'StaffStatus.name'
			);
			$this->InstitutionSiteStaff->recursive = 0;

			// $current = $this->InstitutionSiteStaff->findAllByInstitutionSitePositionIdAndEndDate($id, null, $fields, array('InstitutionSiteStaff.start_date'));

			$current = $this->InstitutionSiteStaff->find(
				'all',
				array(
					'contain' => array(
						'Staff' => array(
							'SecurityUser' => array('openemis_no','first_name','middle_name','third_name','last_name')
						),
						'StaffStatus'
					),
					// 'fields' => $fields,
					'conditions' => array(
						'InstitutionSiteStaff.institution_site_position_id' => $id,
						'InstitutionSiteStaff.end_date' => null
					),
					'order' => array('InstitutionSiteStaff.start_date')
				)
			);

			$totalCurrentFTE = '0.00';
			if (count($current)>0) {
				foreach ($current as $c) {
					$totalCurrentFTE = number_format((floatVal($totalCurrentFTE) + floatVal($c['InstitutionSiteStaff']['FTE'])),2);
				}
			}
			$past = $this->InstitutionSiteStaff->find('all', array(
				// 'fields' => $fields,
				'contain' => array(
						'Staff' => array(
							'SecurityUser' => array('openemis_no','first_name','middle_name','third_name','last_name')
						),
						'StaffStatus'
					),
				'conditions' => array(
					'InstitutionSiteStaff.institution_site_position_id' => $id,
					'InstitutionSiteStaff.end_date IS NOT NULL'
				),
				'order' => array('InstitutionSiteStaff.start_date')
			));
			$this->setVar(compact('current', 'totalCurrentFTE', 'past'));
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

	public function view($id=0) {
		$this->render = 'auto';
		if ($this->exists($id)) {
			$data = $this->find(
				'first',
				array(
					'conditions' => array(
						'InstitutionSitePosition.id' => $id
					)
				)
			);
			$this->Session->write($this->alias.'.id', $id);
			$this->setVar(compact('data'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => get_class($model)));
		}
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
			$staffFields['staff_status_id']['options'] = $this->InstitutionSiteStaff->StaffStatus->getList();
			$staffFields['FTE']['visible'] = true;
			$staffFields['FTE']['type'] = 'disabled';
			$staffFields['staff_name']['type'] = 'disabled';
			$staffFields['staff_name']['visible'] = true;
			// pass the whole staff object to view so that we can use ModelHelper to show the name by its getName() 
			$staffFields['staff_name']['value'] = $staff['Staff'];
			
			try {
				$date = new DateTime($startDate);
			} catch (Exception $e) {
			    return 'The Start Date Is Not In A Proper Format.';
			    exit(1);
			}
			$date->add(new DateInterval('P1D')); // plus 1 day
			$staffFields['end_date']['attr'] = array(
				'startDate' => $date->format('d-m-Y'),
				'data-date' => empty($endDate) ? '' : $date->format('d-m-Y')
			);
			
			// customise field inputs order to follow Staff/Position/edit UI
			$omitChangeOrder = array('FTE','staff_name','start_date','end_date');
			$staffFields['FTE']['order'] = 4;
			$staffFields['start_date']['order'] = 5;
			$staffFields['end_date']['order'] = 6;
			$order = 0;
			$staffFields['staff_name']['order'] = $order;
			// adjust field inputs order
			foreach ($staffFields as $key=>$value) {
				$order++;
				if (!in_array($key, $omitChangeOrder)) {
					foreach ($omitChangeOrder as $oco) {
						if ($order==$staffFields[$oco]['order']) {
							$order++;
						}
					}
					$staffFields[$key]['order'] = $order;
				}
			}
			// re-order fields array according to their 'order' values//
			// this code only works on PHP5.3 and above
			uasort($staffFields, function($a, $b) {
			    return $a['order'] - $b['order'];
			});

			if ($this->request->is(array('post', 'put'))) {
				$data = $this->request->data;
				//$data['InstitutionSiteStaff']['FTE'] = !empty($data['InstitutionSiteStaff']['FTE']) ? ($data['InstitutionSiteStaff']['FTE'] / 100) : NULL;
				$data['InstitutionSiteStaff']['institution_site_position_id'] = $id;
				
				$this->InstitutionSiteStaff->validator()->remove('search');
				$this->InstitutionSiteStaff->validator()->remove('FTE');
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
