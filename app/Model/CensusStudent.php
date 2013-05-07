<?php
App::uses('AppModel', 'Model');

class CensusStudent extends AppModel {
	public $actsAs = array('Containable');
	public $belongsTo = array(
		'SchoolYear' => array('foreignKey' => 'school_year_id'),
		'EducationGrade' => array('foreignKey' => 'education_grade_id'),
		'StudentCategory'=>array('foreignKey' => 'student_category_id'),
		'InstitutionSite' => array('foreignKey' => 'institution_site_id'),
		'Institution' =>
            array(
                'joinTable'  => 'institutions',
				'foreignKey' => false,
                'conditions' => array(' Institution.id = InstitutionSite.institution_id '),
            )
	);
	
	public function getCensusData($siteId, $yearId, $gradeId, $categoryId) {
		$this->formatResult = true;
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => array('CensusStudent.id', 'CensusStudent.age', 'CensusStudent.male', 'CensusStudent.female', 'CensusStudent.source'),
			'joins' => array(
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array(
						'EducationGrade.id = CensusStudent.education_grade_id'
					)
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array(
						'EducationProgramme.id = EducationGrade.education_programme_id'
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
				'CensusStudent.education_grade_id' => $gradeId,
				'CensusStudent.school_year_id' => $yearId,
				'CensusStudent.student_category_id' => $categoryId,
				'CensusStudent.institution_site_id' => $siteId
			),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'CensusStudent.age')
		));
		return $data;
	}
	
	public function saveCensusData($data, $institutionSiteId) {
		$keys = array();
		$deleted = array();
		
		if(isset($data['deleted'])) {
			 $deleted = $data['deleted'];
			 unset($data['deleted']);
		}
		foreach($deleted as $id) {
			$this->delete($id);
		}
		
		for($i=0; $i<sizeof($data); $i++) {
			$row = $data[$i];
			if($row['age'] > 0 && ($row['male'] > 0 || $row['female'] > 0)) {
				if($row['id'] == 0) {
					$this->create();
				}
				$row['institution_site_id'] = $institutionSiteId;
				$save = $this->save(array('CensusStudent' => $row));
				if($row['id'] == 0) {
					$keys[strval($i+1)] = $save['CensusStudent']['id'];
				}
			} else if($row['id'] > 0 && $row['male'] == 0 && $row['female'] == 0) {
				$this->delete($row['id']);
			}
		}
		return $keys;
	}
	
	public function findListAsSubgroups() {
		$this->formatResult = true;
		$list = $this->find('all', array(
			'fields' => array('CensusStudent.id', 'CensusStudent.education_grade_id', 'CensusStudent.age'),
			'conditions' => array('EducationGrade.visible' => 1),
			'group' => array('CensusStudent.education_grade_id', 'CensusStudent.age')
		));
		
		$ageList = array();
		foreach($list as $obj) {
			$gradeId = $obj['education_grade_id'];
			$age = $obj['age'];
			if(!isset($ageList[$age])) {
				$ageList[$age] = array('grades' => array());
			}
			$ageList[$age]['grades'][] = $gradeId;
		}
		
		return $ageList;
	}

	public function calculateTotalStudentsPerEducationCycle($schoolYearId) {

		$this->unbindModel(array('belongsTo' => array('EducationGrade', 'StudentCategory', 'InstitutionSite', 'Institution', 'SchoolYear')));
		$this->bindModel(
			array('hasOne' => 
				array('EducationProgramme' => array(
	            	'className' => 'EducationProgramme',
	            	'joinTable' => 'education_programmes',
	            	'foreignKey' => false,
					'dependent'  => false,
	                'conditions' => array(' EducationProgramme.id = InstitutionSiteProgramme.education_programme_id '),
	            )),
			));

		$options['fields'] = array(
        	'EducationProgramme.education_cycle_id',
            'SUM(CensusStudent.male) as TotalMale',
            'SUM(CensusStudent.female) as TotalFemale'
        );

		$options['group'] = array('EducationProgramme.education_cycle_id');
		$options['conditions'] = array('CensusStudent.school_year_id' => $schoolYearId);

		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		// massage data
		foreach ($values as $k => $v) {
			$eduCycleId = $v['education_cycle_id'];
			$data[$eduCycleId] = $v['TotalMale'] + $v['TotalFemale'];
		}

		return $data;
	}

	/**
	 * calculate the total number of students in a particular area, Required by Yearbook
	 * @return int 	sum of students
	 */
	public function calculateTotalStudentsByAreaId($areaId, $schoolYearId) {

		$this->unbindModel(array('belongsTo' => array('EducationGrade', 'StudentCategory', 'Institution', 'SchoolYear')));

		$options['fields'] = array(
            'SUM(CensusStudent.male) as TotalMale',
            'SUM(CensusStudent.female) as TotalFemale',
            'SUM(CensusStudent.male + CensusStudent.female) as TotalStudents'
        );

		// $options['conditions'] = array('AND' => array('InstitutionSite.area_id' => $areaId, 'InstitutionSite.date_opened >=' => date('Y-m-d', $startDate), 'InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('Area.id'=>null));
		// $options['conditions'] = array('CensusStudent.school_year_id' => $schoolYearId);
		$options['conditions'] = array('AND' => array('CensusStudent.school_year_id' => $schoolYearId, 'InstitutionSite.area_id' => $areaId, 'NOT' => array('InstitutionSite.area_id' => null)));
		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		$data = ($values[0]['TotalStudents'] > 0) ? $values[0]['TotalStudents'] : 0;
		return $data;
	}


	/*
	 * calculate the total number of students in a particular area, based on the various education cycle, 
	 * Required by Yearbook - Summary By Element and Area
	 * @return int 	sum of students
	 */
	public function calculateTotalStudentsByLevelAndAreaId($areaId, $schoolYearId) {

		$this->unbindModel(array('belongsTo' => array('EducationGrade', 'StudentCategory', 'Institution', 'SchoolYear')));

		$options['fields'] = array(
            'SUM(CensusStudent.male) as TotalMale',
            'SUM(CensusStudent.female) as TotalFemale',
            'SUM(CensusStudent.male + CensusStudent.female) as TotalStudents'
        );

		// $options['conditions'] = array('AND' => array('InstitutionSite.area_id' => $areaId, 'InstitutionSite.date_opened >=' => date('Y-m-d', $startDate), 'InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('Area.id'=>null));
		// $options['conditions'] = array('CensusStudent.school_year_id' => $schoolYearId);
		$options['conditions'] = array('AND' => array('CensusStudent.school_year_id' => $schoolYearId, 'InstitutionSite.area_id' => $areaId, 'NOT' => array('InstitutionSite.area_id' => null)));
		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		$data = ($values[0]['TotalStudents'] > 0) ? $values[0]['TotalStudents'] : 0;
		return $data;
	}

	// Required by Yearbook, students per level and providers
	public function calculateTotalStudentsPerLevel($schoolYearId, $eduCycleId) {

		$this->unbindModel(array('belongsTo' => array('EducationGrade', 'StudentCategory', 'SchoolYear')));
		$this->bindModel(array('hasOne' => array(
			'EducationProgramme' =>
            array(
                'className'              => 'EducationProgramme',
                'joinTable'              => 'education_programmes',
				'foreignKey' => false,
				'dependent'    => false,
                'conditions' => array(' EducationProgramme.id = InstitutionSiteProgramme.education_programme_id '),
            ),
            'EducationCycle' =>
            array(
                'className'              => 'EducationCycle',
                'joinTable'              => 'education_cycles',
				'foreignKey' => false,
				'dependent'    => false,
                'conditions' => array(' EducationProgramme.education_cycle_id = EducationCycle.id '),
            ),
            'InstitutionProvider' => array(
                'className' => 'InstitutionProvider',
                'joinTable' => 'institution_providers',
				'foreignKey' => false,
				'dependent'    => false,
                'conditions' => array(' Institution.institution_provider_id = InstitutionProvider.id '),
            ),
        )));

        $options['fields'] = array(
        	'InstitutionProvider.id as ProviderId',
        	'InstitutionProvider.name as ProviderName',
        	'EducationCycle.id as CycleId',
        	'EducationCycle.name as CycleName',
        	'SUM(CensusStudent.male) as TotalMale',
        	'SUM(CensusStudent.female) as TotalFemale',
        	'SUM(CensusStudent.female + CensusStudent.male) as TotalStudents'
        );

        $options['group'] = array('InstitutionProvider.id','EducationProgramme.education_cycle_id');
        $options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'CensusStudent.school_year_id' => $schoolYearId), 'NOT' => array('InstitutionProvider.id'=>null));
		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		return $values;
	}

	/**
	 * calculate the total number of students in a particular area, based on the various education cycle, 
	 * Required by Yearbook - Enrolment By Level and Area
	 * @return int 	sum of students
	 */
	public function calculateTotalEnrolmentPerLevelAndAreaId($schoolYearId, $areaId, $eduCycleId) {
		$this->unbindModel(array('belongsTo' => array('EducationGrade', 'StudentCategory', 'SchoolYear')));
		$this->bindModel(array('hasOne' => array(
			'Area' =>
            array(
                'className' => 'Area',
                'joinTable' => 'areas',
				'foreignKey' => false,
				'dependent'    => false,
                'conditions' => array(' Area.id = InstitutionSite.area_id '),
            ),
			'EducationProgramme' =>
            array(
                'className' => 'EducationProgramme',
                'joinTable' => 'education_programmes',
				'foreignKey' => false,
				'dependent'    => false,
                'conditions' => array(' EducationProgramme.id = InstitutionSiteProgramme.education_programme_id '),
            ),
            'EducationCycle' =>
            array(
                'className'              => 'EducationCycle',
                'joinTable'              => 'education_cycles',
				'foreignKey' => false,
				'dependent'    => false,
                'conditions' => array(' EducationProgramme.education_cycle_id = EducationCycle.id '),
            )
        )));

		$options['fields'] = array(
            'SUM(CensusStudent.male) as TotalMale',
            'SUM(CensusStudent.female) as TotalFemale',
            'SUM(CensusStudent.male + CensusStudent.female) as TotalStudents'
        );

		$options['group'] = array('EducationProgramme.education_cycle_id');
		$options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'CensusStudent.school_year_id' => $schoolYearId, 'InstitutionSite.area_id' => $areaId, 'NOT' => array('InstitutionSite.area_id' => null)));
		// $options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'CensusStudent.school_year_id' => $schoolYearId, 'NOT' => array('InstitutionSite.area_id' => null)));
		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		return $values;

	}

}
