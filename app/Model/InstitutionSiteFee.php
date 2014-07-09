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

class InstitutionSiteFee extends AppModel {

	public $actsAs = array(
		'ControllerAction'
	);
	public $belongsTo = array(
		'InstitutionSite',
		'SchoolYear',
		'EducationGrade',
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

	public $hasMany = array(
		'InstitutionSiteFeeType' => array(
			'dependent' => true
		),
		'InstitutionSiteFeeStudent' => array(
			'dependent' => true
		)
	);

	public $validate = array(
		'total_fee' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please enter a Total Fee'
			)
		)
	);

	public $headerDefault = 'Fees';

	public $_action = 'fees';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$controller->set('_action', $this->_action);
		$controller->set('selectedAction', $this->_action . 'View');
	}

	public function fee($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);

		$yearOptions = $this->SchoolYear->institutionProgrammeYearList($controller->institutionSiteId);
		$selectedYear = (isset($params->pass[0]) ? $params->pass[0] : ($controller->Session->check('InstitutionSiteFee.selected_year') ? $controller->Session->read('InstitutionSiteFee.selected_year') : key($yearOptions)));
		$data = $this->getListOfFees($selectedYear, $controller->institutionSiteId);

		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');

		if(!empty($selectedYear)){
			$controller->Session->write('InstitutionSiteFee.selected_year', $selectedYear);
		}
		$programmes = $InstitutionSiteProgramme->getSiteProgrammes($institutionSiteId, $selectedYear);
		$modelName = $this->name;
		$controller->set('subheader', $this->headerDefault);
		$controller->set(compact('data', 'selectedYear', 'programmes', 'yearOptions', 'modelName'));
	}

	public function feeView($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$feeId = $controller->params['pass'][0];
		$data = $this->findById($feeId);

		if (!empty($data)) {
			$controller->Session->write('InstitutionSiteFee.id', $feeId);
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

	public function feeAdd($controller, $params) {
		$controller->Navigation->addCrumb('Add ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);

	}
	
	public function feeEdit($controller, $params) {
		$controller->Navigation->addCrumb('Edit ' . $this->headerDefault . ' Details');
		$controller->set('subheader', $this->headerDefault);
		$this->setup_add_edit_form($controller, $params);
		
		$this->render = 'add';
	}


	public function feeDelete($controller, $params) {
		if ($controller->Session->check('InstitutionSiteFee.id')) {
			$id = $controller->Session->read('InstitutionSiteFee.id');
			if ($this->delete($id)) {
				$controller->Message->alert('general.delete.success');
			} else {
				$controller->Message->alert('general.delete.failed');
			}
			return $controller->redirect(array('action' => 'fee'));
		}
	}


	function setup_add_edit_form($controller, $params){
		if(!$controller->Session->check('InstitutionSiteFee.selected_year')){
			return $controller->redirect(array('action'=>'fee'));
		}
		$id = empty($params['pass'][0])? 0:$params['pass'][0];


		$institutionSiteId = $controller->institutionSiteId;
		$yearOptions = $this->SchoolYear->institutionProgrammeYearList($controller->institutionSiteId);
		
		$selected_year = $controller->Session->read('InstitutionSiteFee.selected_year');
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
			$fees = $this->find('all', array(
				'fields' => array('EducationGrade.id', 'InstitutionSiteFee.id', 'EducationGrade.name', 'InstitutionSiteFee.total_fee'),
				'conditions' => array(
					'InstitutionSiteFee.school_year_id' => $yearId,
					'InstitutionSiteFee.institution_site_id' => $institutionSiteId,
					'InstitutionSiteFee.education_grade_id' => array_keys($programGrade['education_grades'])
				),
				'order' => array('EducationGrade.id')
			));
			
			foreach($fees as $fee) {
				$data[$programGrade['education_programme_id']][] = array(
					'id' => $fee['InstitutionSiteFee']['id'],
					'grade' => $fee['EducationGrade']['name'],
					'total_fee'=> $fee['InstitutionSiteFee']['total_fee']
				);
			}
		}
		
		return $data;
	}

}
