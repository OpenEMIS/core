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

class Position extends AppModel {
	public $useTable = 'institution_site_staff';
	
	public $actsAs = array(
		'ControllerAction2',
		'DatePicker' => array('start_date', 'end_date'),
		'Year' => array('start_date' => 'start_year', 'end_date' => 'end_year')
	);
	
	public $belongsTo = array(
		'Staff.Staff',
		'StaffType' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'staff_type_id'
		),
		'StaffStatus' => array(
			'className' => 'FieldOptionValue',
			'foreignKey' => 'staff_status_id'
		),
		'InstitutionSitePosition',
		'InstitutionSite',
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
	
	public function beforeAction() {
		parent::beforeAction();
		$staffId = $this->Session->read('Staff.id');
		if (!is_null($staffId)) {
			$this->Navigation->addCrumb('Positions');
			$institutionSiteId = $this->Session->read('InstitutionSite.id');
			
			$this->fields['institution_site_id']['labelKey'] = 'InstitutionSite';
			$this->fields['institution_site_id']['dataModel'] = 'InstitutionSite';
			$this->fields['institution_site_id']['dataField'] = 'name';
			$this->setFieldOrder('institution_site_id', 1);
			$this->setFieldOrder('institution_site_position_id', 2);
			$this->fields['staff_id']['type'] = 'hidden';
			$this->fields['staff_status_id']['type'] = 'select';
			$this->fields['staff_status_id']['options'] = $this->StaffStatus->getList();
			$this->fields['staff_status_id']['labelKey'] = 'InstitutionSiteStaff';
			
			$this->fields['staff_type_id']['type'] = 'select';
			$this->fields['staff_type_id']['options'] = $this->StaffType->getList();
			$this->fields['staff_type_id']['labelKey'] = 'InstitutionSiteStaff';
			
			$this->fields['start_year']['visible'] = false;
			$this->fields['end_year']['visible'] = false;
			
			if ($this->action == 'edit') {
				$this->fields['institution_site_id']['type'] = 'disabled';
			}
			
		} else {
			return $this->redirect(array('plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'index'));
		}
	}
	
	public function afterAction() {
		if ($this->action == 'view') {
			$data = $this->controller->viewVars['data'];
			$titleId = $data['InstitutionSitePosition']['staff_position_title_id'];
			$name = $this->InstitutionSitePosition->StaffPositionTitle->field('name', $titleId);
			
			$this->controller->viewVars['data']['Position']['institution_site_position_id'] = $name;
			$this->fields['institution_site_position_id']['value'] = $name;
		} else if ($this->action == 'edit') {
			$data = $this->request->data;
			$titleId = $data['InstitutionSitePosition']['staff_position_title_id'];
			$name = $this->InstitutionSitePosition->StaffPositionTitle->field('name', $titleId);
			$this->fields['institution_site_position_id']['type'] = 'disabled';
			$this->fields['institution_site_position_id']['value'] = $name;
			
			$positionId = $this->request->data['Position']['id'];
			$startDate = $this->request->data['Position']['start_date'];
			$this->fields['FTE']['type'] = 'select';
			$this->fields['FTE']['options'] = $this->Staff->InstitutionSiteStaff->getFTEOptions($positionId, array('startDate' => $startDate));
		}
		parent::afterAction();
	}
	
	public function index() {
		$alias = $this->alias;
		$staffId = $this->Session->read('Staff.id');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$conditions = array("$alias.staff_id" => $staffId);
		
		if (!is_null($institutionSiteId)) {
			$conditions["$alias.institution_site_id"] = $institutionSiteId;
		}
		
		$this->contain(array(
			'InstitutionSite',
			'StaffStatus',
			'InstitutionSitePosition' => array(
				'fields' => array('InstitutionSitePosition.staff_position_title_id'),
				'StaffPositionTitle' => array('fields' => array('StaffPositionTitle.name'))
			)
		));
		
		$data = $this->find('all', array(
			'fields' => array(
				'Position.id', 'Position.start_date', 'Position.end_date', 
				'InstitutionSite.name', 'StaffStatus.name', 'Position.institution_site_position_id'
			),
			'conditions' => $conditions,
			'order' => array("$alias.start_date DESC")
		));
		$this->setVar('data', $data);
	}
}
