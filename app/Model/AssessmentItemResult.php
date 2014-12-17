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

class AssessmentItemResult extends AppModel {
	public $actsAs = array(
		'ControllerAction'
	);
	
	public function getResultsByStudent($studentId, $institutionSiteId=0) {
		$fields = array(
			'EducationSubject.code', 'EducationSubject.name', 'EducationGrade.id', 'EducationGrade.name', 'EducationProgramme.name',
			'AssessmentItemResult.marks', 'AssessmentResultType.name', 'AssessmentItemType.id', 'AssessmentItemType.name'
		);
		
		$joins = array(
			array(
				'table' => 'assessment_items',
				'alias' => 'AssessmentItem',
				'conditions' => array('AssessmentItem.id = AssessmentItemResult.assessment_item_id')
			),
			array(
				'table' => 'assessment_item_types',
				'alias' => 'AssessmentItemType',
				'conditions' => array('AssessmentItemType.id = AssessmentItem.assessment_item_type_id')
			),
			array(
				'table' => 'assessment_result_types',
				'alias' => 'AssessmentResultType',
				'conditions' => array('AssessmentResultType.id = AssessmentItemResult.assessment_result_type_id')
			),
			array(
				'table' => 'education_grades_subjects',
				'alias' => 'EducationGradeSubject',
				'conditions' => array('EducationGradeSubject.id = AssessmentItem.education_grade_subject_id')
			),
			array(
				'table' => 'education_subjects',
				'alias' => 'EducationSubject',
				'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id')
			),
			array(
				'table' => 'education_grades',
				'alias' => 'EducationGrade',
				'conditions' => array('EducationGrade.id = EducationGradeSubject.education_grade_id')
			),
			array(
				'table' => 'education_programmes',
				'alias' => 'EducationProgramme',
				'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
			)
		);
		
		$conditions = array('AssessmentItemResult.student_id' => $studentId);
		
		if($institutionSiteId==0) {
			$joins[] = array(
				'table' => 'institution_sites',
				'alias' => 'InstitutionSite',
				'conditions' => array('InstitutionSite.id = AssessmentItemResult.institution_site_id')
			);
			$joins[] = array(
				'table' => 'institutions',
				'alias' => 'Institution',
				'conditions' => array('Institution.id = InstitutionSite.institution_id')
			);
			$fields[] = 'InstitutionSite.name';
			$fields[] = 'Institution.name';
		} else {
			$conditions['AssessmentItemResult.institution_site_id'] = $institutionSiteId;
		}
		
		$data = $this->find('all', array(
			'recursive' => -1,
			'fields' => $fields,
			'joins' => $joins,
			'conditions' => $conditions,
			'order' => array('EducationProgramme.order', 'EducationGrade.order', 'EducationSubject.order')
		));
		
		return $data;
	}
	
	public function groupItemResults($data) {
		$results = array();
		
		foreach($data as $obj) {
			$gradeId = $obj['EducationGrade']['id'];
			$assessmentId = $obj['AssessmentItemType']['id'];
			if(!array_key_exists($gradeId, $results)) {
				$results[$gradeId] = array(
					'name' => $obj['EducationProgramme']['name'] . ' - ' . $obj['EducationGrade']['name'], 
					'assessments' => array($assessmentId => array(
						'name' => $obj['AssessmentItemType']['name'],
						'subjects' => array()
					))
				);
			} else {
				if(!array_key_exists($assessmentId, $results[$gradeId]['assessments'])) {
					$results[$gradeId]['assessments'][$assessmentId] = array(
						'name' => $obj['AssessmentItemType']['name'],
						'subjects' => array()
					);
				}
			}
			$results[$gradeId]['assessments'][$assessmentId]['subjects'][] = array(
				'code' => $obj['EducationSubject']['code'],
				'name' => $obj['EducationSubject']['name'],
				'marks' => $obj['AssessmentItemResult']['marks'],
				'grading' => $obj['AssessmentResultType']['name']
			);
		}
		return $results;
	}
	
	public function assessments($controller, $params) {
		$controller->Navigation->addCrumb('Assessments');

		$yearOptions = $controller->AssessmentItemType->getYearListForAssessments($controller->institutionSiteId);
		$defaultYearId = 0;
		if(!empty($yearOptions)){
			$currentYearId = ClassRegistry::init('SchoolYear')->getSchoolYearIdByDate(Date('Y-m-d'));
			if(!empty($currentYearId) && array_key_exists($currentYearId, $yearOptions)){
				$defaultYearId = $currentYearId;
			}else{
				$latestYearId = ClassRegistry::init('SchoolYear')->getLatestSchoolYearIdByDate(Date('Y-m-d'), $yearOptions);
				if(!empty($latestYearId) && array_key_exists($latestYearId, $yearOptions)){
					$defaultYearId = $latestYearId;
				}else{
					$defaultYearId = key($yearOptions);
				}
			}
		}
		
		$selectedYear = isset($params->pass[0]) ? $params->pass[0] : $defaultYearId;
		$data = array();
		if (empty($yearOptions)) {
			$controller->Utility->alert($controller->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT'), array('type' => 'info'));
		} else {
			$data = $controller->AssessmentItemType->getInstitutionAssessmentsBySchoolYear($controller->institutionSiteId, $selectedYear);

			if (empty($data)) {
				$controller->Utility->alert($controller->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT'), array('type' => 'info'));
			}
		}
		$controller->set(compact('data', 'yearOptions', 'selectedYear'));
	}

	public function assessmentsResults($controller, $params) {
		if (count($controller->params['pass']) >= 2 && count($controller->params['pass']) <= 4) {
            $selectedYear = intval($controller->params['pass'][0]);
            $assessmentId = intval($controller->params['pass'][1]);
			
			$selectedClass = 0;
			$selectedItem = 0;
			$data = array();
			
			$AssessmentItemType = ClassRegistry::init('AssessmentItemType');
			$assessmentObj = $AssessmentItemType->findById($assessmentId);
			$assessmentName = $assessmentObj['AssessmentItemType']['name'];
			$educationGradeObj = $AssessmentItemType->getGradeNameByAssessment($assessmentId);
			$educationGradeName = $educationGradeObj[0]['EducationGrade']['name'];
			
			$controller->Navigation->addCrumb('Assessments', array('controller' => 'InstitutionSites', 'action' => 'assessments'));
			$controller->Navigation->addCrumb('Results');
			
            if ($selectedYear != 0 && $assessmentId != 0) {
				$classOptions = $controller->InstitutionSiteClass->getClassListWithYear($controller->institutionSiteId, $selectedYear, $assessmentId);

                if(empty($classOptions)){
					$controller->Message->alert('Assessment.result.noClass');
				}else {
					$selectedClass = isset($controller->params['pass'][2]) ? $controller->params['pass'][2] : key($classOptions);
					$itemOptions = $controller->AssessmentItem->getClassItemList($assessmentId, $selectedClass);
					if (empty($itemOptions)) {
						$controller->Message->alert('Assessment.result.noAssessmentItem');
					}else{
						$selectedItem = isset($controller->params['pass'][3]) ? $controller->params['pass'][3] : key($itemOptions);
					
						$InstitutionSiteClassStudent = ClassRegistry::init('InstitutionSiteClassStudent');
						$data = $InstitutionSiteClassStudent->getStudentAssessmentResults($selectedClass, $selectedItem, $assessmentId);

						if (empty($data)) {
							$controller->Message->alert('Assessment.result.noStudent');
						}
					}
                }
				
				$controller->set(compact('classOptions', 'itemOptions', 'selectedClass', 'selectedItem', 'data', 'assessmentId', 'selectedYear', 'assessmentName', 'educationGradeName'));
            } else {
				$controller->redirect(array('action' => 'assessments'));
            }
        } else {
            $controller->redirect(array('action' => 'assessments'));
        }
	}
	
	public function assessmentsResultsEdit($controller, $params) {
		if (count($controller->params['pass']) >= 2 && count($controller->params['pass']) <= 4) {
			$selectedYear = intval($controller->params['pass'][0]);
			$assessmentId = intval($controller->params['pass'][1]);
			
			$selectedClass = 0;
			$selectedItem = 0;
			$data = array();

			$AssessmentItemType = ClassRegistry::init('AssessmentItemType');
			$assessmentObj = $AssessmentItemType->findById($assessmentId);
			$assessmentName = $assessmentObj['AssessmentItemType']['name'];
			$educationGradeObj = $AssessmentItemType->getGradeNameByAssessment($assessmentId);
			$educationGradeName = $educationGradeObj[0]['EducationGrade']['name'];

			$controller->Navigation->addCrumb('Assessments', array('controller' => 'InstitutionSites', 'action' => 'assessments'));
			$controller->Navigation->addCrumb('Results');

			if ($selectedYear != 0 && $assessmentId != 0) {
				$classOptions = $controller->InstitutionSiteClass->getClassListWithYear($controller->institutionSiteId, $selectedYear, $assessmentId);

				if (empty($classOptions)) {
					$controller->Message->alert('Assessment.result.noClass');
				} else {
					$selectedClass = isset($controller->params['pass'][2]) ? $controller->params['pass'][2] : key($classOptions);
					$itemOptions = $controller->AssessmentItem->getClassItemList($assessmentId, $selectedClass);
					
					if (empty($itemOptions)) {
						$controller->Message->alert('Assessment.result.noAssessmentItem');
					}else{
						$selectedItem = isset($controller->params['pass'][3]) ? $controller->params['pass'][3] : key($itemOptions);
					}
				}

				$InstitutionSiteClassStudent = ClassRegistry::init('InstitutionSiteClassStudent');
				$data = $InstitutionSiteClassStudent->getStudentAssessmentResults($selectedClass, $selectedItem, $assessmentId);

				if ($controller->request->is('get')) {
					if (empty($data)) {
						$controller->Message->alert('Assessment.result.noStudent');
					}
					$gradingOptions = $controller->AssessmentResultType->findList(true);

					$controller->set(compact('data', 'gradingOptions'));
				} else {
					if (isset($controller->data['AssessmentItemResult'])) {

						unset($controller->request->data['AssessmentItemResult']['class_id']);
						unset($controller->request->data['AssessmentItemResult']['assessment_item_id']);

						$result = $controller->data['AssessmentItemResult'];
						foreach ($result as $key => &$obj) {
							$obj['assessment_item_id'] = $selectedItem;
							$obj['institution_site_id'] = $controller->institutionSiteId;
						}

						foreach ($result AS $key => $record) {
							if (empty($record['marks']) || empty($record['assessment_result_type_id'])) {
								unset($result[$key]);
							}
						}

						if (!empty($result)) {
							$controller->AssessmentItemResult->saveMany($result);
							$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
						}
					}
					$controller->redirect(array('action' => 'assessmentsResults', $selectedYear, $assessmentId, $selectedClass, $selectedItem));
				}

				$controller->set(compact('classOptions', 'itemOptions', 'selectedClass', 'selectedItem', 'assessmentId', 'selectedYear', 'assessmentName', 'educationGradeName'));
			} else {
				$controller->redirect(array('action' => 'assessments'));
			}
		} else {
			$controller->redirect(array('action' => 'assessments'));
		}
	}
}
