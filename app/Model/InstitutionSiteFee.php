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
		'ControllerAction2',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		)
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
		'InstitutionSiteFeeType' => array('dependent' => true)
	);

	/*
	public $hasOne = array(
		'InstitutionSiteStudentFee' => array(
			'dependent' => true
		)
	);
	
	public function afterSave($created, $options = array()) {
		//pr($this->data);die;
		$id = $this->data[$this->alias]['id'];
		$total = $this->InstitutionSiteFeeType->find('all', array(
			'recursive' => -1,
			'fields' => array('SUM(amount) AS total'),
			'conditions' => array('InstitutionSiteFeeType.institution_site_fee_id' => $id),
			'group' => array('InstitutionSiteFeeType.institution_site_fee_id')
		));
		$this->log($total, 'debug');
		parent::afterSave($created, $options);
	}
	*/
	
	private function cleanFeeTypes(&$data) {
		if (isset($data['InstitutionSiteFeeType'])) {
			$types = $data['InstitutionSiteFeeType'];
			foreach ($types as $i => $obj) {
				if (empty($obj['amount'])) {
					unset($data['InstitutionSiteFeeType'][$i]);
				}
			}
		}
	}
	
	public function updateTotal($id) {
		$total = $this->InstitutionSiteFeeType->find('all', array(
			'recursive' => -1,
			'fields' => array('SUM(amount) AS total'),
			'conditions' => array('InstitutionSiteFeeType.institution_site_fee_id' => $id),
			'group' => array('InstitutionSiteFeeType.institution_site_fee_id')
		));
		pr($total);
	}
	
	public function beforeAction() {
		parent::beforeAction();
		$contentHeader = 'Fees';
		$this->Navigation->addCrumb($contentHeader);
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$yearOptions = $this->SchoolYear->getAvailableYears(true, 'DESC');
		
		$this->fields['total']['visible'] = false;
		$this->fields['institution_site_id']['type'] = 'hidden';
		$this->fields['institution_site_id']['value'] = $institutionSiteId;
		$this->fields['school_year_id']['type'] = 'select';
		$this->fields['school_year_id']['options'] = $yearOptions;
		$this->fields['fee_types'] = array(
			'type' => 'element',
			'element' => '../InstitutionSites/InstitutionSiteFee/fee_types',
			'visible' => true
		);
		$this->setFieldOrder('fee_types', 5);
		
		$ConfigItem = ClassRegistry::init('ConfigItem');
	   	$currency = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'currency'));
		$this->setVar('currency', $currency);
		$this->setVar('contentHeader', __($contentHeader));
	}
	
	public function index($selectedYear=0) {
		$params = $this->controller->params;
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$yearOptions = $this->SchoolYear->getAvailableYears(true, 'DESC');
		
		if ($selectedYear == 0) {
			$selectedYear = key($yearOptions);
		}
		
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('InstitutionSiteFee.*', 'EducationGrade.name', 'EducationGrade.education_programme_id'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = InstitutionSiteFee.education_grade_id')
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
				)
			),
			'conditions' => array(
				'InstitutionSiteFee.institution_site_id' => $institutionSiteId,
				'InstitutionSiteFee.school_year_id' => $selectedYear
			),
			'order' => array('EducationProgramme.order', 'EducationGrade.order')
		));
		
		$programmeOptions = $this->EducationGrade->EducationProgramme->find('list');
		
		if (empty($data)) {
			$this->Message->alert('general.noData');
		}

		$this->setVar(compact('data', 'selectedYear', 'programmeOptions', 'yearOptions'));
	}
	
	public function add($selectedYear=0) {
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		
		$feeTypes = $this->InstitutionSiteFeeType->FeeType->getList(true);
		
		if ($this->request->is('get')) {
			$yearOptions = $this->SchoolYear->getAvailableYears(true, 'DESC');
		
			if ($selectedYear == 0) {
				$selectedYear = key($yearOptions);
			}
			
			foreach ($feeTypes as $key => $val) {
				$this->request->data['InstitutionSiteFeeType'][] = array(
					'fee_type_id' => $key,
					'name' => $val,
					'amount' => ''
				);
			}
		} else {
			$data = $this->request->data;
			$submit = $data['submit'];
			$selectedYear = $data[$this->alias]['school_year_id'];
			
			if ($submit == 'Save') {
				$this->cleanFeeTypes($data);
				//pr($data);die;
				/*
				if (isset($data['InstitutionSiteFeeType'])) {
					$types = $data['InstitutionSiteFeeType'];
					foreach ($types as $i => $obj) {
						if (empty($obj['InstitutionSiteFeeType']['amount'])) {
							unset($data['InstitutionSiteFeeType'][$i]);
						}
					}
					if (empty($data['InstitutionSiteFeeType'])) {
						//unset($this->data['InstitutionSiteFeeType']);
					}
				}
				*/
				if ($this->saveAll($data)) {
					$this->Message->alert('general.add.success');
					return $this->redirect(array('action' => get_class($this)));
				} else {
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('general.add.failed');
				}
			}
		}
		
		$this->fields['school_year_id']['default'] = $selectedYear;
		$this->fields['school_year_id']['attr'] = array('onchange' => "$('#reload').click()");
		$this->fields['education_grade_id']['type'] = 'select';
		
		$gradeOptions = $this->EducationGrade->getGradeOptionsByInstitutionAndSchoolYear($institutionSiteId, $selectedYear);
		$this->fields['education_grade_id']['options'] = $gradeOptions;
		
		$this->render = 'auto';
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
		return $this->remove($controller, 'fee');
	}


	function setup_add_edit_form($controller, $params){
		if(!$controller->Session->check('InstitutionSiteFee.selected_year')){
			return $controller->redirect(array('action'=>'fee'));
		}

		$institutionSiteId = $controller->Session->read('InstitutionSite.id');
		$yearOptions = $this->SchoolYear->institutionProgrammeYearList($institutionSiteId);
		
		$selectedYear = $controller->Session->read('InstitutionSiteFee.selected_year');

		$programmeOptions = ClassRegistry::init('InstitutionSiteProgramme')->getSiteProgrammeOptions($institutionSiteId, $selectedYear);
		$programmeId = null;

		$financeFeeTypeOptions = array_map('__', $this->InstitutionSiteFeeType->FeeType->getList());

		$ConfigItem = ClassRegistry::init('ConfigItem');
	   	$currency = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'currency'));
		
		if($controller->request->is('get')){
			$id = empty($params['pass'][0])? 0:$params['pass'][0];
			
			if(empty($id)){
				$controller->Message->alert('general.notExists');
				return $controller->redirect(array('action' => 'fee', $selectedYear));
			}

			if(strpos($controller->action, 'Add')!==false){
				$selectedYear = $controller->Session->read('InstitutionSiteFee.selected_year');
				$educationGrades = $this->EducationGrade->find('first', array('conditions'=>array('EducationGrade.id'=>$id)));
				if(empty($educationGrades)){
					$controller->Message->alert('general.notExists');
					return $controller->redirect(array('action' => 'fee', $selectedYear));
				}

				$programmeId = $educationGrades['EducationProgramme']['id'];
				$gradeOptions = $this->EducationGrade->getGradeOptions($programmeId, null, true);
				$i = 0;
				$data = array();
				$data['InstitutionSiteFee']['school_year_id'] = $selectedYear;
				$data['InstitutionSiteFee']['total_fee'] = 0;
				$data['InstitutionSiteFee']['education_grade_id'] = $educationGrades['EducationGrade']['id'];
				$data['InstitutionSiteFee']['programme_id'] = $programmeId;

				$data['InstitutionSiteFee']['school_year'] = $yearOptions[$selectedYear];
				$data['InstitutionSiteFee']['programme'] = $programmeOptions[$programmeId];
				$data['InstitutionSiteFee']['education_grade'] = $gradeOptions[$educationGrades['EducationGrade']['id']];


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

				$gradeOptions = $this->EducationGrade->getGradeOptions($programmeId, null, true);

				$data['InstitutionSiteFee']['school_year'] = $yearOptions[$selectedYear];
				$data['InstitutionSiteFee']['programme'] = $programmeOptions[$programmeId];
				$data['InstitutionSiteFee']['education_grade'] = $gradeOptions[$data['InstitutionSiteFee']['education_grade_id']];


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
			$controller->request->data['InstitutionSiteFee']['institution_site_id'] = $institutionSiteId;
			if ($this->saveAll($controller->request->data)){
				if(empty($controller->request->data[$this->name]['id'])){
					$controller->Message->alert('general.add.success');
				}else{
					$controller->Message->alert('general.edit.success');
				}
				return $controller->redirect(array('action' => 'fee', $selectedYear));
			}
		}
       
		$controller->set(compact('selectedYear', 'currency'));
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
					'EducationGrade.id' => array_keys($programGrade['education_grades']),
					'EducationGrade.visible' => 1
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

	public function reportsGetHeader($args) {
		$ConfigItem = ClassRegistry::init('ConfigItem');
   		$currency = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'currency'));

		$header = array(
			__('School Year'),
			__('Education Programme'),
			__('Education Grade')
		);

		$financeFeeTypeOptions = array_map('__', $this->InstitutionSiteFeeType->FeeType->getList());

		foreach($financeFeeTypeOptions as $ft){
			$header[] = $ft;
		}

		$header[] = __('Total Fee') . ' ('.$currency.')';

		return $header;
	}
	
	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		if ($index == 1) {
			$options = array();
			$options['recursive'] = -1;
			$options['fields'] = array(
				'SchoolYear.name', 
				'SchoolYear.id', 
				'EducationProgramme.name', 
				'EducationGrade.id',
				'EducationGrade.name',
			);

			$options['order'] = array('InstitutionSiteProgramme.school_year_id', 'EducationProgramme.id', 'EducationGrade.id');
			$options['conditions'] = array('InstitutionSiteProgramme.institution_site_id' => $institutionSiteId, 'EducationGrade.visible'=>1);
			$options['joins'] = array(
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
						'table' => 'school_years',
						'alias' => 'SchoolYear',
						'conditions' => array('SchoolYear.id = InstitutionSiteProgramme.school_year_id')
					),
				);
			$data = $this->EducationGrade->find('all', $options);

			$fees = $this->find('all', array('conditions'=>array('InstitutionSiteFee.institution_site_id'=>$institutionSiteId), 'order'=>array('InstitutionSiteFee.school_year_id', 'InstitutionSiteFee.education_grade_id')));
	
			$financeFeeTypeOptions = array_map('__', $this->InstitutionSiteFeeType->FeeType->getList());

			$newData = array();
			foreach($data AS $row){
				$tempRow = array();
				
				$schoolYear = $row['SchoolYear'];
				$educationProgramme = $row['EducationProgramme'];
				$educationGrade = $row['EducationGrade'];
				
				$tempRow[] = $schoolYear['name'];
				$tempRow[] = $educationProgramme['name'];
				$tempRow[] = $educationGrade['name'];
				
				$totalFee = 0;
				if(!empty($financeFeeTypeOptions)){
					$tempFT = array();
					foreach($financeFeeTypeOptions as $key=>$ft){
						$tempFT[$key] = number_format(0, 2);
					}
					foreach($fees as $fee){
						if($fee['EducationGrade']['id']==$educationGrade['id'] && $fee['SchoolYear']['id']==$schoolYear['id']){
							if(isset($fee['InstitutionSiteFeeType'])){
								foreach($fee['InstitutionSiteFeeType'] as $key=>$val){
									$tempFT[$val['fee_type_id']] = $val['fee'];
									$totalFee += $val['fee'];
								}
							}
							break;
						}
					}
					foreach($tempFT as $val){
						$tempRow[] = $val;
					}
				}
				$tempRow[] = number_format($totalFee, 2);
				$newData[] = $tempRow;
			}

			return $newData;
		}
	}

	public function reportsGetFileName($args) {
		$index = $args[1];
		return 'Report_Finance_Fee';
	}
	

}
