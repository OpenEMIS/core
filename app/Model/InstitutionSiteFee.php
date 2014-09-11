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
		'InstitutionSiteFeeType' => array('dependent' => true),
		'InstitutionSiteStudentFee' => array('dependent' => true)
	);
	
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
		$sum = $this->InstitutionSiteFeeType->find('first', array(
			'recursive' => -1,
			'fields' => array('SUM(amount) AS total'),
			'conditions' => array('InstitutionSiteFeeType.institution_site_fee_id' => $id),
			'group' => array('InstitutionSiteFeeType.institution_site_fee_id')
		));
		$total = !empty($sum) ? $sum[0]['total'] : 0;
		$this->id = $id;
		$this->saveField('total', $total);
	}
	
	public function beforeAction() {
		parent::beforeAction();
		$contentHeader = 'Fees';
		$this->Navigation->addCrumb($contentHeader);
		
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$yearOptions = $this->SchoolYear->find('list', array('conditions' => array('available' => 1), 'order' => array('order')));
		
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
		
		if ($this->action == 'view') {
			$this->fields['education_grade_id']['dataModel'] = 'EducationGrade';
			$this->fields['education_grade_id']['dataField'] = 'name';
		}
		
		if ($this->action == 'view' || $this->action == 'edit') {
			$selectedYear = $this->controller->params->pass[2];
			$this->setVar('params', array($selectedYear));
		}
		
		$feeTypes = $this->InstitutionSiteFeeType->FeeType->getList(true);
		$this->setVar('feeTypes', $feeTypes);
		
		$ConfigItem = ClassRegistry::init('ConfigItem');
		$currency = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'currency'));
		$this->setVar('currency', $currency);
		
		$this->setVar('contentHeader', __($contentHeader));
	}
	
	public function index($selectedYear=0) {
		$params = $this->controller->params;
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$yearOptions = $this->SchoolYear->find('list', array('conditions' => array('available' => 1), 'order' => array('order')));
		
		if ($selectedYear == 0) {
			$selectedYear = key($yearOptions);
		}
		
		// need to order by programmes, grades
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
			$yearOptions = $this->SchoolYear->find('list', array('conditions' => array('available' => 1), 'order' => array('order')));
		
			if ($selectedYear == 0) {
				$selectedYear = key($yearOptions);
			}
			
			foreach ($feeTypes as $key => $val) {
				$this->request->data['InstitutionSiteFeeType'][] = array(
					'id' => String::uuid(),
					'fee_type_id' => $key,
					'amount' => ''
				);
			}
		} else {
			$data = $this->request->data;
			$submit = $data['submit'];
			$selectedYear = $data[$this->alias]['school_year_id'];
			
			if ($submit == 'Save') {
				$this->cleanFeeTypes($data);
				
				if ($this->saveAll($data)) {
					$this->updateTotal($this->id);
					$this->Message->alert('general.add.success');
					return $this->redirect(array('action' => get_class($this), 'index', $selectedYear));
				} else {
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('general.add.failed');
				}
			}
		}
		
		$this->fields['school_year_id']['default'] = $selectedYear;
		$this->fields['school_year_id']['attr'] = array('onchange' => "$('#reload').click()");
		
		$gradeOptions = $this->EducationGrade->getGradeOptionsByInstitutionAndSchoolYear($institutionSiteId, $selectedYear);
		// find the grades that already has fees
		$existedGrades = $this->find('list', array(
			'fields' => array('InstitutionSiteFee.education_grade_id', 'InstitutionSiteFee.education_grade_id'),
			'conditions' => array(
				'InstitutionSiteFee.institution_site_id' => $institutionSiteId,
				'InstitutionSiteFee.school_year_id' => $selectedYear
			)
		));
		// remove the existed grades from the options
		$gradeOptions = array_diff_key($gradeOptions, $existedGrades);
		$this->fields['education_grade_id']['type'] = 'select';
		$this->fields['education_grade_id']['options'] = $gradeOptions;
		
		$this->render = 'auto';
		$this->setVar('params', array($selectedYear));
	}
	
	public function edit($id=0, $selectedYear=0) {
		$this->render = 'auto';
		
		if ($this->exists($id)) {
			$data = $this->findById($id);
			
			if ($this->request->is(array('post', 'put'))) {
				$this->cleanFeeTypes($this->request->data);
				$this->InstitutionSiteFeeType->deleteAll(array('InstitutionSiteFeeType.institution_site_fee_id' => $id));
				if ($this->saveAll($this->request->data)) {
					$this->updateTotal($id);
					$this->Message->alert('general.edit.success');
					return $this->redirect(array('action' => get_class($this), 'view', $id, $selectedYear));
				} else {
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('general.edit.failed');
				}
			} else {
				$items = $data['InstitutionSiteFeeType'];
				$feeTypes = $this->controller->viewVars['feeTypes'];
				
				foreach ($feeTypes as $key => $val) {
					$found = false;
					foreach ($items as $obj) {
						if ($obj['fee_type_id'] == $key) {
							$found = true;
							break;
						}
					}
					if (!$found) {
						$data['InstitutionSiteFeeType'][] = array(
							'id' => String::uuid(),
							'institution_site_fee_id' => $id,
							'fee_type_id' => $key,
							'amount' => ''
						);
					}
				}
				$this->request->data = $data;
			}
			$this->fields['school_year_id']['type'] = 'disabled';
			$this->fields['education_grade_id']['type'] = 'disabled';
			$this->fields['education_grade_id']['value'] = $data['EducationGrade']['name'];
		} else {
			$this->Message->alert('general.view.notExists');
			return $this->redirect(array('action' => get_class($this)));
		}	
	}

	public function reportsGetHeader($args) {
		$ConfigItem = ClassRegistry::init('ConfigItem');
   		$currency = $ConfigItem->field('ConfigItem.value', array('ConfigItem.name' => 'currency'));

		$header = array(
			__('School Year'),
			__('Education Programme'),
			__('Education Grade')
		);

		$feeTypeOptions = $this->InstitutionSiteFeeType->FeeType->getList();

		foreach($feeTypeOptions as $id => $type){
			$header[] = __($type);
		}

		$header[] = __('Total Fee') . ' ('.$currency.')';

		return $header;
	}
	
	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		if ($index == 1) {
			$data = $this->find('all', array(
				'fields' => array('InstitutionSiteFee.total', 'InstitutionSiteFee.education_grade_id'),
				'contain' => array(
					'SchoolYear' => array('fields' => array('name')),
					'EducationGrade' => array(
						'fields' => array('name'),
						'EducationProgramme' => array('fields' => array('name'))
					),
					'InstitutionSiteFeeType' => array('fields' => array('amount', 'fee_type_id'))
				),
				'conditions' => array('InstitutionSiteFee.institution_site_id' => $institutionSiteId),
				'order' => array('SchoolYear.name', 'EducationGrade.order')
			));
			$feeTypeOptions = $this->InstitutionSiteFeeType->FeeType->getList();
			
			$csvData = array();
			foreach ($data as $obj) {
				$row = array();
				$row[] = $obj['SchoolYear']['name'];
				$row[] = $obj['EducationGrade']['EducationProgramme']['name'];
				$row[] = $obj['EducationGrade']['name'];
				
				foreach ($feeTypeOptions as $typeId => $type) {
					$amount = '';
					foreach ($obj['InstitutionSiteFeeType'] as $fees) {
						if ($typeId == $fees['fee_type_id']) {
							$amount = number_format($fees['amount'], 2);
							break;
						}
					}
					$row[] = $amount;
				}
				
				$row[] = $obj['InstitutionSiteFee']['total'];
				$csvData[] = $row;
			}

			return $csvData;
		}
	}

	public function reportsGetFileName($args) {
		$index = $args[1];
		return 'Report_Finance_Fee';
	}
}
