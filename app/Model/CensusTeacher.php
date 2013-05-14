<?php
/*
@OPENEMIS LICENSE LAST UPDATED ON 2013-05-14

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

class CensusTeacher extends AppModel {
	public $belongsTo = array(
		'InstitutionSiteProgramme',
		'SchoolYear',
		'EducationGrade'
	);
	
	public function getTeacherId($institutionSiteId, $yearId) {
		$data = $this->find('list', array(
			'fields' => array('CensusTeacher.id'),
			'conditions' => array('CensusTeacher.institution_site_id' => $institutionSiteId, 'CensusTeacher.school_year_id' => $yearId)
		));
		return $data;
	}
	
	public function mergeSingleGradeData(&$class, $data) {
		foreach($class as $key => &$obj) {
			foreach($obj['education_grades'] as $gradeId => &$grade) {
				$male = 0;
				$female = 0;
				$source = 0;
				foreach($data as $value) {
					if($value['education_grade_id'] == $gradeId 
					&& $value['education_programme_id'] == $obj['education_programme_id']) {
						$male = $value['male'];
						$female = $value['female'];
						$source = $value['source'];
						break;
					}
				}
				$grade = array('name' => $grade, 'male' => $male, 'female' => $female,'source' => $source);
			}
		}
	}
	
	public function getSingleGradeData($institutionSiteId, $yearId) {
		$this->formatResult = true;
		$data = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'CensusTeacher.id',
				'CensusTeacher.male',
				'CensusTeacher.female',
				'CensusTeacher.source',
				'EducationProgramme.id AS education_programme_id',
				"CONCAT(EducationCycle.name, ' - ', EducationProgramme.name) AS education_programme_name",
				'EducationGrade.id AS education_grade_id',
				'EducationGrade.name AS education_grade_name'
			),
			'joins' => array(
				array(
					'table' => 'institution_site_programmes',
					'alias' => 'InstitutionSiteProgramme',
					'conditions' => array(
						'InstitutionSiteProgramme.institution_site_id = CensusTeacher.institution_site_id',
						'InstitutionSiteProgramme.school_year_id = CensusTeacher.school_year_id',
					)
				),
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
				),
				array(
					'table' => 'census_teacher_grades',
					'alias' => 'CensusTeacherGrade',
					'conditions' => array(
						'CensusTeacherGrade.census_teacher_id = CensusTeacher.id',
						'CensusTeacherGrade.education_grade_id = EducationGrade.id'
					)
				)
			),
			'conditions' => array(
				'CensusTeacher.school_year_id' => $yearId,
				'CensusTeacher.institution_site_id' => $institutionSiteId
			),
			'group' => array('CensusTeacher.id HAVING COUNT(CensusTeacherGrade.census_teacher_id) <= 1'),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		return $data;
	}
	
	public function getMultiGradeData($institutionSiteId, $yearId) {
		$list = $this->find('list' , array(
			'recursive' => -1,
			'fields' => array('CensusTeacher.id'),
			'joins' => array(
				array(
					'table' => 'census_teacher_grades',
					'alias' => 'CensusTeacherGrade',
					'conditions' => array('CensusTeacherGrade.census_teacher_id = CensusTeacher.id')
				)
			),
			'conditions' => array(
				'CensusTeacher.institution_site_id' => $institutionSiteId,
				'CensusTeacher.school_year_id' => $yearId
			),
			'group' => array('CensusTeacher.id HAVING COUNT(CensusTeacherGrade.census_teacher_id) > 1')
		));
		
		$gradeList = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'CensusTeacher.id',
				'CensusTeacher.male',
				'CensusTeacher.female',
				'CensusTeacher.source',
				'EducationProgramme.id',
				"CONCAT(EducationCycle.name, ' - ', EducationProgramme.name) AS education_programme_name",
				'EducationGrade.id',
				'EducationGrade.name'
			),
			'joins' => array(
				array(
					'table' => 'census_teacher_grades',
					'alias' => 'CensusTeacherGrade',
					'conditions' => array('CensusTeacherGrade.census_teacher_id = CensusTeacher.id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = CensusTeacherGrade.education_grade_id')
				),
				array(
					'table' => 'education_programmes',
					'alias' => 'EducationProgramme',
					'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
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
				)
			),
			'conditions' => array('CensusTeacher.id' => $list),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		$data = array();
		foreach($gradeList as $obj) {
			$programme = $obj['EducationProgramme'];
			$grade = $obj['EducationGrade'];
			$teacher = $obj['CensusTeacher'];
			
			if(!isset($data[$teacher['id']])) {
				$data[$teacher['id']] = array(
					'male' => $teacher['male'],
					'female' => $teacher['female'],
					'source' => $teacher['source'],
					'programmes' => array(),
					'grades' => array()
				);
			}
			$data[$teacher['id']]['programmes'][] = $obj[0]['education_programme_name'];
			$data[$teacher['id']]['grades'][$grade['id']] = $grade['name'];
		}
		return $data;
	}
	
	public function clean($data, $yearId, $institutionSiteId) {
		$clean = array();
		$gradeList = array();
		// get the current list of census teacher record ids from the database
		$ids = $this->getTeacherId($institutionSiteId, $yearId);		
		foreach($data as $obj) {
			// remove duplicate grades per record
			$grades = array_unique($obj['CensusTeacherGrade']);
			if(array_search($grades, $gradeList, true) === false) { // the multi grade combination must not exists
				$gradeList[] = $grades;
				// reuse the current census class record ids
				$id = current($ids);
				if($id === false) {
					$id = null;
				} else {
					next($ids);
				}
				// build census grades records
				foreach($grades as &$grade) {
					$grade = array('census_teacher_id' => $id, 'education_grade_id' => $grade);
				}
				
				if ((int)$obj['male'] < 0){ $obj['male'] =0; }
				if ((int)$obj['female'] < 0){ $obj['female'] =0; }
				
				$clean[] = array(
					'id' => $id,
					'male' => $obj['male'],
					'female' => $obj['female'],
					'institution_site_id' => $institutionSiteId,
					'school_year_id' => $yearId,
					'CensusTeacherGrade' => $grades
				);
			}
		}
		// Reset all values of male and female for the existing ids
		$this->unbindModel(array('belongsTo' => array_keys($this->belongsTo)), true);
		$this->updateAll(
			array('CensusTeacher.male' => 0, 'CensusTeacher.female' => 0),
			array('CensusTeacher.id' => $ids)
		);
		// Finally, delete all existing census grades records and re-insert them upon saving
		$CensusTeacherGrade = ClassRegistry::init('CensusTeacherGrade');
		$CensusTeacherGrade->deleteAll(array('CensusTeacherGrade.census_teacher_id' => $ids), false);
		return $clean;
	}
	
	public function saveCensusData($data) {
		$CensusTeacherGrade = ClassRegistry::init('CensusTeacherGrade');
		foreach($data as $obj) {
			if(empty($obj['id'])) {
				$this->create();
			}
			$censusGrades = $obj['CensusTeacherGrade'];
			unset($obj['CensusTeacherGrade']);
			$result = $this->save($obj);
			$id = $result['CensusTeacher']['id'];
			foreach($censusGrades as $grade) {
				$grade['census_teacher_id'] = $id;
				$CensusTeacherGrade->save($grade);
			}
		}
	}

	public function calculateTotalTeachersPerEducationCycle($schoolYearId) {

		$this->unbindModel(array('belongsTo' => array('EducationGrade', 'SchoolYear', 'InstitutionSiteProgramme')));
		$this->bindModel(
			array('hasOne' => 
				array(
					'InstitutionSite' =>
		            array(
		                'joinTable'  => 'institution_sites',
						'foreignKey' => false,
		                'conditions' => array(' InstitutionSite.id = CensusTeacher.institution_site_id '),
		            ),
		            'InstitutionSiteProgramme' =>
		            array(
		                'joinTable'  => 'institution_site_programmes',
						'foreignKey' => false,
		                'conditions' => array(' CensusTeacher.institution_site_id = InstitutionSiteProgramme.institution_site_id '),
		            ),
					'EducationProgramme' => array(
	            	'className' => 'EducationProgramme',
	            	'joinTable' => 'education_programmes',
	            	'foreignKey' => false,
					'dependent'  => false,
	                'conditions' => array(' EducationProgramme.id = InstitutionSiteProgramme.education_programme_id '),
	            )),
			));

		$options['fields'] = array(
        	'EducationProgramme.education_cycle_id',
            'SUM(CensusTeacher.male) as TotalMale',
            'SUM(CensusTeacher.female) as TotalFemale'
        );

		$options['group'] = array('EducationProgramme.education_cycle_id');
		$options['conditions'] = array('CensusTeacher.school_year_id' => $schoolYearId);

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
	 * calculate the total number of teachers in a particular area, Required by Yearbook
	 * @return int 	sum of teachers
	 */
	public function calculateTotalTeachersByAreaId($areaId, $schoolYearId) {

		$this->unbindModel(array('belongsTo' => array('EducationGrade', 'SchoolYear', 'InstitutionSiteProgramme')));
		$this->bindModel(
			array('belongsTo' =>
				array(
					'InstitutionSite' => array(
					'className' => 'InstitutionSite',
	                'joinTable'  => 'institution_sites',
					'foreignKey' => false,
	                'conditions' => array(' InstitutionSite.id = CensusTeacher.institution_site_id '),
			    ))
			));

		$options['fields'] = array(
            'SUM(CensusTeacher.male) as TotalMale',
            'SUM(CensusTeacher.female) as TotalFemale',
            'SUM(CensusTeacher.male + CensusTeacher.female) as TotalTeachers'
        );

		// $options['conditions'] = array('AND' => array('InstitutionSite.area_id' => $areaId, 'InstitutionSite.date_opened >=' => date('Y-m-d', $startDate), 'InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('Area.id'=>null));
		// $options['conditions'] = array('CensusTeacher.school_year_id' => $schoolYearId);
		$options['conditions'] = array('AND' => array('CensusTeacher.school_year_id' => $schoolYearId, 'InstitutionSite.area_id' => $areaId, 'NOT' => array('InstitutionSite.area_id' => null)));
		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		$data = ($values[0]['TotalTeachers'] > 0) ? $values[0]['TotalTeachers'] : 0;
		return $data;
	}

	// Required by Yearbook, students per level and providers
	public function calculateTotalTeachersPerLevel($schoolYearId, $eduCycleId) {

		$this->unbindModel(array('belongsTo' => array('EducationGrade', 'SchoolYear', 'InstitutionSiteProgramme')));
		$this->bindModel(array(
			'belongsTo' => array(
				'InstitutionSite' => array(
	                'joinTable'  => 'institution_sites',
					'foreignKey' => false,
	                'conditions' => array(' InstitutionSite.id = CensusTeacher.institution_site_id '),
	            ),
	            'InstitutionSiteProgramme' => array(
	                'joinTable'  => 'institution_site_programmes',
					'foreignKey' => false,
	                'conditions' => array(' CensusTeacher.institution_site_id = InstitutionSiteProgramme.institution_site_id '),
	            ),
	            'Institution' => array(
	                'joinTable'  => 'institutions',
					'foreignKey' => false,
	                'conditions' => array(' Institution.id = InstitutionSite.institution_id '),
	            ),
			),
			'hasOne' => array(
	            'InstitutionProvider' => array(
	                'className' => 'InstitutionProvider',
	                'joinTable' => 'institution_providers',
					'foreignKey' => false,
					'dependent'    => false,
	                'conditions' => array(' Institution.institution_provider_id = InstitutionProvider.id '),
	            ),
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
            ))
		));

        $options['fields'] = array(
        	'InstitutionProvider.id as ProviderId',
        	'InstitutionProvider.name as ProviderName',
        	'EducationCycle.id as CycleId',
        	'EducationCycle.name as CycleName',
        	'SUM(CensusTeacher.male) as TotalMale',
        	'SUM(CensusTeacher.female) as TotalFemale',
        	'SUM(CensusTeacher.female + CensusTeacher.male) as TotalTeachers'
        );

        $options['group'] = array('InstitutionProvider.id','EducationProgramme.education_cycle_id');
        $options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'CensusTeacher.school_year_id' => $schoolYearId), 'NOT' => array('InstitutionProvider.id'=>null));
		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		return $values;
	}

	/**
	 * calculate the total number of students in a particular area, based on the various education cycle, 
	 * Required by Yearbook - Enrolment By Level and Area
	 * @return int 	sum of students
	 */
	public function calculateTotalTeachersPerLevelAndAreaId($schoolYearId, $areaId, $eduCycleId) {
		$this->unbindModel(array('belongsTo' => array('EducationGrade', 'SchoolYear', 'InstitutionSiteProgramme')));
		$this->bindModel(array(
			'belongsTo' =>
				array(
					'InstitutionSite' => array(
	                'joinTable'  => 'institution_sites',
					'foreignKey' => false,
	                'conditions' => array(' InstitutionSite.id = CensusTeacher.institution_site_id '),
			    ),
				'InstitutionSiteProgramme' =>
	            array(
	                'joinTable'  => 'institution_site_programmes',
					'foreignKey' => false,
	                'conditions' => array(' CensusTeacher.institution_site_id = InstitutionSiteProgramme.institution_site_id '),
	            ),
			),
			'hasOne' => array(
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
	                'className'  => 'EducationCycle',
	                'joinTable'  => 'education_cycles',
					'foreignKey' => false,
					'dependent'  => false,
	                'conditions' => array(' EducationProgramme.education_cycle_id = EducationCycle.id '),
	            )
        	)
		));

		$options['fields'] = array(
            'SUM(CensusTeacher.male) as TotalMale',
            'SUM(CensusTeacher.female) as TotalFemale',
            'SUM(CensusTeacher.male + CensusTeacher.female) as TotalTeachers'
        );

		// $options['conditions'] = array('AND' => array('InstitutionSite.area_id' => $areaId, 'InstitutionSite.date_opened >=' => date('Y-m-d', $startDate), 'InstitutionSite.date_opened <=' => date('Y-m-d', $endDate)), 'NOT' => array('Area.id'=>null));
		// $options['conditions'] = array('CensusTeacher.school_year_id' => $schoolYearId);
		$options['group'] = array('EducationProgramme.education_cycle_id');
		$options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'CensusTeacher.school_year_id' => $schoolYearId, 'InstitutionSite.area_id' => $areaId, 'NOT' => array('InstitutionSite.area_id' => null)));
		// $options['conditions'] = array('AND' => array('EducationProgramme.education_cycle_id' => $eduCycleId, 'CensusTeacher.school_year_id' => $schoolYearId, 'NOT' => array('InstitutionSite.area_id' => null)));
		$values = $this->find('all', $options);
		$values = $this->formatArray($values);

		return $values;

	}
}
