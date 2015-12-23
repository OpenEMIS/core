<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class AssessmentItemResultsTable extends AppTable {
	public $selectedPeriod;
	public $assessmentId;
	
	// public $actsAs = array(
	// 	'ControllerAction',
	// 	'Excel'
	// );

	public function initialize(array $config) {
		// $this->table('assessment_results');
        parent::initialize($config);
		
		$this->belongsTo('AssessmentItems', ['className' => 'Institution.AssessmentItems']);
		$this->belongsTo('AssessmentResultTypes', ['className' => 'Institution.AssessmentResultTypes']);
		
		// public $belongsTo = array(
		// 	'AssessmentItem',
		// 	'AssessmentResultType'
		// );

	}
	
	// public function getResultsByStudent($studentId, $institutionId=0) {
	// 	$fields = array(
	// 		'EducationSubject.code', 'EducationSubject.name', 'EducationGrade.id', 'EducationGrade.name', 'EducationProgramme.name',
	// 		'AssessmentItemResult.marks', 'AssessmentResultType.name', 'AssessmentItemType.id', 'AssessmentItemType.name'
	// 	);
		
	// 	$joins = array(
	// 		array(
	// 			'table' => 'assessment_items',
	// 			'alias' => 'AssessmentItem',
	// 			'conditions' => array('AssessmentItem.id = AssessmentItemResult.assessment_item_id')
	// 		),
	// 		array(
	// 			'table' => 'assessment_item_types',
	// 			'alias' => 'AssessmentItemType',
	// 			'conditions' => array('AssessmentItemType.id = AssessmentItem.assessment_item_type_id')
	// 		),
	// 		array(
	// 			'table' => 'field_option_values',
	// 			'alias' => 'AssessmentResultType',
	// 			'conditions' => array('AssessmentResultType.id = AssessmentItemResult.assessment_result_type_id')
	// 		),
	// 		array(
	// 			'table' => 'education_grades_subjects',
	// 			'alias' => 'EducationGradeSubject',
	// 			'conditions' => array('EducationGradeSubject.id = AssessmentItem.education_grade_subject_id')
	// 		),
	// 		array(
	// 			'table' => 'education_subjects',
	// 			'alias' => 'EducationSubject',
	// 			'conditions' => array('EducationSubject.id = EducationGradeSubject.education_subject_id')
	// 		),
	// 		array(
	// 			'table' => 'education_grades',
	// 			'alias' => 'EducationGrade',
	// 			'conditions' => array('EducationGrade.id = EducationGradeSubject.education_grade_id')
	// 		),
	// 		array(
	// 			'table' => 'education_programmes',
	// 			'alias' => 'EducationProgramme',
	// 			'conditions' => array('EducationProgramme.id = EducationGrade.education_programme_id')
	// 		)
	// 	);
		
	// 	$conditions = array('AssessmentItemResult.student_id' => $studentId);
		
	// 	if($institutionId==0) {
	// 		$joins[] = array(
	// 			'table' => 'institutions',
	// 			'alias' => 'Institution',
	// 			'conditions' => array('Institution.id = AssessmentItemResult.institution_id')
	// 		);
			
	// 		$fields[] = 'Institution.name';
	// 	} else {
	// 		$conditions['AssessmentItemResult.institution_id'] = $institutionId;
	// 	}
		
	// 	$data = $this->find('all', array(
	// 		'recursive' => -1,
	// 		'fields' => $fields,
	// 		'joins' => $joins,
	// 		'conditions' => $conditions,
	// 		'order' => array('EducationProgramme.order', 'EducationGrade.order', 'EducationSubject.order')
	// 	));
		
	// 	return $data;
	// }
	
	// public function groupItemResults($data) {
	// 	$results = array();
		
	// 	foreach($data as $obj) {
	// 		$gradeId = $obj['EducationGrade']['id'];
	// 		$assessmentId = $obj['AssessmentItemType']['id'];
	// 		if(!array_key_exists($gradeId, $results)) {
	// 			$results[$gradeId] = array(
	// 				'name' => $obj['EducationProgramme']['name'] . ' - ' . $obj['EducationGrade']['name'], 
	// 				'assessments' => array($assessmentId => array(
	// 					'name' => $obj['AssessmentItemType']['name'],
	// 					'subjects' => array()
	// 				))
	// 			);
	// 		} else {
	// 			if(!array_key_exists($assessmentId, $results[$gradeId]['assessments'])) {
	// 				$results[$gradeId]['assessments'][$assessmentId] = array(
	// 					'name' => $obj['AssessmentItemType']['name'],
	// 					'subjects' => array()
	// 				);
	// 			}
	// 		}
	// 		$results[$gradeId]['assessments'][$assessmentId]['subjects'][] = array(
	// 			'code' => $obj['EducationSubject']['code'],
	// 			'name' => $obj['EducationSubject']['name'],
	// 			'marks' => $obj['AssessmentItemResult']['marks'],
	// 			'grading' => $obj['AssessmentResultType']['name']
	// 		);
	// 	}
	// 	return $results;
	// }
	
	// public function assessments($controller, $params) {
	// 	$controller->Navigation->addCrumb('Assessments');

	// 	$academicPeriodOptions = $this->AssessmentItem->AssessmentItemType->getAcademicPeriodListForAssessments($controller->institutionId);
	// 	$defaultAcademicPeriodId = 0;
	// 	if(!empty($academicPeriodOptions)){
	// 		$currentAcademicPeriodId = ClassRegistry::init('AcademicPeriod')->getCurrent();
	// 		if(!empty($currentAcademicPeriodId) && array_key_exists($currentAcademicPeriodId, $academicPeriodOptions)){
	// 			$defaultAcademicPeriodId = $currentAcademicPeriodId;
	// 		}else{
	// 			$defaultAcademicPeriodId = key($academicPeriodOptions);
	// 		}
	// 	}
		
	// 	$selectedAcademicPeriod = isset($params->pass[0]) ? $params->pass[0] : $defaultAcademicPeriodId;
	// 	$data = array();
	// 	if (empty($academicPeriodOptions)) {
	// 		$controller->Utility->alert($controller->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT'), array('type' => 'info'));
	// 	} else {
	// 		$data = $this->AssessmentItem->AssessmentItemType->getInstitutionAssessmentsByAcademicPeriod($controller->institutionId, $selectedAcademicPeriod);

	// 		if (empty($data)) {
	// 			$controller->Utility->alert($controller->Utility->getMessage('ASSESSMENT_NO_ASSESSMENT'), array('type' => 'info'));
	// 		}
	// 	}
	// 	$controller->set(compact('data', 'academicPeriodOptions', 'selectedAcademicPeriod'));
	// }

	// public function assessmentsResults($controller, $params) {
	// 	if (count($controller->params['pass']) >= 2 && count($controller->params['pass']) <= 4) {
 //            $selectedAcademicPeriod = intval($controller->params['pass'][0]);
 //            $assessmentId = intval($controller->params['pass'][1]);
			
	// 		$selectedClass = 0;
	// 		$selectedItem = 0;
	// 		$data = array();
			
	// 		$AssessmentItemType = ClassRegistry::init('AssessmentItemType');
	// 		$assessmentObj = $AssessmentItemType->findById($assessmentId);
	// 		$assessmentName = $assessmentObj['AssessmentItemType']['name'];
	// 		$educationGradeObj = $AssessmentItemType->getGradeNameByAssessment($assessmentId);
	// 		$educationGradeName = $educationGradeObj[0]['EducationGrade']['name'];
			
	// 		$controller->Navigation->addCrumb('Assessments', array('controller' => 'Institutions', 'action' => 'assessments'));
	// 		$controller->Navigation->addCrumb('Results');
			
 //            if ($selectedAcademicPeriod != 0 && $assessmentId != 0) {
	// 			$classOptions = ClassRegistry::init('InstitutionClass')->getClassListWithAcademicPeriod($controller->institutionId, $selectedAcademicPeriod, $assessmentId);

 //                if(empty($classOptions)){
	// 				$controller->Message->alert('Assessment.result.noClass');
	// 				$itemOptions = array();
	// 			}else {
	// 				$selectedClass = isset($controller->params['pass'][2]) ? $controller->params['pass'][2] : key($classOptions);
	// 				$itemOptions = $this->AssessmentItem->getClassItemList($assessmentId, $selectedClass);
	// 				if (empty($itemOptions)) {
	// 					$controller->Message->alert('Assessment.result.noAssessmentItem');
	// 				}else{
	// 					$selectedItem = isset($controller->params['pass'][3]) ? $controller->params['pass'][3] : key($itemOptions);
					
	// 					$InstitutionClassStudent = ClassRegistry::init('InstitutionClassStudent');
	// 					$data = $InstitutionClassStudent->getStudentAssessmentResults($selectedClass, $selectedItem, $assessmentId);

	// 					if (empty($data)) {
	// 						$controller->Message->alert('Assessment.result.noStudent');
	// 					}
	// 				}
 //                }
				
	// 			$controller->set(compact('classOptions', 'itemOptions', 'selectedClass', 'selectedItem', 'data', 'assessmentId', 'selectedAcademicPeriod', 'assessmentName', 'educationGradeName'));
 //            } else {
	// 			$controller->redirect(array('action' => 'assessments'));
 //            }
 //        } else {
 //            $controller->redirect(array('action' => 'assessments'));
 //        }
	// }
	
	// public function assessmentsResultsEdit($controller, $params) {
	// 	if (count($controller->params['pass']) >= 2 && count($controller->params['pass']) <= 4) {
	// 		$selectedAcademicPeriod = intval($controller->params['pass'][0]);
	// 		$assessmentId = intval($controller->params['pass'][1]);
			
	// 		$selectedClass = 0;
	// 		$selectedItem = 0;
	// 		$data = array();

	// 		$AssessmentItemType = ClassRegistry::init('AssessmentItemType');
	// 		$assessmentObj = $AssessmentItemType->findById($assessmentId);
	// 		$assessmentName = $assessmentObj['AssessmentItemType']['name'];
	// 		$educationGradeObj = $AssessmentItemType->getGradeNameByAssessment($assessmentId);
	// 		$educationGradeName = $educationGradeObj[0]['EducationGrade']['name'];

	// 		$controller->Navigation->addCrumb('Assessments', array('controller' => 'Institutions', 'action' => 'assessments'));
	// 		$controller->Navigation->addCrumb('Results');

	// 		if ($selectedAcademicPeriod != 0 && $assessmentId != 0) {
	// 			$classOptions = ClassRegistry::init('InstitutionClass')->getClassListWithAcademicPeriod($controller->institutionId, $selectedAcademicPeriod, $assessmentId);

	// 			if (empty($classOptions)) {
	// 				$controller->Message->alert('Assessment.result.noClass');
	// 			} else {
	// 				$selectedClass = isset($controller->params['pass'][2]) ? $controller->params['pass'][2] : key($classOptions);
	// 				$itemOptions = $this->AssessmentItem->getClassItemList($assessmentId, $selectedClass);
					
	// 				if (empty($itemOptions)) {
	// 					$controller->Message->alert('Assessment.result.noAssessmentItem');
	// 				}else{
	// 					$selectedItem = isset($controller->params['pass'][3]) ? $controller->params['pass'][3] : key($itemOptions);
	// 				}
	// 			}

	// 			$InstitutionClassStudent = ClassRegistry::init('InstitutionClassStudent');
	// 			$data = $InstitutionClassStudent->getStudentAssessmentResults($selectedClass, $selectedItem, $assessmentId);
				
	// 			$itemId = !empty($selectedItem) ? $selectedItem : 0;
	// 			$assessmentItemResult = $this->AssessmentItem->findById($itemId);
	// 			if(!empty($assessmentItemResult)){
	// 				$assessmentItem = $assessmentItemResult['AssessmentItem'];
	// 				$minMarks = $assessmentItem['min'];
	// 				$maxMarks = $assessmentItem['max'];
	// 			}else{
	// 				$minMarks = 0;
	// 				$maxMarks = 0;
	// 			}
				
	// 			$gradingOptions = $this->AssessmentResultType->getList();
	// 			$controller->set(compact('data', 'gradingOptions'));

	// 			if ($controller->request->is('get')) {
	// 				if (empty($data)) {
	// 					$controller->Message->alert('Assessment.result.noStudent');
	// 				}
	// 			} else {
	// 				if (isset($controller->data['AssessmentItemResult'])) {

	// 					unset($controller->request->data['AssessmentItemResult']['class_id']);
	// 					unset($controller->request->data['AssessmentItemResult']['assessment_item_id']);

	// 					$result = $controller->data['AssessmentItemResult'];
	// 					foreach ($result as $key => &$obj) {
	// 						$obj['assessment_item_id'] = $selectedItem;
	// 						$obj['institution_id'] = $controller->InstitutionId;
	// 					}

	// 					$dataAllValid = true;
	// 					foreach ($result AS $key => $record) {
	// 						if (empty($record['marks']) && empty($record['assessment_result_type_id'])) {
	// 							unset($result[$key]);
	// 						}else{
	// 							if($record['marks'] < 0 || $record['marks'] > $maxMarks){
	// 								$dataAllValid = false;
	// 							}
	// 						}
	// 					}
						
	// 					if(!$dataAllValid){
	// 						$controller->Message->alert('Assessment.result.marksNotValid');
	// 						$controller->request->data = $data;
	// 					}else{
	// 						if (!empty($result)) {
	// 							$this->saveMany($result);
	// 						}
							
	// 						$controller->Utility->alert($controller->Utility->getMessage('SAVE_SUCCESS'));
	// 						$controller->redirect(array('action' => 'assessmentsResults', $selectedAcademicPeriod, $assessmentId, $selectedClass, $selectedItem));
	// 					}
	// 				}
	// 			}

	// 			$controller->set(compact('classOptions', 'itemOptions', 'selectedClass', 'selectedItem', 'assessmentId', 'selectedAcademicPeriod', 'assessmentName', 'educationGradeName', 'minMarks', 'maxMarks'));
	// 		} else {
	// 			$controller->redirect(array('action' => 'assessments'));
	// 		}
	// 	} else {
	// 		$controller->redirect(array('action' => 'assessments'));
	// 	}
	// }
	
	// public function assessmentsToExcel($controller, $params){
	// 	$periodId = isset($params->pass[0]) ? $params->pass[0] : 0;
	// 	$assessmentId = isset($params->pass[1]) ? $params->pass[1] : 0;
		
	// 	$this->excel($periodId, $assessmentId);
	// }
	
	// public function excel($periodId, $assessmentId) {
	// 	$this->selectedPeriod = $periodId;
	// 	$this->assessmentId = $assessmentId;
	// 	parent::excel();
	// }
	
	// public function excelGetFileName() {
	// 	$assessmentId = $this->assessmentId;
	// 	$assessment = ClassRegistry::init('AssessmentItemType')->findById($assessmentId);
	// 	//$modelName = $this->alias;
	// 	$assessmentName = str_replace(' ', '_', $assessment['AssessmentItemType']['name']);
	// 	$fileName = $assessmentName;
	// 	return $fileName;
	// }

	// public function generateSheet($writer) {
	// 	$institutionId = CakeSession::read('Institution.id');
	// 	$periodId = $this->selectedPeriod;
	// 	$assessmentId = $this->assessmentId;
		
	// 	$InstitutionClassStudent = ClassRegistry::init('InstitutionClassStudent');
		
	// 	$commonHeader = array(
	// 		__('OpenEMIS ID'),
	// 		__('First Name'),
	// 		__('Last Name')
	// 	);
	// 	$footer = $this->excelGetFooter();

	// 	$classOptions = ClassRegistry::init('InstitutionClass')->getAssessmentClassList($institutionId, $periodId, $assessmentId);
	// 	foreach($classOptions as $classId => $className){
	// 		$checkList = array();
	// 		$subjectsHeader = array();
			
	// 		$subjectOptions = $this->AssessmentItem->getClassItemList($assessmentId, $classId);
	// 		//pr($subjectOptions);die;
	// 		foreach($subjectOptions as $subjectId => $subjectName){
	// 			$subjectsHeader[] = sprintf('%s %s', $subjectName, __('Marks'));
	// 			$subjectsHeader[] = sprintf('%s %s', $subjectName, __('Grading'));

	// 			$resultData = $InstitutionClassStudent->getStudentAssessmentResults($classId, $subjectId, $assessmentId);
	// 			//pr($resultData);die;
	// 			foreach($resultData as $row){
	// 				$StudentObj = $row['Student'];
	// 				$studentId = $StudentObj['id'];
	// 				$AssessmentItemResultObj = $row['AssessmentItemResult'];
	// 				$AssessmentResultTypeObj = $row['AssessmentResultType'];

	// 				$resultArr = array(
	// 					'marks' => $AssessmentItemResultObj['marks'],
	// 					'grading' => $AssessmentResultTypeObj['name']
	// 				);

	// 				$checkList[$classId][$studentId][$subjectId] = $resultArr;
	// 			}
	// 		}
	// 		//pr($checkList);die;
			
	// 		$studentsData = $InstitutionClassStudent->getStudentsByClassAssessment($classId, $assessmentId);
	// 		//pr($studentsData);
	// 		$sheetData = array();
	// 		foreach($studentsData as $row){
	// 			$dataRow = array();

	// 			$StudentObj = $row['Student'];
	// 			$SecurityUserObj = $row['SecurityUser'];
	// 			$studentId = $StudentObj['id'];
	// 			$dataRow[] = $SecurityUserObj['openemis_no'];
	// 			$dataRow[] = $SecurityUserObj['first_name'];
	// 			$dataRow[] = $SecurityUserObj['last_name'];

	// 			foreach($subjectOptions as $subjectId => $subjectName){
	// 				if(isset($checkList[$classId][$studentId][$subjectId])){
	// 					$dataRow[] = $checkList[$classId][$studentId][$subjectId]['marks'];
	// 					$dataRow[] = $checkList[$classId][$studentId][$subjectId]['grading'];
	// 				}else{
	// 					$dataRow[] = '';
	// 					$dataRow[] = '';
	// 				}
	// 			}

	// 			$sheetData[] = $dataRow;
	// 		}
			
	// 		$header = array_merge($commonHeader, $subjectsHeader);
	// 		//pr($header);die;
	// 		$writer->writeSheetRow($className, $header);
			
	// 		foreach ($sheetData as $sheetRow) {
	// 			$writer->writeSheetRow($className, $sheetRow);
	// 		}

	// 		$writer->writeSheetRow($className, array(''));
	// 		$writer->writeSheetRow($className, $footer);
	// 	}
	// }
	
}
