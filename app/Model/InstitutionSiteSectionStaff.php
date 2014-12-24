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

class InstitutionSiteSectionStaff extends AppModel {
	public $useTable = 'institution_site_section_staff';
	public $actsAs = array('ControllerAction2');
	
	public $belongsTo = array(
		'Staff.Staff',
		'InstitutionSiteSection'
	);
	
	public function beforeAction() {
		parent::beforeAction();
		$id = $this->Session->read('InstitutionSiteSection.id');
		
		if($this->InstitutionSiteSection->exists($id)) {
			$header = $this->InstitutionSiteSection->field('name', array('id' => $id));
			$this->Navigation->addCrumb($header);
			$this->setVar('header', $header);
			$this->setVar('selectedAction', $this->alias . '/index');
			$currentSectionId = $this->Session->read('InstitutionSiteSection.id');
			$this->setVar('actionOptions', $this->InstitutionSiteSection->getSectionActions($currentSectionId));
		} else {
			$this->Message->alert('general.notExists');
			return $this->redirect(array('action' => $this->alias, 'index'));
		}
	}
	
	public function index() {
		$id = $this->Session->read('InstitutionSiteSection.id');
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'DISTINCT Staff.identification_no',
				'Staff.first_name', 'Staff.last_name'
			),
			'joins' => array(
				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array('InstitutionSiteSectionStaff.staff_id = Staff.id')
				)
			),
			'conditions' => array(
				'InstitutionSiteSectionStaff.institution_site_section_id' => $id,
				'InstitutionSiteSectionStaff.status' => 1
			),
			'order' => array('Staff.first_name ASC')
		));
		if(empty($data)) {
			$this->Message->alert('general.noData');
		}
		$this->setVar(compact('data'));
	}
	
	public function edit() {
		$id = $this->Session->read('InstitutionSiteSection.id');
		if($this->request->is('get')) {
			$data = $this->Staff->find('all', array(
				'recursive' => 0,
				'fields' => array(
					'Staff.id', 'Staff.first_name', 'Staff.middle_name', 'Staff.last_name', 'Staff.identification_no',
					'InstitutionSiteSectionStaff.id', 'InstitutionSiteSectionStaff.status', 'InstitutionSiteSection.id'
				),
				'joins' => array(
					array(
						'table' => 'institution_site_staff',
						'alias' => 'InstitutionSiteStaff',
						'conditions' => array('InstitutionSiteStaff.staff_id = Staff.id')
					),
					array(
						'table' => 'institution_site_sections',
						'alias' => 'InstitutionSiteSection',
						'conditions' => array(
							'InstitutionSiteSection.institution_site_id = InstitutionSiteStaff.institution_site_id',
							'InstitutionSiteSection.id = ' . $id
						)
					),
					array(
						'table' => 'school_years',
						'alias' => 'SchoolYear',
						'conditions' => array('SchoolYear.id = InstitutionSiteSection.school_year_id')
					),
					array(
						'table' => 'institution_site_section_staff',
						'alias' => $this->alias,
						'type' => 'LEFT',
						'conditions' => array(
							$this->alias . '.staff_id = InstitutionSiteStaff.staff_id',
							$this->alias . '.institution_site_section_id = InstitutionSiteSection.id'
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
				'group' => array('Staff.id'),
				'order' => array($this->alias.'.status DESC')
			));
			if(empty($data)) {
				$this->Message->alert('general.noData');
			}
			$this->setVar(compact('data'));
		} else {
			$data = $this->request->data;
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
			$this->Message->alert('general.edit.success');
			return $this->redirect(array('action' => $this->_action));
		}
	}
	
	// used by InstitutionSite.classesEdit/classesView
	public function getStaffs($classId, $mode = 'all') {
		$data = $this->find('all', array(
			'recursive' => 0,
			'fields' => array(
				'Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.last_name'
			),
			'conditions' => array('InstitutionSiteClassStaff.institution_site_class_id' => $classId),
			'order' => array('Staff.first_name')
		));

		if ($mode == 'list') {
			$list = array();
			foreach ($data as $obj) {
				$id = $obj['Staff']['id'];
				$list[$id] = sprintf('%s %s', $obj['Staff']['first_name'], $obj['Staff']['last_name']);
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
				'Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.last_name'
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
			$teacherName = $obj['Staff']['first_name'] . ' ' . $obj['Staff']['last_name'];
			$list[$id] = sprintf('%s %s', $obj['Staff']['first_name'], $obj['Staff']['last_name']);
		}
		return $list;
	}

	public function getStaffsInClassYear($classId, $yearId, $mode = 'all') {
		$this->unbindModel(array('belongsTo' => array('InstitutionSiteClass')));
		$data = $this->find('all', array(
			'fields' => array(
				'Staff.id', 'Staff.identification_no', 'Staff.first_name', 'Staff.last_name'
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
				$list[$id] = sprintf('%s %s', $obj['Staff']['first_name'], $obj['Staff']['last_name']);
			}
			return $list;
		} else {
			return $data;
		}
	}

}
