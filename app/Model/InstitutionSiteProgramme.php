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
		'ControllerAction2',
		'ReportFormat' => array(
			'supportedFormats' => array('csv')
		),
		'SchoolYear'
	);
	
	public $belongsTo = array(
		'SchoolYear',
		'InstitutionSite',
		'EducationProgramme'
	);
	
	public $virtualFields = array(
		'name' => "SELECT `education_programmes`.`name` from `education_programmes` WHERE `education_programmes`.`id` = InstitutionSiteProgramme.education_programme_id"
	);
	
	public $reportMapping = array(
		1 => array(
			'fields' => array(
				'SchoolYear' => array(
					'name' => 'School Year'
				),
				'EducationProgramme' => array(
					'name' => 'Programme'
				),
				'InstitutionSiteProgramme' => array(
					'system_cycle' => 'System - Cycle'
				)
			),
			'fileName' => 'Report_Programme_List'
		)
	);
	
	public function beforeAction() {
		parent::beforeAction();
		$this->Navigation->addCrumb('Programmes');
	}
	
	public function index($selectedYear=0) {
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$yearOptions = $this->SchoolYear->find('list', array('conditions' => array('SchoolYear.visible' => 1), 'order' => array('SchoolYear.order')));
		
		if ($selectedYear == 0) {
			$selectedYear = key($yearOptions);
		}
		$this->contain(array(
			'EducationProgramme' => array(
				'fields' => array('EducationProgramme.name'),
				'EducationCycle' => array('fields' => array('EducationCycle.name'))
			)
		));
		$data = $this->find('all', array(
			'conditions' => array(
				'InstitutionSiteProgramme.status' => 1,
				'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
				'InstitutionSiteProgramme.school_year_id' => $selectedYear
			),
			'order' => array('EducationProgramme.order')
		));
		$this->setVar(compact('yearOptions', 'selectedYear', 'data'));
	}
	
	public function edit($selectedYear=0) {
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		if ($this->request->is('get')) {
			$yearOptions = $this->SchoolYear->find('list', array('conditions' => array('SchoolYear.visible' => 1), 'order' => array('SchoolYear.order')));
			if ($selectedYear == 0) {
				$selectedYear = key($yearOptions);
			}

			$programKeys = array_keys($this->EducationProgramme->getProgrammeOptions());
			$this->EducationProgramme->contain(array(
				'EducationCycle' => array('fields' => array('EducationCycle.name')),
				'InstitutionSiteProgramme' => array(
					'fields' => array('InstitutionSiteProgramme.id', 'InstitutionSiteProgramme.status'),
					'conditions' => array(
						'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
						'InstitutionSiteProgramme.school_year_id' => $selectedYear
					)
				)
			));
			// grabbing all records first...then filtering by status = 1 and if parent is visible
			$data = $this->EducationProgramme->find('all', array(
				'conditions' => array('EducationProgramme.visible' => 1),
				'order' => array('EducationProgramme.order')
			));
			$processedData = array();
			foreach ($data as $key => $value) {
				$InstitutionSiteProgrammeData = $value['InstitutionSiteProgramme'];
				$found = false;
				foreach ($InstitutionSiteProgrammeData as $programDataKey => $programDataVal) {
						if ($programDataVal['status']!='0') {
							$found = true;
						}
				}
				if ($found) {
					array_push($processedData, $value);
					continue;
				}
				if (in_array($value['EducationProgramme']['id'], $programKeys)) {
					array_push($processedData, $value);
					continue;
				}
			}
			$data = $processedData;

			$this->setVar(compact('yearOptions', 'selectedYear', 'data'));
		} else {
			$data = $this->request->data[$this->alias];
			
			foreach($data as $key => $obj) {
				$data[$key]['school_year_id'] = $selectedYear;
				$data[$key]['institution_site_id'] = $institutionSiteId;
			}
			
			if ($this->saveAll($data)) {
				$this->Message->alert('general.edit.success');
			} else {
				$this->Message->alert('general.edit.failed');
			}
			return $this->redirect(array('action' => get_class($this), 'index', $selectedYear));
		}
	}
	
	public function getProgrammeOptions($institutionSiteId, $yearId=null) {
		$conditions = array('InstitutionSiteProgramme.institution_site_id' => $institutionSiteId);
		
		if(!is_null($yearId)) {
			$conditions['InstitutionSiteProgramme.school_year_id'] = $yearId;
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
	public function getSiteProgrammes($institutionSiteId, $yearId) {
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
				'InstitutionSiteProgramme.school_year_id' => $yearId,
				'InstitutionSiteProgramme.status' => 1
			),
			'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
		));
		return $data;
	}
	
	public function getProgrammesForEdit($institutionSiteId, $yearId) {
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
						'InstitutionSiteProgramme.school_year_id = ' . $yearId,
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
	
	public function getSiteProgrammeOptions($institutionSiteId, $yearId, $withCycle=true) {
		$data = array();
		if($withCycle) {
			$list = $this->getSiteProgrammes($institutionSiteId, $yearId);
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
					'InstitutionSiteProgramme.school_year_id' => $yearId,
					'InstitutionSiteProgramme.status' => 1
				),
				'order' => array('EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order')
			));
		}
		return $data;
	}
	
	// used by CensusController, classes/teachers
	public function getProgrammeList($institutionSiteId, $yearId, $withGrades = true) {
		$list = $this->getActiveProgrammes($institutionSiteId, $yearId);
		
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
	
	public function getActiveProgrammes($institutionSiteId, $yearId, $formatResult = false) {
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
				'InstitutionSiteProgramme.school_year_id' => $yearId,
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

		$this->unbindModel(array('belongsTo' => array('Institution')));

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
			'InstitutionProvider' =>
			array(
				'className'			  => 'InstitutionProvider',
				'joinTable'			  => 'institution_providers',
				'foreignKey' => false,
				'dependent'	=> false,
				'conditions' => array(' Institution.institution_provider_id = InstitutionProvider.id '),
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
			'InstitutionProvider.id as ProviderId',
			'InstitutionProvider.name as ProviderName',
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
		
	public function getYearsHaveProgrammes($institutionSiteId){
		$data = $this->find('all', array(
				'recursive' => -1,
				'fields' => array(
					'SchoolYear.id',
					'SchoolYear.name'
				),
				'joins' => array(
						array(
							'table' => 'school_years',
							'alias' => 'SchoolYear',
							'conditions' => array(
								'InstitutionSiteProgramme.school_year_id = SchoolYear.id'
							)
						)
				),
				'conditions' => array('InstitutionSiteProgramme.institution_site_id' => $institutionSiteId),
				'group' => array('InstitutionSiteProgramme.school_year_id'),
				'order' => array('SchoolYear.name DESC')
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

	public function getSiteProgrammeForSelection($institutionSiteId, $yearId) {
		$data = array();
		$list = $this->getSiteProgrammes($institutionSiteId, $yearId);
		foreach($list as &$obj) {
			$data[$obj['id']] = $obj['education_cycle_name'] . ' - ' . $obj['education_programme_name'];
		}
		return $data;
	}

	// used by CensusTextbook
	public function getSiteProgrammeGrades($institutionSiteId, $yearId, $programmeId) {
		$data = array();
		$list = $this->getProgrammeList($institutionSiteId, $yearId);
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

		$yearId = $controller->params->query['yearId'];
		$programmeOptions = $controller->InstitutionSiteProgramme->getSiteProgrammeForSelection($controller->institutionSiteId, $yearId, false);
		
		$controller->set(compact('programmeOptions'));
		$controller->render('/Elements/programmes/programmes_options');
	}
	
	public function reportsGetHeader($args) {
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->getCSVHeader($this->reportMapping[$index]['fields']);
	}

	public function reportsGetData($args) {
		$institutionSiteId = $args[0];
		$index = $args[1];

		if ($index == 1) {
			$options = array();
			$options['recursive'] = -1;
			$options['fields'] = $this->getCSVFields($this->reportMapping[$index]['fields']);
			$options['order'] = array('SchoolYear.name', 'EducationSystem.order', 'EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order');
			$options['conditions'] = array(
				'InstitutionSiteProgramme.institution_site_id' => $institutionSiteId,
				'InstitutionSiteProgramme.status' => 1
			);

			$options['joins'] = array(
				array(
					'table' => 'school_years',
					'alias' => 'SchoolYear',
					'conditions' => array(
						'InstitutionSiteProgramme.school_year_id = SchoolYear.id'
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.education_programme_id = EducationProgramme.id'
					)
				),
				array(
					'table' => 'education_cycles',
					'alias' => 'EducationCycle',
					'conditions' => array(
						'EducationProgramme.education_cycle_id = EducationCycle.id'
					)
				),
				array(
					'table' => 'education_levels',
					'alias' => 'EducationLevel',
					'conditions' => array(
						'EducationCycle.education_level_id = EducationLevel.id'
					)
				),
				array(
					'table' => 'education_systems',
					'alias' => 'EducationSystem',
					'conditions' => array(
						'EducationLevel.education_system_id = EducationSystem.id'
					)
				)
			);
			
			$this->virtualFields = array(
				'system_cycle' => 'CONCAT(EducationSystem.name, " - ", EducationCycle.name)'
			);

			$data = $this->find('all', $options);
			
			return $data;
		}
	}
	
	public function reportsGetFileName($args){
		//$institutionSiteId = $args[0];
		$index = $args[1];
		return $this->reportMapping[$index]['fileName'];
	}
}