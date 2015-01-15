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
	public $selectedPeriod;
	public $InstitutionSiteId;
	
	public $actsAs = array(
		'ControllerAction',
		'Excel'
	);

	public $belongsTo = array(
		'AssessmentItem',
		'AssessmentResultType'
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
			
			$fields[] = 'InstitutionSite.name';
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

		$yearOptions = $this->AssessmentItem->AssessmentItemType->getYearListForAssessments($controller->institutionSiteId);
		$defaultYearId = 0;
		if(!empty($yearOptions)){
			$currentYearId = ClassRegistry::init('SchoolYear')->getCurrent();
			if(!empty($currentYearId) && array_key_exists($currentYearId, $yearOptions)){
				$defaultYearId = $currentYearId;
			}else{
				$defaultYearId = key($yearOptions);
			}
		}
		
		$selectedYear = isset($params->pass[0]) ? $params->pass[0] : $defaultYearId;
		$data = array();
		if (empty($yearOptions)) {
			$controller->Utility->alert($controller->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT'), array('type' => 'info'));
		} else {
			$data = $this->AssessmentItem->AssessmentItemType->getInstitutionAssessmentsBySchoolYear($controller->institutionSiteId, $selectedYear);

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
				$classOptions = ClassRegistry::init('InstitutionSiteClass')->getClassListWithYear($controller->institutionSiteId, $selectedYear, $assessmentId);

                if(empty($classOptions)){
					$controller->Message->alert('Assessment.result.noClass');
				}else {
					$selectedClass = isset($controller->params['pass'][2]) ? $controller->params['pass'][2] : key($classOptions);
					$itemOptions = $this->AssessmentItem->getClassItemList($assessmentId, $selectedClass);
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
				$classOptions = ClassRegistry::init('InstitutionSiteClass')->getClassListWithYear($controller->institutionSiteId, $selectedYear, $assessmentId);

				if (empty($classOptions)) {
					$controller->Message->alert('Assessment.result.noClass');
				} else {
					$selectedClass = isset($controller->params['pass'][2]) ? $controller->params['pass'][2] : key($classOptions);
					$itemOptions = $this->AssessmentItem->getClassItemList($assessmentId, $selectedClass);
					
					if (empty($itemOptions)) {
						$controller->Message->alert('Assessment.result.noAssessmentItem');
					}else{
						$selectedItem = isset($controller->params['pass'][3]) ? $controller->params['pass'][3] : key($itemOptions);
					}
				}

				$InstitutionSiteClassStudent = ClassRegistry::init('InstitutionSiteClassStudent');
				$data = $InstitutionSiteClassStudent->getStudentAssessmentResults($selectedClass, $selectedItem, $assessmentId);
				
				$itemId = !empty($selectedItem) ? $selectedItem : 0;
				$assessmentItemResult = $this->AssessmentItem->findById($itemId);
				if(!empty($assessmentItemResult)){
					$assessmentItem = $assessmentItemResult['AssessmentItem'];
					$minMarks = $assessmentItem['min'];
					$maxMarks = $assessmentItem['max'];
				}else{
					$minMarks = 0;
					$maxMarks = 0;
				}
				
				$gradingOptions = $this->AssessmentResultType->findList(true);

				$controller->set(compact('data', 'gradingOptions'));

				if ($controller->request->is('get')) {
					if (empty($data)) {
						$controller->Message->alert('Assessment.result.noStudent');
					}
				} else {
					if (isset($controller->data['AssessmentItemResult'])) {

						unset($controller->request->data['AssessmentItemResult']['class_id']);
						unset($controller->request->data['AssessmentItemResult']['assessment_item_id']);

						$result = $controller->data['AssessmentItemResult'];
						foreach ($result as $key => &$obj) {
							$obj['assessment_item_id'] = $selectedItem;
							$obj['institution_site_id'] = $controller->institutionSiteId;
						}

						$dataAllValid = true;
						foreach ($result AS $key => $record) {
							if (empty($record['marks']) && empty($record['assessment_result_type_id'])) {
								unset($result[$key]);
							}else{
								if($record['marks'] < 0 || $record['marks'] > $maxMarks){
									$dataAllValid = false;
								}
							}
						}
						
						if(!$dataAllValid){
							$controller->Message->alert('Assessment.result.marksNotValid');
							$controller->request->data = $data;
						}else{
							if (!empty($result)) {
								$this->saveMany($result);
							}
							
							$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
							$controller->redirect(array('action' => 'assessmentsResults', $selectedYear, $assessmentId, $selectedClass, $selectedItem));
						}
					}
				}

				$controller->set(compact('classOptions', 'itemOptions', 'selectedClass', 'selectedItem', 'assessmentId', 'selectedYear', 'assessmentName', 'educationGradeName', 'minMarks', 'maxMarks'));
			} else {
				$controller->redirect(array('action' => 'assessments'));
			}
		} else {
			$controller->redirect(array('action' => 'assessments'));
		}
	}
	
	public function assessmentsToExcel($controller, $params){
		$periodId = isset($params->pass[0]) ? $params->pass[0] : 0;
		
		$this->excel($periodId);
	}
	
	public function excel($periodId) {
		$this->selectedPeriod = $periodId;
		parent::excel();
	}

	public function generateSheet($writer) {
		$institutionSiteId = CakeSession::read('InstitutionSite.id');
		$periodId = $this->selectedPeriod;
		//$SchoolYear = ClassRegistry::init('SchoolYear');
		//$period = $SchoolYear->findById($this->selectedPeriod);
		$assessmentsData = $this->AssessmentItem->AssessmentItemType->getInstitutionAssessmentsBySchoolYear($institutionSiteId, $periodId);
		//pr($assessments);die;
		$assessments = array();
		foreach($assessmentsData as $assessmentItem){
			foreach($assessmentItem['items'] as $item){
				$assessments[] = $item;
			}
		}
		//pr($assessments);die;
		
		$commonHeader = array(
			__('Class'),
			__('OpenEMIS ID'),
			__('First Name'),
			__('Last Name')
		);
		
		foreach($assessments as $assessment){
			$assessmentId = $assessment['id'];
			$assessmentName = $assessment['name'];
			
			$subjectsHeader = array();
			$classOptions = ClassRegistry::init('InstitutionSiteClass')->getClassListWithYear($institutionSiteId, $periodId, $assessmentId);
			foreach($classOptions as $classId => $className){
				$subjectOptions = $this->AssessmentItem->getClassItemList($assessmentId, $classId);
				foreach($subjectOptions as $subjectId => $subjectName){
					$subjectsHeader[] = sprintf('%s %s', $subjectName, __('Marks'));
					$subjectsHeader[] = sprintf('%s %s', $subjectName, __('Grading'));
				}
			}
			
			$header = array_merge($commonHeader, $subjectsHeader);
			pr($header);die;
			$writer->writeSheetRow($assessmentName, $header);
		}
		
		
		
//		$startDate = $period['SchoolYear']['start_date'];
//		$endDate = $period['SchoolYear']['end_date'];
//
//		$months = $this->controller->generateMonthsByDates($startDate, $endDate);
//		//pr($months);
//		$footer = $this->excelGetFooter();
//		
//		foreach($months as $month){
//			$monthInString = $month['month']['inString'];
//			$monthInNumber = $month['month']['inNumber'];
//			$year = $month['year'];
//			
//			$days = $this->controller->generateDaysOfMonth($year, $monthInNumber, $startDate, $endDate);
//			//pr($days);
//			$headerDays = array();
//			$daysIndex = array();
//			foreach($days as $item){
//				$headerDays[] = sprintf('%s (%s)', $item['day'], $item['weekDay']);
//				$daysIndex[] = $item['date'];
//			}
//			
//			$headerInfo = array(
//				__('Section'),
//				__('OpenEMIS ID'),
//				__('First Name'),
//				__('Last Name')
//			);
//			$header = array_merge($headerInfo, $headerDays);
//			//pr($header);
//			$writer->writeSheetRow($monthInString, $header);
//			
//			$data = $this->getData($daysIndex);
//			//pr($data);die;
//			
//			foreach ($data as $row) {
//				$writer->writeSheetRow($monthInString, $row);
//			}
//			
//			$writer->writeSheetRow($monthInString, array(''));
//			$writer->writeSheetRow($monthInString, $footer);
//		}
	}
//	
//	public function getData($days) {
//		if(count($days) == 0){
//			return null;
//		}else{
//			$monthStartDay = $days[0];
//			//pr($monthStartDay);
//			$monthEndDay = $days[count($days) - 1];
//			//pr($monthEndDay);
//		}
//		//pr($days);die;
//		
//		$InstitutionSiteStudentAbsence = ClassRegistry::init('InstitutionSiteStudentAbsence');
//		
//		$institutionSiteId = $this->Session->read('InstitutionSite.id');
//		$InstitutionSiteSection = ClassRegistry::init('InstitutionSiteSection');
//		$yearId = $this->selectedPeriod;
//		$sections = $InstitutionSiteSection->getSectionListByInstitution($institutionSiteId, $yearId);
//		
//		$InstitutionSiteSectionStudent = ClassRegistry::init('InstitutionSiteSectionStudent');
//		$data = array();
//		foreach($sections as $sectionId => $sectionName){
//			$studentList = $InstitutionSiteSectionStudent->getSectionSutdents($sectionId, $monthStartDay, $monthEndDay);
//			//pr($studentList);die;
//			$absenceData = $InstitutionSiteStudentAbsence->getAbsenceData($institutionSiteId, $yearId, $sectionId, $monthStartDay, $monthEndDay);
//			
//			$absenceCheckList = array();
//			foreach($absenceData AS $absenceUnit){
//				$absenceStudent = $absenceUnit['Student'];
//				$studentId = $absenceStudent['id'];
//				$absenceRecord = $absenceUnit['InstitutionSiteStudentAbsence'];
//				$indexAbsenceDate = date('Y-m-d', strtotime($absenceRecord['first_date_absent']));
//
//				$absenceCheckList[$studentId][$indexAbsenceDate] = $absenceUnit;
//
//				if($absenceRecord['full_day_absent'] == 'Yes' && !empty($absenceRecord['last_date_absent']) && $absenceRecord['last_date_absent'] > $absenceRecord['first_date_absent']){
//					$tempStartDate = date("Y-m-d", strtotime($absenceRecord['first_date_absent']));
//					$formatedLastDate = date("Y-m-d", strtotime($absenceRecord['last_date_absent']));
//					
//					while($tempStartDate <= $formatedLastDate){
//						$stampTempDate = strtotime($tempStartDate);
//						$tempIndex = date('Y-m-d', $stampTempDate);
//
//						$absenceCheckList[$studentId][$tempIndex] = $absenceUnit;
//
//						$stampTempDateNew = strtotime('+1 day', $stampTempDate);
//						$tempStartDate = date("Y-m-d", $stampTempDateNew);
//					}
//				}
//			}
//			//pr($absenceCheckList);die;
//			
//			foreach ($studentList as $student){
//				$studentObj = $student['Student'];
//				$studentId = $studentObj['id'];
//				//$studentName = sprintf('%s %s %s', $studentObj['first_name'], $studentObj['middle_name'], $studentObj['last_name']);
//				
//				$row = array();
//				$row[] = $sectionName;
//				$row[] = $studentObj['identification_no'];
//				$row[] = $studentObj['first_name'];
//				$row[] = $studentObj['last_name'];
//				
//				foreach ($days as $index){
//					if (isset($absenceCheckList[$studentId][$index])) {
//						$absenceObj = $absenceCheckList[$studentId][$index]['InstitutionSiteStudentAbsence'];
//						if ($absenceObj['full_day_absent'] !== 'Yes') {
//							$startTimeAbsent = $absenceObj['start_time_absent'];
//							$endTimeAbsent = $absenceObj['end_time_absent'];
//							$timeStr = sprintf(__('Absent') . ' - ' . $absenceObj['absence_type']. ' (%s - %s)' , $startTimeAbsent, $endTimeAbsent);
//							$row[] = $timeStr;
//						}else{
//							$row[] = sprintf('%s %s %s', __('Absent'), __('Full'), __('Day'));
//						}
//					}else{
//						$row[] = __('');
//					}
//				}
//				
//				$data[] = $row;
//			}
//		}
//		//pr($data);die;
//		return $data;
//	}
}
