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
				'DISTINCT SecurityUser.openemis_no',
				'SecurityUser.first_name', 'SecurityUser.last_name',
				'SecurityUser.middle_name', 'SecurityUser.third_name'
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
			'order' => array('SecurityUser.first_name ASC')
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
					'Staff.id', 'SecurityUser.first_name', 'SecurityUser.middle_name', 'SecurityUser.last_name', 'SecurityUser.third_name', 'SecurityUser.openemis_no',
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
						'table' => 'academic_periods',
						'alias' => 'AcademicPeriod',
						'conditions' => array('AcademicPeriod.id = InstitutionSiteSection.academic_period_id')
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
							'InstitutionSiteStaff.start_year >= ' => 'AcademicPeriod.start_year',
							'InstitutionSiteStaff.end_year >= ' => 'AcademicPeriod.start_year'
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
	public function getStaffs($sectionId, $mode = 'all') {
		$data = $this->find('all', array(
			'recursive' => 0,
			'fields' => array(
				'Staff.id', 'SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.last_name', 'SecurityUser.middle_name', 'SecurityUser.third_name'
			),
			'conditions' => array('InstitutionSiteSectionStaff.institution_site_section_id' => $sectionId),
			'order' => array('SecurityUser.first_name')
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
				'Staff.id', 'SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.last_name', 'SecurityUser.middle_name', 'SecurityUser.third_name'
			),
			'joins' => array(
				array(
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => array(
						'InstitutionSiteSection.institution_site_id = ' . $institutionSiteId,
						'InstitutionSiteSection.id = InstitutionSiteSectionStaff.institution_site_section_id'
					)
				),
				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array('Staff.id = InstitutionSiteSectionStaff.staff_id')
				),
			),
			'order' => array('SecurityUser.first_name')
		));
		$list = array();
		foreach ($data as $obj) {
			$id = $obj['Staff']['id'];
			$teacherName = $obj['SecurityUser']['first_name'] . ' ' . $obj['SecurityUser']['middle_name'] . ' ' . $obj['SecurityUser']['third_name'] . ' ' . $obj['SecurityUser']['last_name'];
			$list[$id] = ModelHelper::getName($obj['Staff']);
		}
		return $list;
	}

	public function getStaffsInSectionAcademicPeriod($sectionId, $academicPeriodId, $mode = 'all') {
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'Staff.id', 'SecurityUser.openemis_no', 'SecurityUser.first_name', 'SecurityUser.last_name', 'SecurityUser.middle_name', 'SecurityUser.third_name'
			),
			'conditions' => array('InstitutionSiteSectionStaff.institution_site_section_id' => $sectionId, 'AcademicPeriod.id' => $academicPeriodId),
			'joins' => array(
				array(
					'table' => 'institution_site_sections',
					'alias' => 'InstitutionSiteSection',
					'conditions' => array(
						'InstitutionSiteSection.id = InstitutionSiteSectionStaff.institution_site_section_id'
					)
				),
				array(
					'table' => 'academic_periods',
					'alias' => 'AcademicPeriod',
					'conditions' => array('AcademicPeriod.id = InstitutionSiteSection.academic_period_id')
				),
				array(
					'table' => 'institution_site_staff',
					'alias' => 'InstitutionSiteStaff',
					'conditions' => array('InstitutionSiteStaff.staff_id = InstitutionSiteSectionStaff.staff_id',
						'OR' => array(
							'InstitutionSiteStaff.end_year >= AcademicPeriod.end_year', 'InstitutionSiteStaff.end_year is null'
						)
					)
				),
				array(
					'table' => 'staff',
					'alias' => 'Staff',
					'conditions' => array('InstitutionSiteStaff.staff_id = Staff.id')
				),
				array(
					'table' => 'security_users',
					'alias' => 'SecurityUser',
					'conditions' => array('Staff.security_user_id = SecurityUser.id')
				)
			),
			'order' => array('SecurityUser.first_name')
		));
		$this->bindModel(array('belongsTo' => array('InstitutionSiteSection')));
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
