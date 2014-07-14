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
		)
	);


	public $hasOne = array(
		'InstitutionSiteStudentFee' => array(
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

	public $_action = 'fee';
	
	public function beforeAction($controller, $action) {
		parent::beforeAction($controller, $action);
		$controller->set('_action', $this->_action);
		$controller->set('selectedAction', $this->_action . 'View');
	}

	public function fee($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault);

		$yearOptions = $this->SchoolYear->institutionProgrammeYearList($controller->institutionSiteId);
		$selectedYear = (isset($params->pass[0]) ? $params->pass[0] : key($yearOptions));

		$programmeOptions = ClassRegistry::init('InstitutionSiteProgramme')->getSiteProgrammeOptions($controller->institutionSiteId, $selectedYear);
		$selectedProgramme =  (isset($params->pass[1]) ? $params->pass[1] : 0);

		$data = $this->getListOfFees($selectedYear, $controller->institutionSiteId);

		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		$InstitutionSiteProgramme = ClassRegistry::init('InstitutionSiteProgramme');

		if(!empty($selectedYear)){
			$controller->Session->write('InstitutionSiteFee.selected_year', $selectedYear);
		}
		$controller->Session->write('InstitutionSiteFee.selected_programme', $selectedProgramme);

		$programmes = array();
		if(empty($selectedProgramme)){
			$programmes = $InstitutionSiteProgramme->getSiteProgrammes($institutionSiteId, $selectedYear);
		}else{
			$programmes = $this->EducationGrade->EducationProgramme->find('first', array('recursive'=>-1,'fields'=>array('EducationProgramme.*'), 'conditions'=>array('EducationProgramme.id'=>$selectedProgramme)));
		}

		if(empty($programmes)){
			$controller->Message->alert('InstitutionSiteProgramme.noData');
		}
		
		$ConfigItem = ClassRegistry::init('ConfigItem');
	   	$currency = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'currency'));

		$modelName = $this->name;
		$controller->set('subheader', $this->headerDefault);
		$controller->set(compact('data', 'currency', 'selectedYear', 'selectedProgramme', 'programmes', 'programmeOptions', 'yearOptions', 'modelName'));
	}

	public function feeView($controller, $params) {
		$controller->Navigation->addCrumb($this->headerDefault . ' Details');
		$feeId = $controller->params['pass'][0];
		$data = $this->find('first', array('recursive'=>2,'conditions'=>array('InstitutionSiteFee.id'=>$feeId)));

		if (!empty($data)) {
			$controller->Session->write('InstitutionSiteFee.id', $feeId);
		} else {
			$controller->Message->alert('general.notExists');
			return $controller->redirect(array('action' => 'fee'));
		}
		
		$institutionSiteFeeTypes = $data['InstitutionSiteFeeType'];

		$ConfigItem = ClassRegistry::init('ConfigItem');
	   	$currency = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'currency'));

		$controller->set('subheader', $this->headerDefault . ' Details');
		$controller->set(compact('data', 'currency', 'feeTypeOptions', 'institutionSiteFeeTypes'));
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

		$institutionSiteId = $controller->institutionSiteId;
		$yearOptions = $this->SchoolYear->institutionProgrammeYearList($controller->institutionSiteId);
		
		$selected_year = $controller->Session->read('InstitutionSiteFee.selected_year');

		$programmeOptions = ClassRegistry::init('InstitutionSiteProgramme')->getSiteProgrammeOptions($institutionSiteId, $selected_year);
		$programmeId = null;

		$financeFeeTypeOptions = array_map('__', $this->InstitutionSiteFeeType->FeeType->getList());
		$controller->set('modelName', $this->name);

		$ConfigItem = ClassRegistry::init('ConfigItem');
	   	$currency = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'currency'));
		
		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			
			if(empty($id)){
				$controller->Message->alert('general.notExists');
				return $controller->redirect(array('action' => 'fee', $selected_year));
			}

			if(strpos($controller->action, 'Add')!==false){
				$selected_year = $controller->Session->read('InstitutionSiteFee.selected_year');
				$educationGrades = $this->EducationGrade->find('first', array('conditions'=>array('EducationGrade.id'=>$id)));
				if(empty($educationGrades)){
					$controller->Message->alert('general.notExists');
					return $controller->redirect(array('action' => 'fee', $selected_year));
				}

				$programmeId = $educationGrades['EducationProgramme']['id'];

				$i = 0;
				$data = array();
				$data['InstitutionSiteFee']['school_year_id'] = $selected_year;
				$data['InstitutionSiteFee']['total_fee'] = 0;
				$data['InstitutionSiteFee']['education_grade_id'] = $educationGrades['EducationGrade']['id'];
				$data['InstitutionSiteFee']['programme_id'] = $programmeId;
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
		
		}else{
			$controller->request->data['InstitutionSiteFee']['institution_site_id'] = $controller->institutionSiteId;
			if ($this->saveAll($controller->request->data)){
				if(empty($controller->request->data[$this->name]['id'])){
					$controller->Message->alert('general.add.success');
				}else{
					$controller->Message->alert('general.edit.success');
				}
				return $controller->redirect(array('action' => 'fee', $selected_year));
			}
		}
		$gradeOptions = $this->EducationGrade->getGradeOptions($programmeId, null);
       
		$controller->set(compact('programmeOptions','gradeOptions', 'yearOptions', 'selected_year', 'currency'));
	}

	private function getListOfFees($yearId, $institutionSiteId) {

		$data = array();

		$programmeGrades = ClassRegistry::init('InstitutionSiteProgramme')->getProgrammeList($institutionSiteId, $yearId);
		foreach($programmeGrades as $programGrade){
			$fees = $this->EducationGrade->find('all', array(
				'recursive'=>-1,
				'fields' => array('EducationGrade.id', 'InstitutionSiteFee.id', 'EducationGrade.name', 'InstitutionSiteFee.total_fee'),
				'joins' => array(
					array(
						'table' => 'education_programmes',
						'alias' => 'EducationProgramme',
						'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
					),
					array(
						'table' => 'institution_site_programmes',
						'alias' => 'InstitutionSiteProgramme',
						'conditions' => array(
							'InstitutionSiteProgramme.education_programme_id = EducationProgramme.id',
						)
					),
					array(
						'type' => 'LEFT',
						'table' => 'institution_site_fees',
						'alias' => 'InstitutionSiteFee',
						'conditions' => array(
							'InstitutionSiteFee.school_year_id = InstitutionSiteProgramme.school_year_id', 
							'InstitutionSiteFee.education_grade_id = EducationGrade.id', 
							'InstitutionSiteFee.institution_site_id = InstitutionSiteProgramme.institution_site_id'
						)
					)
				),
				'conditions' => array(
					'InstitutionSiteProgramme.school_year_id' => $yearId,
					'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
					'EducationGrade.id' => array_keys($programGrade['education_grades'])
				),
				'order' => array('EducationGrade.id')
			));
			
			foreach($fees as $fee) {
				$data[$programGrade['education_programme_id']][] = array(
					'id' => $fee['InstitutionSiteFee']['id'],
					'education_grade_id' => $fee['EducationGrade']['id'],
					'grade' => $fee['EducationGrade']['name'],
					'total_fee'=> $fee['InstitutionSiteFee']['total_fee']
				);
			}
		}
		
		return $data;
	}

}
