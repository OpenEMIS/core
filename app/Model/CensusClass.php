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

class CensusClass extends AppModel {
        public $actsAs = array(
                'ControllerAction'
	);
    
	public $belongsTo = array(
		'SchoolYear',
		'InstitutionSite'
	);
	
	public function getClassId($institutionSiteId, $yearId) {
		$data = $this->find('list', array(
			'fields' => array('CensusClass.id'),
			'conditions' => array('CensusClass.institution_site_id' => $institutionSiteId, 'CensusClass.school_year_id' => $yearId)
		));
		return $data;
	}
	
	public function mergeSingleGradeData(&$class, $data) {
		foreach($class as $key => &$obj) {
			foreach($obj['education_grades'] as $gradeId => &$grade) {
				$classes = 0;
				$seats = null;
				$source = 0;
				foreach($data as $value) {
					if($value['education_grade_id'] == $gradeId 
					&& $value['education_programme_id'] == $obj['education_programme_id']) {
						$classes = $value['classes'];
						$seats = $value['seats'];
						$source = $value['source'];
						break;
					}
				}
				$grade = array('name' => $grade, 'classes' => $classes, 'seats' => $seats,'source' => $source);
			}
		}
	}
	
	public function getSingleGradeData($institutionSiteId, $yearId) {
		$this->formatResult = true;
		$data = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'CensusClass.id',
				'CensusClass.classes',
				'CensusClass.seats',
				'CensusClass.source',
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
						'InstitutionSiteProgramme.institution_site_id = CensusClass.institution_site_id',
						'InstitutionSiteProgramme.school_year_id = CensusClass.school_year_id'
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
					'table' => 'census_class_grades',
					'alias' => 'CensusClassGrade',
					'conditions' => array(
						'CensusClassGrade.census_class_id = CensusClass.id',
						'CensusClassGrade.education_grade_id = EducationGrade.id'
					)
				)
			),
			'conditions' => array(
				'CensusClass.school_year_id' => $yearId,
				'CensusClass.institution_site_id' => $institutionSiteId
			),
			'group' => array('CensusClass.id HAVING COUNT(CensusClassGrade.census_class_id) <= 1'),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order')
		));
		
		return $data;
	}
	
	public function getMultiGradeData($institutionSiteId, $yearId) {
		$classList = $this->find('list' , array(
			'recursive' => -1,
			'fields' => array('CensusClass.id'),
			'joins' => array(
				array(
					'table' => 'census_class_grades',
					'alias' => 'CensusClassGrade',
					'conditions' => array('CensusClassGrade.census_class_id = CensusClass.id')
				)
			),
			'conditions' => array(
				'CensusClass.school_year_id' => $yearId,
				'CensusClass.institution_site_id' => $institutionSiteId
			),
			'group' => array('CensusClass.id HAVING COUNT(CensusClassGrade.census_class_id) > 1')
		));
		
		$gradeList = $this->find('all' , array(
			'recursive' => -1,
			'fields' => array(
				'CensusClass.id',
				'CensusClass.classes',
				'CensusClass.seats',
				'CensusClass.source',
				'EducationProgramme.id',
				"CONCAT(EducationCycle.name, ' - ', EducationProgramme.name) AS education_programme_name",
				'EducationGrade.id',
				'EducationGrade.name'
			),
			'joins' => array(
				array(
					'table' => 'census_class_grades',
					'alias' => 'CensusClassGrade',
					'conditions' => array('CensusClassGrade.census_class_id = CensusClass.id')
				),
				array(
					'table' => 'education_grades',
					'alias' => 'EducationGrade',
					'conditions' => array('EducationGrade.id = CensusClassGrade.education_grade_id')
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
			'conditions' => array('CensusClass.id' => $classList),
			'order' => array('EducationLevel.order', 'EducationCycle.order', 'EducationProgramme.order', 'EducationGrade.order', 'CensusClass.id')
		));
		
		$data = array();
		foreach($gradeList as $obj) {
			$programme = $obj['EducationProgramme'];
			$grade = $obj['EducationGrade'];
			$class = $obj['CensusClass'];
			
			if(!isset($data[$class['id']])) {
				$data[$class['id']] = array(
					'classes' => $class['classes'],
					'seats' => $class['seats'],
					'source' => $class['source'],
					'programmes' => array(),
					'grades' => array()
				);
			}
			$data[$class['id']]['programmes'][] = $obj[0]['education_programme_name'];
			$data[$class['id']]['grades'][$grade['id']] = $grade['name'];
		}
		return $data;
	}
	
	public function clean($data, $yearId, $institutionSiteId, &$duplicate) {
		$clean = array();
		$gradeList = array();
		// get the current list of census class record ids from the database
		$classIds = $this->getClassId($institutionSiteId, $yearId);		
		foreach($data as $obj) {
			// remove duplicate grades per record
			$grades = array_unique($obj['CensusClassGrade']);
			if(array_search($grades, $gradeList, true) === false) { // the multi grade combination must not exists
				$gradeList[] = $grades;
				// reuse the current census class record ids
				$id = current($classIds);
				if($id === false) {
					$id = null;
				} else {
					next($classIds);
				}
				// build CensusClassGrade records
				foreach($grades as &$grade) {
					$grade = array('census_class_id' => $id, 'education_grade_id' => $grade);
				}
				$clean[] = array(
					'id' => $id,
					'classes' => $obj['classes'],
					'seats' => $obj['seats'],
					'institution_site_id' => $institutionSiteId,
					'school_year_id' => $yearId,
					'CensusClassGrade' => $grades
				);
			} else {
				if(!$duplicate) $duplicate = true;
			}
		}
		// Reset all values of classes and seats for the existing class ids
		$this->unbindModel(array('belongsTo' => array_keys($this->belongsTo)), true);
		$this->updateAll(
			array('CensusClass.classes' => 0, 'CensusClass.seats' => null),
			array('CensusClass.id' => $classIds)
		);
		// Finally, delete all existing census class grades records and re-insert them upon saving
		$CensusClassGrade = ClassRegistry::init('CensusClassGrade');
		$CensusClassGrade->deleteAll(array('CensusClassGrade.census_class_id' => $classIds), false);
		return $clean;
	}
	
	public function saveCensusData($data) {
		$CensusClassGrade = ClassRegistry::init('CensusClassGrade');
		foreach($data as $obj) {
			if(empty($obj['id'])) {
				$this->create();
			}
			$censusGrades = $obj['CensusClassGrade'];
			unset($obj['CensusClassGrade']);
			$result = $this->save($obj);
			$id = $result['CensusClass']['id'];
			foreach($censusGrades as $grade) {
				$grade['census_class_id'] = $id;
				$CensusClassGrade->save($grade);
			}
		}
	}
        
        public function classes($controller, $params) {
        $controller->Navigation->addCrumb('Classes');

        $yearList = $controller->SchoolYear->getYearList();
        $selectedYear = isset($controller->params['pass'][0]) ? $controller->params['pass'][0] : key($yearList);
        $displayContent = true;

        $programmeGrades = $controller->InstitutionSiteProgramme->getProgrammeList($controller->institutionSiteId, $selectedYear);
        if (empty($programmeGrades)) {
            $controller->Utility->alert($controller->Utility->getMessage('CENSUS_NO_PROG'), array('type' => 'warn', 'dismissOnClick' => false));
            $displayContent = false;
        } else {
            $singleGradeClasses = $controller->CensusClass->getSingleGradeData($controller->institutionSiteId, $selectedYear);
            $multiGradeData = $controller->CensusClass->getMultiGradeData($controller->institutionSiteId, $selectedYear);
            $singleGradeData = $programmeGrades;
            $controller->CensusClass->mergeSingleGradeData($singleGradeData, $singleGradeClasses);

            $controller->set(compact('singleGradeData', 'multiGradeData'));
        }

        $isEditable = $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear);

        $controller->set(compact('displayContent', 'selectedYear', 'yearList', 'isEditable'));
    }

    public function classesEdit($controller, $params) {
        if ($controller->request->is('get')) {
            $controller->Navigation->addCrumb('Edit Classes');

            $yearList = $controller->SchoolYear->getAvailableYears();
            $selectedYear = $controller->getAvailableYearId($yearList);
            $editable = $controller->CensusVerification->isEditable($controller->institutionSiteId, $selectedYear);
            if (!$editable) {
                $controller->redirect(array('action' => 'classes', $selectedYear));
            } else {
                $displayContent = true;
                $programmeGrades = $controller->InstitutionSiteProgramme->getProgrammeList($controller->institutionSiteId, $selectedYear);
                if (empty($programmeGrades)) {
                    $controller->Utility->alert($controller->Utility->getMessage('CENSUS_NO_PROG'), array('type' => 'warn', 'dismissOnClick' => false));
                    $displayContent = false;
                } else {
                    $programmes = $controller->InstitutionSiteProgramme->getProgrammeList($controller->institutionSiteId, $selectedYear, false);
                    $singleGradeClasses = $controller->CensusClass->getSingleGradeData($controller->institutionSiteId, $selectedYear);
                    $multiGradeData = $controller->CensusClass->getMultiGradeData($controller->institutionSiteId, $selectedYear);
                    $singleGradeData = $programmeGrades;
                    $controller->CensusClass->mergeSingleGradeData($singleGradeData, $singleGradeClasses);

                    $controller->set(compact('programmes', 'programmeGrades', 'singleGradeData', 'multiGradeData'));
                }

                $controller->set(compact('displayContent', 'selectedYear', 'yearList'));
            }
        } else {
            $yearId = $controller->data['CensusClass']['school_year_id'];
            unset($controller->request->data['CensusClass']['school_year_id']);
            $duplicate = false;
            $data = $controller->CensusClass->clean($controller->data['CensusClass'], $yearId, $controller->institutionSiteId, $duplicate);

            if ($duplicate) {
                $controller->Utility->alert($controller->Utility->getMessage('CENSUS_MULTI_DUPLICATE'), array('type' => 'warn', 'dismissOnClick' => false));
            }
            $controller->CensusClass->saveCensusData($data);
            $controller->Utility->alert($controller->Utility->getMessage('CENSUS_UPDATED'));
            $controller->redirect(array('action' => 'classes', $yearId));
        }
    }

    public function classesAddMultiClass($controller, $params) {
        $controller->layout = 'ajax';

        $yearId = $controller->params['pass'][0];
        $programmeGrades = $controller->InstitutionSiteProgramme->getProgrammeList($controller->institutionSiteId, $yearId);
        $programmes = $controller->InstitutionSiteProgramme->getProgrammeList($controller->institutionSiteId, $yearId, false);

        $body = $controller->params->query['tableBody'];
        $i = $controller->params->query['index'];

        $controller->set(compact('i', 'body', 'programmes', 'programmeGrades', 'yearId'));
    }

    public function classesAddMultiGrade($controller, $params) {
        $this->render = false;

        $row = $controller->params->query['row'];
        $index = $controller->params->query['index'];
        $yearId = $controller->params['pass'][0];
        $programmeGrades = $controller->InstitutionSiteProgramme->getProgrammeList($controller->institutionSiteId, $yearId);
        $programmes = $controller->InstitutionSiteProgramme->getProgrammeList($controller->institutionSiteId, $yearId, false);
        $grades = $programmeGrades[current($programmes)]['education_grades'];

        $option = '<option value="%d">%s</option>';
        $programmesHtml = sprintf('<div class="table_cell_row"><select class="form-control" index="%d" url="Census/loadGradeList" onchange="Census.loadGradeList(this)">', $index);
        foreach ($programmes as $id => $value) {
            $programmesHtml .= sprintf($option, $id, $value);
        }
        $programmesHtml .= '</select></div>';

        $gradesHtml = sprintf('<div class="table_cell_row"><select class="form-control" index="%d" name="data[CensusClass][%d][CensusClassGrade][%d]">', $index, $row, $index);
        foreach ($grades as $id => $value) {
            $gradesHtml .= sprintf($option, $id, $value);
        }
        $gradesHtml .= '</select></div>';

        $data = array('programmes' => $programmesHtml, 'grades' => $gradesHtml);
        return json_encode($data);
    }
}