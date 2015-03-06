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

class Programme extends AppModel {
	public $useTable = 'institution_site_students';
	
	public $actsAs = array(
		'Excel' => array('header' => array('Student' => array('identification_no', 'first_name', 'last_name'))),
		'DatePicker' => array('start_date', 'end_date'),
		'Year' => array('start_date' => 'start_year', 'end_date' => 'end_year')
	);
	
	public $belongsTo = array(
		'Students.Student',
		'Students.StudentStatus',
		'EducationProgramme',
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
		$studentId = $this->Session->read('Student.id');
		if (!is_null($studentId)) {
			$this->Navigation->addCrumb('Programmes');
			$institutionSiteId = $this->Session->read('InstitutionSite.id');
			
			$this->fields['institution_site_id']['labelKey'] = 'InstitutionSite';
			$this->fields['institution_site_id']['dataModel'] = 'InstitutionSite';
			$this->fields['institution_site_id']['dataField'] = 'name';
			$this->setFieldOrder('institution_site_id', 1);
			
			$this->fields['education_programme_id']['labelKey'] = 'InstitutionSiteStudent';
			$this->fields['education_programme_id']['dataModel'] = 'EducationProgramme';
			$this->fields['education_programme_id']['dataField'] = 'name';
			$this->setFieldOrder('education_programme_id', 2);
			
			$this->fields['student_id']['type'] = 'hidden';
			$this->fields['student_status_id']['type'] = 'select';
			$this->fields['student_status_id']['options'] = $this->StudentStatus->getList();
			$this->fields['student_status_id']['labelKey'] = 'InstitutionSiteStudent';
			$this->fields['start_year']['visible'] = false;
			$this->fields['end_year']['visible'] = false;
			
			if ($this->action == 'edit') {
				$this->fields['institution_site_id']['type'] = 'disabled';
				$this->fields['education_programme_id']['type'] = 'disabled';
			}
			
			$contentHeader = __('Programmes');
			$this->controller->set(compact('contentHeader'));
		} else {
			return $this->controller->redirect(array('controller' => 'Students', 'action' => 'index'));
		}
	}
	
	public function afterAction() {
		if ($this->action == 'edit') {
			$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');
			$InstitutionSiteProgramme->contain();
			$institutionSiteProgrammeObj = $InstitutionSiteProgramme->find('first', array(
				'conditions' => array(
					'InstitutionSiteProgramme.institution_site_id' => $this->request->data[$this->alias]['institution_site_id'],
					'InstitutionSiteProgramme.education_programme_id' => $this->request->data[$this->alias]['education_programme_id']
				)
			));
			$startDate = $institutionSiteProgrammeObj['InstitutionSiteProgramme']['start_date'];
			$endDate = $institutionSiteProgrammeObj['InstitutionSiteProgramme']['end_date'];

			$dataStartDate = $this->request->data[$this->alias]['start_date'];
			$date = new DateTime($dataStartDate);
			$date->add(new DateInterval('P1D')); // plus 1 day
			$dataEndDate = !empty($this->request->data[$this->alias]['end_date']) ? $this->request->data[$this->alias]['end_date'] : $date->format('d-m-Y');
			
			$this->fields['start_date']['attr'] = array(
				'startDate' => $startDate,
				'endDate' => $endDate,
				'data-date' => $dataStartDate
			);
			$this->fields['end_date']['attr'] = array(
				'startDate' => $date->format('d-m-Y'),
				'data-date' => $dataEndDate
			);
		}
	}
	
	public function index() {
		$alias = $this->alias;
		$studentId = $this->Session->read('Student.id');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$conditions = array("$alias.student_id" => $studentId);
		
		if (!is_null($institutionSiteId)) {
			$conditions["$alias.institution_site_id"] = $institutionSiteId;
		}
		$this->recursive = 0;
		$data = $this->find('all', array(
			'fields' => array('InstitutionSite.name', 'EducationProgramme.name', 'Programme.id', 'Programme.start_date', 'Programme.end_date', 'StudentStatus.name'),
			'conditions' => $conditions,
			'order' => array("$alias.start_date DESC")
		));

		if(empty($data)){
			$this->Message->alert('general.noData');
		}

		$this->controller->set('data', $data);
	}
}
