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

class InstitutionSiteClassStaff extends AppModel {
	public $useTable = 'institution_site_class_staff';
	public $actsAs = array('ControllerAction');
	
	public $belongsTo = array(
		'Staff.Staff',
		'InstitutionSiteClass'
	);
	
	public $_action = 'classesStaff';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$id = $controller->Session->read('InstitutionSiteClass.id');
		
		if($this->InstitutionSiteClass->exists($id)) {
			$header = $this->InstitutionSiteClass->field('name', array('id' => $id));
			$controller->Navigation->addCrumb($header);
			$controller->set('header', $header);
			$controller->set('_action', $this->_action);
			$controller->set('selectedAction', $this->_action);
			$controller->set('actionOptions', $this->InstitutionSiteClass->getClassActions($controller));
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => $this->InstitutionSiteClass->_action));
		}
	}
	
	public function classesStaff($controller, $params) {
		$id = $controller->Session->read('InstitutionSiteClass.id');
		$data = $this->findAllByInstitutionSiteClassIdAndStatus($id, 1, array(), array('Staff.first_name ASC'));
		if(empty($data)) {
			$controller->Message->alert('general.noData');
		}
		$controller->set(compact('data'));
	}
	
	public function classesStaffEdit($controller, $params) {
		$id = $controller->Session->read('InstitutionSiteClass.id');
		if($controller->request->is('get')) {
			$data = $this->Staff->find('all', array(
				'recursive' => 0,
				'fields' => array(
					'Staff.id', 'Staff.first_name', 'Staff.middle_name', 'Staff.third_name', 'Staff.last_name', 'Staff.identification_no',
					'InstitutionSiteClassStaff.id', 'InstitutionSiteClassStaff.status', 'InstitutionSiteClass.id'
				),
				'joins' => array(
					array(
						'table' => 'institution_site_staff',
						'alias' => 'InstitutionSiteStaff',
						'conditions' => array('InstitutionSiteStaff.staff_id = Staff.id')
					),
					array(
						'table' => 'institution_site_classes',
						'alias' => 'InstitutionSiteClass',
						'conditions' => array(
							'InstitutionSiteClass.institution_site_id = InstitutionSiteStaff.institution_site_id',
							'InstitutionSiteClass.id = ' . $id
						)
					),
					array(
						'table' => 'school_years',
						'alias' => 'SchoolYear',
						'conditions' => array('SchoolYear.id = InstitutionSiteClass.school_year_id')
					),
					array(
						'table' => 'institution_site_class_staff',
						'alias' => $this->alias,
						'type' => 'LEFT',
						'conditions' => array(
							$this->alias . '.staff_id = InstitutionSiteStaff.staff_id',
							$this->alias . '.institution_site_class_id = InstitutionSiteClass.id'
						)
					)
				),
				'conditions' => array( // the class school year must be within the staff start and end date
					'OR' => array(
						'InstitutionSiteStaff.end_date IS NULL',
						'AND' => array(
							'InstitutionSiteStaff.start_year >= ' => 'SchoolYear.start_year',
							'InstitutionSiteStaff.end_year >= ' => 'SchoolYear.start_year'
						)
					)
				),
				'order' => array($this->alias.'.status DESC')
			));
			if(empty($data)) {
				$controller->Message->alert('general.noData');
			}
			$controller->set(compact('data'));
		} else {
			$data = $controller->request->data;
			if(isset($data[$this->alias])) {
				foreach($data[$this->alias] as $i => $obj) {
					if(empty($obj['id']) && $obj['status'] == 0) {
						unset($data[$this->alias][$i]);
					}
				}
				if(!empty($data[$this->alias])) {
					$this->saveAll($data[$this->alias]);
				}
			}
			$controller->Message->alert('general.edit.success');
			return $controller->redirect(array('action' => $this->_action));
		}
	}
	
	// used by InstitutionSite.classesEdit/classesView
	public function getStaffs($classId, $mode = 'all') {
		$data = $this->find('all', array(
			'recursive' => 0,
			'fields' => array(
				'Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.middle_name', 'Staff.third_name', 'Staff.last_name'
			),
			'conditions' => array('InstitutionSiteClassStaff.institution_site_class_id' => $classId),
			'order' => array('Staff.first_name')
		));

		if ($mode == 'list') {
			$list = array();
			foreach ($data as $obj) {
				$id = $obj['Staff']['id'];
				$list[$id] = ModelHelper::getName($obj['Staff']);
			}
			return $list;
		} else {
			return $data;
		}
	}

	public function getStaffsByInstitutionSiteId($institutionSiteId) {
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.middle_name', 'Staff.third_name', 'Staff.last_name'
			),
			'joins' => array(
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteClass.institution_site_id = ' . $institutionSiteId,
						'InstitutionSiteClass.id = InstitutionSiteClassStaff.institution_site_class_id'
					)
				),
				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array('Staff.id = InstitutionSiteClassStaff.staff_id')
				),
			),
			'order' => array('Staff.first_name')
		));
		$list = array();
		foreach ($data as $obj) {
			$id = $obj['Staff']['id'];
			$list[$id] = ModelHelper::getName($obj['Staff']);
		}
		return $list;
	}

	public function getStaffsInClassYear($classId, $yearId, $mode = 'all') {
		$this->unbindModel(array('belongsTo' => array('InstitutionSiteClass')));
		$data = $this->find('all', array(
			'fields' => array(
				'Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.middle_name', 'Staff.third_name', 'Staff.last_name'
			),
			'conditions' => array('InstitutionSiteClassStaff.institution_site_class_id' => $classId, 'SchoolYear.id' => $yearId),
			'joins' => array(
				array(
					'table' => 'institution_site_classes',
					'alias' => 'InstitutionSiteClass',
					'conditions' => array(
						'InstitutionSiteClass.id = InstitutionSiteClassStaff.institution_site_class_id'
					)
				),
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array('SchoolYear.id = InstitutionSiteClass.school_year_id')
				),
				array(
					'table' => 'institution_site_staff',
					'alias' => 'InstitutionSiteStaff',
					'conditions' => array('InstitutionSiteStaff.staff_id = InstitutionSiteClassStaff.staff_id',
						'OR' => array(
							'InstitutionSiteStaff.end_year >= SchoolYear.end_year', 'InstitutionSiteStaff.end_year is null'
						)
					)
				)
			),
			'order' => array('Staff.first_name')
		));
		$this->bindModel(array('belongsTo' => array('InstitutionSiteClass')));
		if ($mode == 'list') {
			$list = array();
			foreach ($data as $obj) {
				$id = $obj['Staff']['id'];
				$list[$id] = ModelHelper::getName($obj['Staff']);
			}
			return $list;
		} else {
			return $data;
		}
	}

}
