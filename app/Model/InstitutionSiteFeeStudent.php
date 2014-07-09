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

class InstitutionSiteFeeStudent extends AppModel {

	public $actsAs = array(
		'ControllerAction'
	);
	public $belongsTo = array(
		'InstitutionSiteFee',
		'Students.Student',
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
		'paid' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a Paid Amount'
			)
		)
	);

	public $headerDefault = 'Students';

	public $_action = 'students';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$controller->set('_action', $this->_action);
		$controller->set('selectedAction', $this->_action . 'View');
	}

	public function feeStudent($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);

		$schoolYear = ClassRegistry::init('SchoolYear');
		$yearOptions = $schoolYear->institutionProgrammeYearList($controller->institutionSiteId);
		$selectedYear = (isset($params->pass[0]) ? $params->pass[0] : ($controller->Session->check('InstitutionSiteFeeStudent.selected_year') ? $controller->Session->read('InstitutionSiteFeeStudent.selected_year') : key($yearOptions)));
		$data = $this->getListOfFees($selectedYear, $controller->institutionSiteId);

		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');

		if(!empty($selectedYear)){
			$controller->Session->write('InstitutionSiteFeeStudent.selected_year', $selectedYear);
		}
		$programmes = $InstitutionSiteProgramme->getSiteProgrammes($institutionSiteId, $selectedYear);
		$EducationGrade = ClassRegistry::init('EducationGrade');

		foreach($programmes as $key => $programme){
			$programmes[$key]['education_grades'] = $EducationGrade->find('list', array('recursive'=>-1, 'fields'=>array('id', 'name'), 'conditions'=>array('EducationGrade.education_programme_id'=>$programme['education_programme_id'])));
		}
		pr($programmes);
		$modelName = $this->name;
		$controller->set('subheader', $this->headerDefault);
		$controller->set(compact('data', 'selectedYear', 'programmes', 'yearOptions', 'modelName'));
	}

	public function feeStudentView($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$feeId = $controller->params['pass'][0];
		$data = $this->findById($feeId);

		if (!empty($data)) {
			$controller->Session->write('InstitutionSiteFeeStudent.id', $feeId);
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'fee'));
		}
		
		$yearOptions = $this->SchoolYear->institutionProgrammeYearList($controller->institutionSiteId);
		
		$grades = $this->EducationGrade->find('first', array('conditions'=>array('EducationGrade.id'=>$data['InstitutionSiteFee']['education_grade_id'])));
		
		$data['InstitutionSiteFee']['grade'] = $grades['EducationGrade']['name'];
		$data['InstitutionSiteFee']['programme'] = $grades['EducationProgramme']['name'];
		
		$institutionSiteFeeTypes = $this->InstitutionSiteFeeType->find('all', array('conditions'=>array('InstitutionSiteFeeType.institution_site_fee_id'=>$feeId)));

		$controller->set('subheader', $this->headerDefault . ' Details');
		$controller->set(compact('data', 'yearOptions', 'institutionSiteFeeTypes'));
	}

	public function feeStudentAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);

	}
	
	public function feeStudentEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
		
		$this->render = 'add';
	}


	public function feeStudentDelete($controller, $params) {
		if ($controller->Session->check('InstitutionSiteFeeStudent.id')) {
			$id = $controller->Session->read('InstitutionSiteFeeStudent.id');
			if ($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			return $controller->redirect(array('action' => 'fee'));
		}
	}


	function setup_add_edit_form($controller, $params){
		if(!$controller->Session->check('InstitutionSiteFeeStudent.selected_year')){
			return $controller->redirect(array('action'=>'fee'));
		}
		$id = empty($params['pass'][0])? 0:$params['pass'][0];


		$institutionSiteId = $controller->institutionSiteId;
		$yearOptions = $this->SchoolYear->institutionProgrammeYearList($controller->institutionSiteId);
		
		$selected_year = $controller->Session->read('InstitutionSiteFeeStudent.selected_year');
		$programmeOptions = ClassRegistry::init('InstitutionSiteProgramme')->getSiteProgrammeOptions($institutionSiteId, $selected_year);
		$programmeId = null;
		
		$financeFeeTypeOptions = array_map('__', $this->InstitutionSiteFeeType->FeeType->getList());
		$controller->set('modelName', $this->name);
		

		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			if(empty($id)){
				$programmeId = (isset($params->pass[0]) ? $params->pass[0] : key($programmeOptions));
				$i = 0;
				$data = array();
				$data['InstitutionSiteFee']['total_fee'] = 0;
				foreach($financeFeeTypeOptions as $key=>$val){
					$data['InstitutionSiteFeeType'][$i]['id'] = null;
					$data['InstitutionSiteFeeType'][$i]['fee'] = 0;
					$data['InstitutionSiteFeeType'][$i]['fee_type_id'] = $key;
					$data['InstitutionSiteFeeType'][$i]['fee_type_name'] = $val;
					$i++;
				}

				$controller->request->data = $data;
			}else{
				$this->recursive = -1;
				$data = $this->findById($id);

				$grades = $this->EducationGrade->find('first', array('conditions'=>array('EducationGrade.id'=>$data['InstitutionSiteFee']['education_grade_id'])));
				$programmeId = $grades['EducationProgramme']['id'];
				$data['InstitutionSiteFee']['programme_id'] = $programmeId;
				
				$institutionSiteFeeTypes = $this->InstitutionSiteFeeType->find('all', array('conditions'=>array('InstitutionSiteFeeType.institution_site_fee_id'=>$id)));
				$institutionSiteFeeTypesVal = array();
				$i = 0;
				if(!empty($institutionSiteFeeTypes)){
					foreach($institutionSiteFeeTypes as $institutionSiteFeeType){
						$institutionSiteFeeTypesVal[$i] = $institutionSiteFeeType['InstitutionSiteFeeType'];
						$institutionSiteFeeTypesVal[$i]['fee_type_name'] = $institutionSiteFeeType['FeeType']['name'];
						$i++;
					}
				}

				$controller->request->data = array_merge($data, array('InstitutionSiteFeeType'=>$institutionSiteFeeTypesVal));
			}
		
		}
		else{
			pr($controller->request->data);
			$controller->request->data['InstitutionSiteFee']['institution_site_id'] = $controller->institutionSiteId;
			if ($this->saveAll($controller->request->data)){
				if(empty($controller->request->data[$this->name]['id'])){
					$controller->Message->alert('general.add.success');
				}else{
					$controller->Message->alert('general.edit.success');
				}
				return $controller->redirect(array('action' => 'fee'));
			}
		}
	 	$gradeOptions = $controller->EducationGrade->getGradeOptions($programmeId, null);
       
		$controller->set(compact('programmeOptions','gradeOptions', 'yearOptions', 'selected_year'));
	}

	private function getListOfFees($yearId, $institutionSiteId) {

		$data = array();

		$programmeGrades = ClassRegistry::init('InstitutionSiteProgramme')->getProgrammeList($institutionSiteId, $yearId);
		foreach($programmeGrades as $programGrade){
			$fees = $this->Student->find('all', array(
				'recursive' => -1,
				'fields' => array('Student.id', 'Student.identification_no', 'Student.first_name', 'Student.last_name', 'InstitutionSiteFeeStudent.total_paid', 
					'InstitutionSiteFeeStudent.total_outstanding', 'EducationGrade.id', 'EducationGrade.name'),
				'joins' => array(
					array(
					
						'table' => 'institution_site_students',
						'alias' => 'InstitutionSiteStudent',
						'conditions' => array('InstitutionSiteStudent.student_id = Student.id')
					),
					array(
						'table' => 'institution_site_programmes',
						'alias' => 'InstitutionSiteProgramme',
						'conditions' => array('InstitutionSiteProgramme.id = InstitutionSiteStudent.institution_site_programme_id')
					),
					array(
						'table' => 'education_programmes',
						'alias' => 'EducationProgramme',
						'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
					),
					array(
						'table' => 'education_grades',
						'alias' => 'EducationGrade',
						'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
					),
					array(
						'type' => 'LEFT',
						'table' => 'institution_site_fee_students',
						'alias' => 'InstitutionSiteFeeStudent',
						'conditions' => array('InstitutionSiteFeeStudent.student_id = Student.id')
					)
				),
				'conditions' => array(
					'InstitutionSiteProgramme.school_year_id' => $yearId,
					'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
					'EducationGrade.id' => array_keys($programGrade['education_grades'])
				),
				'order' => array('EducationGrade.id', 'Student.first_name', 'Student.last_name')
			));
			

			foreach($fees as $fee) {
				$data[$programGrade['education_programme_id'].'_'.$fee['EducationGrade']['id']][] = array(
					'id' => $fee['Student']['id'],
					'identification_no' => $fee['Student']['identification_no'],
					'name' => $fee['Student']['first_name'] . ' ' . $fee['Student']['last_name'],
					'grade' => $fee['EducationGrade']['name'],
					'total_paid'=> $fee['InstitutionSiteFeeStudent']['total_paid'],
					'total_outstanding'=> $fee['InstitutionSiteFeeStudent']['total_outstanding']
				);
			}
		}
		
		return $data;
	}

}
