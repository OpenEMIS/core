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

class InstitutionSiteProgramme extends AppModel {
	public $actsAs = array(
		'Excel',
		'DatePicker' => array('start_date', 'end_date'),
		'Year' => array('start_date' => 'start_year', 'end_date' => 'end_year')
	);
	
	public $belongsTo = array(
		'InstitutionSite',
		'EducationProgramme',
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

	public $hasMany = array(
		'InstitutionSiteGrade' => array(
			'className' => 'InstitutionSiteGrade',
			'dependent' => true
		)
	);

	public $validate = array(
		'education_programme_id' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Education Programme.'
			),
			'unique' => array(
	            'rule' => array('checkUnique', array('institution_site_id', 'education_programme_id'), false),
	            'message' => 'This Education Programme already exists in the system.'
	        )
		),
		'start_date' => array(
			'ruleRequired' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select the Start Date'
			),
			'ruleCompare' => array(
				'rule' => array('comparison', 'NOT EQUAL', '0000-00-00'),
				'required' => true,
				'message' => 'Please select the Start Date'
			)
		),
		'end_date' => array(
			'ruleCompare' => array(
				'rule' => array('compareDateReverse', 'start_date'),
				'allowEmpty' => true,
				'message' => 'End Date cannot be earlier than Start Date'
			)
		)
	);

	// Do not include compareDate function here
	// It was moved to ValidationBehaviour.php
	
	public $virtualFields = array(
		'name' => "SELECT `education_programmes`.`name` from `education_programmes` WHERE `education_programmes`.`id` = InstitutionSiteProgramme.education_programme_id"
	);
	
	public function beforeAction() {
		$this->Navigation->addCrumb('Programmes');

		if($this->action == 'view') {
			$this->fields['education_programme_id']['dataModel'] = 'EducationProgramme';
			$this->fields['education_programme_id']['dataField'] = 'name';
			$this->fields['start_year']['visible'] = false;
			$this->fields['end_year']['visible'] = false;
			$this->fields['institution_site_id']['visible'] = false;
		} else if($this->action == 'add' || $this->action == 'edit') {
			$institutionSiteId = $this->Session->read('InstitutionSite.id');
			
			$this->fields['education_level_id']['type'] = 'select';
			$this->fields['education_level_id']['visible'] = true;
			$this->fields['education_level_id']['attr'] = array(
				'onchange' => "$('#reload').click()"
			);
			$this->fields['education_programme_id']['type'] = 'select';
			$this->fields['education_programme_id']['attr'] = array(
				'onchange' => "$('#reload').click()"
			);
			$this->fields['start_year']['visible'] = false;
			$this->fields['end_year']['visible'] = false;
			$this->fields['institution_site_id']['type'] = 'hidden';
			$this->fields['institution_site_id']['value'] = $institutionSiteId;
			$this->fields['grades'] = array(
				'type' => 'element',
				'element' => '../InstitutionSites/InstitutionSiteProgramme/grades',
				'visible' => true
			);

			$this->setFieldOrder('education_level_id', 1);
			$this->setFieldOrder('education_programme_id', 2);
			$this->setFieldOrder('start_date', 3);
			$this->setFieldOrder('end_date', 4);

			$this->controller->set(compact('institutionSiteId'));
		}
	}
	
	public function index() {
		$institutionSiteId = $this->Session->read('InstitutionSite.id');

		$this->contain();
		$data = $this->find('all', array(
			'fields' => array(
				'InstitutionSiteProgramme.id', 'InstitutionSiteProgramme.start_date', 'InstitutionSiteProgramme.end_date',
				'EducationLevel.name', 'EducationCycle.name', 'EducationProgramme.name'
			),
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'EducationProgramme.id = InstitutionSiteProgramme.education_programme_id'
					)
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array(
						'EducationCycle.id = EducationProgramme.education_cycle_id'
					)
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array(
						'EducationLevel.id = EducationCycle.education_level_id'
					)
				)
			),
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId
			),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
		));

		$this->controller->set(compact('data'));
	}

	public function add() {
		$EducationLevel = ClassRegistry::init('EducationLevel');
		$educationLevelOptions = $EducationLevel->getOptions();
		$selectedEducationLevel = key($educationLevelOptions);

		$EducationProgramme = $this->EducationProgramme;
		$educationProgrammeOptions = $EducationProgramme->getOptionsByEducationLevelId($selectedEducationLevel);
		$selectedEducationProgramme = key($educationProgrammeOptions);

		if ($this->request->is(array('post', 'put'))) {
			$data = $this->request->data;

			$selectedEducationLevel = $data[$this->alias]['education_level_id'];
			$educationProgrammeOptions = $EducationProgramme->getOptionsByEducationLevelId($selectedEducationLevel);
			
			$dataSelectedProgramme = !empty($data[$this->alias]['education_programme_id']) ? $data[$this->alias]['education_programme_id'] : false;
			if ($dataSelectedProgramme) {
				$selectedEducationProgramme = array_key_exists($dataSelectedProgramme, $educationProgrammeOptions) ? $dataSelectedProgramme : key($educationProgrammeOptions);
			} else {
				$selectedEducationProgramme = key($educationProgrammeOptions);
			}
			
			if($data['submit'] == 'reload') {
				unset($this->request->data['InstitutionSiteGrade']);
			} else {
				if ($this->saveAll($data)) {
					$this->Message->alert('general.add.success');
					return $this->controller->redirect(array('action' => get_class($this), 'index'));
				} else {
					$this->log($this->validationErrors, 'debug');
					$this->Message->alert('general.add.failed');
				}
			}
		} else {
			$this->fields['end_date']['attr'] = array(
				'data-date' => ''
			);
		}
		$EducationGrade = $EducationProgramme->EducationGrade;
		$educationGrades = $EducationGrade->getGradeOptions($selectedEducationProgramme, null, true);

		$this->fields['education_level_id']['options'] = $educationLevelOptions;
		$this->fields['education_programme_id']['options'] = $educationProgrammeOptions;
		$this->controller->set(compact('educationGrades'));
	}

	public function edit($id=0) {		
		if ($this->exists($id)) {
			$this->contain('EducationProgramme.EducationCycle', 'InstitutionSiteGrade');
			$data = $this->findById($id);

			$EducationLevel = ClassRegistry::init('EducationLevel');
			$educationLevelOptions = $EducationLevel->getOptions();
			$selectedEducationLevel = $data['EducationProgramme']['EducationCycle']['education_level_id'];

			$EducationProgramme = $this->EducationProgramme;
			$educationProgrammeOptions = $EducationProgramme->getOptionsByEducationLevelId($selectedEducationLevel);
			$selectedEducationProgramme = $data['EducationProgramme']['id'];

			if ($this->request->is(array('post', 'put'))) {
				$postData = $this->request->data;

				$selectedEducationLevel = $postData[$this->alias]['education_level_id'];
				$educationProgrammeOptions = $EducationProgramme->getOptionsByEducationLevelId($selectedEducationLevel);

				$dataSelectedProgramme = !empty($postData[$this->alias]['education_programme_id']) ? $postData[$this->alias]['education_programme_id'] : false;
				if ($dataSelectedProgramme) {
					$selectedEducationProgramme = array_key_exists($dataSelectedProgramme, $educationProgrammeOptions) ? $dataSelectedProgramme : key($educationProgrammeOptions);
				} else {
					$selectedEducationProgramme = key($educationProgrammeOptions);
				}

				if($postData['submit'] == 'reload') {
					unset($this->request->data['InstitutionSiteGrade']);
				} else {
					if ($this->saveAll($postData)) {
						$this->Message->alert('general.edit.success');
						return $this->controller->redirect(array('action' => get_class($this), 'view', $this->id));
					} else {
						$this->log($this->validationErrors, 'debug');
						$this->Message->alert('general.edit.failed');
					}
				}
			} else {
				$data[$this->alias]['education_level_id'] = $selectedEducationLevel;
				$this->request->data = $data;
			}

			$EducationGrade = $EducationProgramme->EducationGrade;
			$educationGrades = $EducationGrade->getGradeOptions($selectedEducationProgramme, null, true);

			$this->fields['education_level_id']['options'] = $educationLevelOptions;
			$this->fields['education_programme_id']['options'] = $educationProgrammeOptions;
			$this->controller->set(compact('educationGrades'));
		} else {
			$this->Message->alert('general.notExists');
			return $this->controller->redirect(array('action' => get_class($this)));
		}
	}

	public function getConditionsByAcademicPeriodId($academicPeriodId=0, $conditions=array()) {
		$modelConditions = array();
		if($academicPeriodId > 0) {
			$AcademicPeriod = ClassRegistry::init('AcademicPeriod');
			$academicPeriodObj = $AcademicPeriod->findById($academicPeriodId);
			$startDate = $AcademicPeriod->getDate($academicPeriodObj['AcademicPeriod'], 'start_date');
			$endDate = $AcademicPeriod->getDate($academicPeriodObj['AcademicPeriod'], 'end_date');

			$modelConditions['OR'] = array(
				'OR' => array(
					array(
						$this->alias.'.end_date IS NOT NULL',
						$this->alias.'.start_date <= "' . $startDate . '"',
						$this->alias.'.end_date >= "' . $startDate . '"'
					),
					array(
						$this->alias.'.end_date IS NOT NULL',
						$this->alias.'.start_date <= "' . $endDate . '"',
						$this->alias.'.end_date >= "' . $endDate . '"'
					),
					array(
						$this->alias.'.end_date IS NOT NULL',
						$this->alias.'.start_date >= "' . $startDate . '"',
						$this->alias.'.end_date <= "' . $endDate . '"'
					)
				),
				array(
					$this->alias.'.end_date IS NULL',
					$this->alias.'.start_date <= "' . $endDate . '"'
				)
			);
		}

		$conditions = array_merge($conditions, $modelConditions);

		return $conditions;
	}
	
	public function getProgrammeOptions($institutionSiteId, $academicPeriodId=null) {
		$conditions = array('InstitutionSiteProgramme.institution_site_id' => $institutionSiteId);

		if(!is_null($academicPeriodId)) {
			$conditions = $this->getConditionsByAcademicPeriodId($academicPeriodId, $conditions);
		}
		
		$this->contain('EducationProgramme');
		$options = array();
		$options['conditions'] = $conditions;
		$options['order'] = array('EducationProgramme.order');

		$this->contain('EducationProgramme');
		$data = $this->find('all', $options);

		$list = array();
		foreach($data as $obj) {
			$list[$obj['EducationProgramme']['id']] = $obj['EducationProgramme']['cycle_programme_name'];
		}

		return $list;
	}
	
	// used by InstitutionSiteController
	public function getSiteProgrammes($institutionSiteId, $academicPeriodId) {
		$this->formatResult = true;
		$conditions = array(
			'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId
		);
		$conditions = $this->getConditionsByAcademicPeriodId($academicPeriodId, $conditions);

		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'InstitutionSiteProgramme.id',
				'EducationSystem.name AS education_system_name',
				'EducationLevel.name AS education_level_name',
				'EducationCycle.name AS education_cycle_name',
				'EducationCycle.admission_age AS admission_age',
				'EducationProgramme.id AS education_programme_id',
				'EducationProgramme.name AS education_programme_name'
			),
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id')
				),
				array(
					'table' => 'education_systems',
					'alias' => 'EducationSystem',
					'conditions' => array('EducationSystem.id = EducationLevel.education_system_id')
				)
			),
			'conditions' => $conditions,
			'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
		));

		return $data;
	}
	
	public function getSiteProgrammeOptions($institutionSiteId, $academicPeriodId) {
		$list = array();

		$data = $this->getSiteProgrammes($institutionSiteId, $academicPeriodId);
		foreach($data as &$obj) {
			$list[$obj['education_programme_id']] = $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'];
		}

		return $list;
	}
	
	// used by CensusController, classes/teachers
	public function getProgrammeList($institutionSiteId, $academicPeriodId, $withGrades = true) {
		$list = $this->getActiveProgrammes($institutionSiteId, $academicPeriodId);
		
		$data = array();
		foreach($list as $obj) {
			$name = $obj[0]['education_programme_name'];
			$programme = $obj['EducationProgramme'];
			$grade = $obj['EducationGrade'];
			
			if($withGrades) {
				if(!isset($data[$name])) {
					$data[$name] = array(
						'institution_site_programme_id' => $obj['InstitutionSiteProgramme']['id'],
						'education_programme_id' => $programme['id'],
						'education_grades' => array()
					);
				}
				$data[$name]['education_grades'][$grade['id']] = array('gradeName' => $grade['name']);
			} else {
				if(!isset($data[$programme['id']])) {
					$data[$programme['id']] = $name;
				}
			}
		}
		return $data;
	}
	
	public function getActiveProgrammes($institutionSiteId, $academicPeriodId, $formatResult = false) {
		$fields = $formatResult
				? array(
					'InstitutionSiteProgramme.id AS institution_site_programme_id',
					'EducationProgramme.id AS education_programme_id',
					"CONCAT(EducationCycle.name, ' - ', EducationProgramme.name) AS education_programme_name",
					'EducationGrade.id AS education_grade_id',
					'EducationGrade.name AS education_grade_name'
				)
				: array(
					'InstitutionSiteProgramme.id',
					'EducationProgramme.id', 
					"CONCAT(EducationCycle.name, ' - ', EducationProgramme.name) AS education_programme_name",
					'EducationGrade.id', 'EducationGrade.name'
				);
		
		$this->formatResult = $formatResult;
		$conditions = array(
			'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId
		);
		$conditions = $this->getConditionsByAcademicPeriodId($academicPeriodId, $conditions);

		$data = $this->find('all' , array(
			'recursive' => -1,
			'fields' => $fields,
			'joins' => array(
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = InstitutionSiteProgramme.education_programme_id')
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array('EducationCycle.id = EducationProgramme.education_cycle_id')
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array('EducationLevel.id = EducationCycle.education_level_id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.education_programme_id = EducationProgramme.id')
				)
			),
			'conditions' => $conditions,
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		return $data;
	}
	
	// was getYearsHaveProgrammes
	public function getAcademicPeriodsHaveProgrammes($institutionSiteId){
		$data = $this->find('all', array(
				'recursive' => -1,
				'fields' => array(
					'AcademicPeriod.id',
					'AcademicPeriod.name'
				),
				'joins' => array(
						array(
							'table' => 'academic_periods',
							'alias' => 'AcademicPeriod',
							'conditions' => array(
								'InstitutionSiteProgramme.academic_period_id = AcademicPeriod.id'
							)
						)
				),
				'conditions' => array('InstitutionSiteProgramme.institution_site_id' => $institutionSiteId),
				'group' => array('InstitutionSiteProgramme.academic_period_id'),
				'order' => array('AcademicPeriod.name DESC')
			)
		);
		
		return $data;
	}
	
	public function getSiteProgrammeForSelection($institutionSiteId, $academicPeriodId) {
		$data = array();
		$list = $this->getSiteProgrammes($institutionSiteId, $academicPeriodId);
		foreach($list as &$obj) {
			$data[$obj['id']] = $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'];
		}
		return $data;
	}

	// used by CensusTextbook
	public function getSiteProgrammeGrades($institutionSiteId, $academicPeriodId, $programmeId) {
		$data = array();
		$list = $this->getProgrammeList($institutionSiteId, $academicPeriodId);
		foreach($list as &$obj) {
			if($obj['institution_site_programme_id'] == $programmeId) {
				foreach($obj['education_grades'] as $key => $value) {
					$data[$key] = $value['gradeName'];
				}
				break;
			}
		}
		return $data;
	}
	
	public function programmesOptions($controller, $params) {
		$controller->layout = 'ajax';
		$this->render = false;

		$academicPeriodId = $controller->params->query['academicPeriodId'];
		$programmeOptions = $controller->InstitutionSiteProgramme->getSiteProgrammeForSelection($controller->institutionSiteId, $academicPeriodId, false);
		
		$controller->set(compact('programmeOptions'));
		$controller->render('/Elements/programmes/programmes_options');
	}

	public function getAcademicPeriodOptions($conditions=array()) {
		$startDate = $this->field('MIN(InstitutionSiteProgramme.start_date) AS min_date', $conditions);
		$endDate = $this->field('MAX(InstitutionSiteProgramme.end_date) AS max_date', $conditions);
		$conditions = array_merge(array('InstitutionSiteProgramme.end_date' => NULL), $conditions);
		$options['conditions'] = $conditions;
		$nullDate = $this->find('count', $options);

		$academicPeriodConditions = array();
		$academicPeriodConditions['AcademicPeriod.parent_id >'] = 0;
		$academicPeriodConditions['AcademicPeriod.end_date >='] = $startDate;
		if($nullDate == 0) {
			$academicPeriodConditions['AcademicPeriod.start_date <='] = $endDate;
		} else {
			$academicPeriodConditions['AcademicPeriod.end_date >='] = $startDate;
		}

		$AcademicPeriod = ClassRegistry::init('AcademicPeriod');
		$list = $AcademicPeriod->find('list', array(
			'fields' => array('AcademicPeriod.id', 'AcademicPeriod.name'),
			'conditions' => $academicPeriodConditions,
			'order' => array('AcademicPeriod.order')
		));

		return $list;
	}
}
