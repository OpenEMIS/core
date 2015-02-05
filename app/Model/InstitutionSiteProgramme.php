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
		'ControllerAction2',
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

	public $validate = array(
		'education_programme_id' => array(
			'notEmpty' => array(
				'rule' => 'notEmpty',
				'required' => true,
				'message' => 'Please select a Education Programme.'
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
			),
			'ruleNotLater' => array(
				'rule' => array('compareDate', 'end_date'),
				'message' => 'Start Date cannot be later than End Date'
			),
		)
	);

	public function compareDate($field = array(), $compareField = null) {
		$startDate = new DateTime(current($field));
		$endDate = new DateTime($this->data[$this->name][$compareField]);
		return $endDate >= $startDate;
	}
	
	public $virtualFields = array(
		'name' => "SELECT `education_programmes`.`name` from `education_programmes` WHERE `education_programmes`.`id` = InstitutionSiteProgramme.education_programme_id"
	);
	
	public function beforeAction() {
		parent::beforeAction();
		$this->Navigation->addCrumb('Programmes');

		$params = $this->request->params;

		if(isset($params['pass'][0])) {
			if($params['pass'][0] == 'add' || $params['pass'][0] == 'edit') {
				$institutionSiteId = $this->Session->read('InstitutionSite.id');

				$this->fields['education_level_id']['type'] = 'select';
				$this->fields['education_level_id']['visible'] = true;
				$EducationLevel = ClassRegistry::init('EducationLevel');
				$EducationLevel->contain('EducationSystem');
				$educationLevels = $EducationLevel->find('all', array(
					'order' => array(
						'EducationSystem.order', 'EducationLevel.order'
					)
				));
				$educationLevelOptions = array();
				foreach ($educationLevels as $key => $obj) {
					$educationLevelOptions[$obj['EducationLevel']['id']] = $obj['EducationSystem']['name'] . " - " . $obj['EducationLevel']['name'];
				}
				$this->fields['education_level_id']['options'] = $educationLevelOptions;
				$this->fields['education_level_id']['attr'] = array(
					'onchange' => "$('#reload').click()"
				);

				$selectedEducationLevel = key($educationLevelOptions);
				if ($this->request->is(array('post', 'put'))) {
					$data = $this->request->data;

					$selectedEducationLevel = $data[$this->alias]['education_level_id'];

					if($data['submit'] == 'reload') {
						//onchange
					} else {
						//actual submit
					}
				} else {
					$this->fields['end_date']['attr'] = array(
						'data-date' => ''
					);
				}

				$EducationProgramme = ClassRegistry::init('EducationProgramme');
				$EducationProgramme->contain('EducationCycle');
				$educationProgrammes = $EducationProgramme->find('all', array(
					'conditions' => array(
						'EducationCycle.education_level_id' => $selectedEducationLevel
					),
					'order' => array(
						'EducationCycle.order', 'EducationProgramme.order'
					)
				));
				$educationProgrammeOptions = array();
				foreach ($educationProgrammes as $key => $obj) {
					$educationProgrammeOptions[$obj['EducationProgramme']['id']] = $obj['EducationCycle']['name'] . " - " . $obj['EducationProgramme']['name'];
				}
				$this->fields['education_programme_id']['type'] = 'select';
				$this->fields['education_programme_id']['options'] = $educationProgrammeOptions;

				$this->fields['start_year']['visible'] = false;
				$this->fields['end_year']['visible'] = false;
				$this->fields['institution_site_id']['type'] = 'hidden';
				$this->fields['institution_site_id']['value'] = $institutionSiteId;

				$this->setFieldOrder('education_level_id', 1);
				$this->setFieldOrder('education_programme_id', 2);
				$this->setFieldOrder('start_date', 3);
				$this->setFieldOrder('end_date', 4);
			} else if($params['pass'][0] == 'view') {
				$this->fields['education_programme_id']['dataModel'] = 'EducationProgramme';
				$this->fields['education_programme_id']['dataField'] = 'name';
				$this->fields['start_year']['visible'] = false;
				$this->fields['end_year']['visible'] = false;
				$this->fields['institution_site_id']['visible'] = false;
			}
		}
	}
	
	public function index() {
		$institutionSiteId = $this->Session->read('InstitutionSite.id');

		$this->contain(array(
			'EducationProgramme' => array(
				'fields' => array('EducationProgramme.name'),
				'EducationCycle' => array('fields' => array('EducationCycle.name'))
			)
		));
		$data = $this->find('all', array(
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId
			),
			'order' => array('EducationProgramme.order')
		));

		$this->setVar(compact('data'));
	}
	
	public function getProgrammeOptions($institutionSiteId, $academicPeriodId=null) {
		$conditions = array('InstitutionSiteProgramme.institution_site_id' => $institutionSiteId);
		
		if(!is_null($academicPeriodId)) {
			$conditions['InstitutionSiteProgramme.academic_period_id'] = $academicPeriodId;
		}
		
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('EducationProgramme.id', 'EducationCycle.name', 'EducationProgramme.name'),
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
				)
			),
			'conditions' => $conditions,
			'order' => array('EducationCycle.order', 'EducationProgramme.order')
		));
		
		$list = array();
		foreach($data as $obj) {
			$id = $obj['EducationProgramme']['id'];
			$cycle = $obj['EducationCycle']['name'];
			$programme = $obj['EducationProgramme']['name'];
			$list[$id] = $cycle . ' - ' . $programme;
		}
		return $list;
	}
	
	// used by InstitutionSiteController
	public function getSiteProgrammes($institutionSiteId, $academicPeriodId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array(
				'InstitutionSiteProgramme.id',
				'EducationSystem.name AS education_system_name',
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
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
				'InstitutionSiteProgramme.academic_period_id' => $academicPeriodId,
				'InstitutionSiteProgramme.status' => 1
			),
			'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
		));
		return $data;
	}
	
	public function getProgrammesForEdit($institutionSiteId, $academicPeriodId) {
		$this->EducationProgramme->unbindModel(array('belongsTo' => array('EducationCertification', 'EducationFieldOfStudy')));
		$data = $this->EducationProgramme->find('all', array(
			'recursive' => 0,
			'fields' => array('*'),
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'type' => 'LEFT',
					'conditions' => array(
						'InstitutionSiteProgramme.education_programme_id = EducationProgramme.id',
						'InstitutionSiteProgramme.academic_period_id = ' . $academicPeriodId,
						'InstitutionSiteProgramme.institution_site_id = ' . $institutionSiteId
					)
				)/*,
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array(
						'EducationLevel.id = EducationCycle.education_level_id'
					)
				)*/
			),
			'order' => array('InstitutionSiteProgramme.id DESC', 'EducationCycle.order', 'EducationProgramme.order')
			//'conditions' => array('')
		));
		$this->EducationProgramme->bindModel(array('belongsTo' => array('EducationCertification', 'EducationFieldOfStudy')));
		//pr($data);
		return $data;
	}
	
	public function getSiteProgrammeOptions($institutionSiteId, $academicPeriodId, $withCycle=true) {
		$data = array();
		if($withCycle) {
			$list = $this->getSiteProgrammes($institutionSiteId, $academicPeriodId);
			foreach($list as &$obj) {
				$data[$obj['education_programme_id']] = $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'];
			}
		} else {
			$data = $this->find('list', array(
				'recursive' => -1,
				'fields' => array('EducationProgramme.id AS education_programme_id', 'EducationProgramme.name AS education_programme_name'),
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
				'conditions' => array(
					'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
					'InstitutionSiteProgramme.academic_period_id' => $academicPeriodId,
					'InstitutionSiteProgramme.status' => 1
				),
				'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
			));
		}
		return $data;
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
			'conditions' => array(
				'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
				'InstitutionSiteProgramme.academic_period_id' => $academicPeriodId,
				'InstitutionSiteProgramme.status' => 1
			),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		return $data;
	}
	
	public function getProgrammeCountByInstitutionSite($institutionSiteId) {
		$count = $this->find('count', array(
			'conditions' => array('InstitutionSiteProgramme.institution_site_id' => $institutionSiteId)
		));
		
		return $count;
	}
	
	public function getAllProgBySystemId($educSystemId,$arrExclude = Array()){
		$exclude = (count($arrExclude) > 0 )?" AND education_programmes.id NOT IN (".implode(",",$arrExclude).")":"";
		  $arr = $this->query("
		  SELECT * FROM `education_programmes` 
		  LEFT JOIN education_cycles 
		  ON  education_cycles.id = education_programmes.education_cycle_id
		  LEFT JOIN education_levels 
		  ON  education_levels.id = education_cycles.education_level_id
		  LEFT JOIN education_field_of_studies 
		  ON  education_field_of_studies.id = education_programmes.education_field_of_study_id
		  LEFT JOIN education_certifications 
		  ON  education_certifications.id = education_programmes.education_certification_id
		  LEFT JOIN education_systems
		  ON  education_systems.id = education_levels.education_system_id
		  WHERE education_cycle_id in 
			(SELECT id FROM education_cycles WHERE education_level_id in 
				(SELECT id from education_levels WHERE education_system_id = $educSystemId)) $exclude");
		 
		 return $arr;
	   
	}

	// Required by Yearbook, summary by element and level
	public function calculateTotalSitesPerEducationCycle($year) {

		$startDate = mktime(0,0,0,1,1,$year);
		$endDate = mktime(0,0,0,12,31,$year);

		$options['fields'] = array(
			'EducationProgramme.education_cycle_id',
			'COUNT(InstitutionSite.id) as TotalInstitutionSites'
		);

		$options['group'] = array('EducationProgramme.education_cycle_id');
		$options['conditions'] = array(array('InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('EducationProgramme.education_cycle_id' => NULL, 'InstitutionSite.date_closed' => NULL, 'InstitutionSite.date_closed !=' => "0000-00-00"));

		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		// massage data
		foreach ($values as $k => $v) {
			$eduCycleId = $v['education_cycle_id'];
			$data[$eduCycleId] = $v['TotalInstitutionSites'];
		}

		return $data;
	}

	// Required by Yearbook, schools per level and provides
	public function calculateTotalSchoolsPerLevel($year, $eduCycleId) {

		$startDate = mktime(0,0,0,1,1,$year);
		$endDate = mktime(0,0,0,12,31,$year);
		$this->bindModel(array('hasOne' => array(
			'InstitutionSiteProvider' =>
			array(
				'className'			  => 'InstitutionSiteProvider',
				'joinTable'			  => 'institution_site_providers',
				'foreignKey' => false,
				'dependent'	=> false,
				'conditions' => array('InstitutionSite.institution_site_provider_id = InstitutionSiteProvider.id '),
			),
			'EducationCycle' =>
			array(
				'className'			  => 'EducationCycle',
				'joinTable'			  => 'education_cycles',
				'foreignKey' => false,
				'dependent'	=> false,
				'conditions' => array(' EducationProgramme.education_cycle_id = EducationCycle.id '),
			)
		)));

		$options['fields'] = array(
			'InstitutionSiteProvider.id as ProviderId',
			'InstitutionSiteProvider.name as ProviderName',
			'EducationCycle.id as CycleId',
			'EducationCycle.name as CycleName',
			'COUNT(InstitutionSite.id) as TotalInstitutionSites'
		);

		$options['group'] = array('InstitutionProvider.id','EducationProgramme.education_cycle_id');
		// $options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'InstitutionSite.date_opened >=' => date('Y-m-d', $startDate), 'InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('EducationProgramme.education_cycle_id'=>null));
		$options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('EducationProgramme.education_cycle_id'=>null, 'InstitutionSite.date_closed' => NULL, 'InstitutionSite.date_closed !=' => "0000-00-00"));
		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		return $values;
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
	
	public function programmesGradeList($controller, $params) {
		$controller->layout = 'ajax';
		$this->render = false;
		$programmeId = $controller->params->query['programmeId'];
		$exclude = $controller->params->query['exclude'];
		$gradeOptions = $controller->EducationGrade->getGradeOptions($programmeId, $exclude);
		
		$controller->set(compact('gradeOptions'));
		$controller->render('/Elements/programmes/grade_options');
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
	
	public function getInstitutionAcademicPeriodOptions($institutionSiteId) {
		$options = array(
			'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
			'InstitutionSiteProgramme.status' => 1,
			'AcademicPeriod.available' => 1,
			'AcademicPeriod.parent_id >' => 0
		);
		$list = $this->getAcademicPeriodOptions($options);
		
		return $list;
	}
}
