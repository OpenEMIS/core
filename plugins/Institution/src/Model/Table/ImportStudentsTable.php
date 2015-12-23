<?php
namespace Institution\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use App\Model\Table\AppTable;
use Cake\I18n\Time;

class ImportStudentsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('import_mapping');
		parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'Students']);

	    // register the target table once
	    $this->Institutions = TableRegistry::get('Institution.Institutions');
	    $this->InstitutionStudents = TableRegistry::get('Institution.Students');
	    $this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
	    $this->Students = TableRegistry::get('Security.Users');
	}

	public function beforeAction($event) {
		$session = $this->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$this->institutionId = $session->read('Institution.Institutions.id');
			$this->gradesInInstitution = $this->InstitutionGrades
					->find('list', [
						'keyField' => 'id',
						'valueField' => 'education_grade_id'
					])
					->where([
						$this->InstitutionGrades->aliasField('institution_id') => $this->institutionId
					])
					->toArray();
		} else {
			$this->institutionId = false;
			$this->gradesInInstitution = [];
		}
		$this->systemDateFormat = TableRegistry::get('ConfigItems')->value('date_format');
		$this->admissionAgeMinus = TableRegistry::get('ConfigItems')->value('admission_age_minus');
		$this->admissionAgePlus = TableRegistry::get('ConfigItems')->value('admission_age_plus');
	    $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
		$this->studentStatusId = $StudentStatuses->find()
												->select(['id'])
												->where([$StudentStatuses->aliasField('code') => 'CURRENT'])
												->first()
												->id;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
			'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
			'Model.import.onImportPopulateAcademicPeriodsData' => 'onImportPopulateAcademicPeriodsData',
			'Model.import.onImportPopulateEducationGradesData' => 'onImportPopulateEducationGradesData',
			'Model.import.onImportPopulateStudentStatusesData' => 'onImportPopulateStudentStatusesData',
			'Model.import.onImportPopulateStudentsData' => 'onImportPopulateStudentsData',
			'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes) {
		$columns = new Collection($columns);
		$filtered = $columns->filter(function ($value, $key, $iterator) {
		    return $value == 'student_id';
		});
		$studentIdIndex = key($filtered->toArray());
		$studentId = $sheet->getCellByColumnAndRow($studentIdIndex, $row)->getValue();

		if (in_array($studentId, $importedUniqueCodes->getArrayCopy())) {
			$tempRow['duplicates'] = true;
			return true;
		}

		$tempRow['duplicates'] = false;
		$tempRow['entity'] = $this->InstitutionStudents->newEntity();
		$tempRow['student_status_id'] = $this->studentStatusId;
		$tempRow['start_year'] = false;
		$tempRow['end_date'] = false;
		$tempRow['end_year'] = false;
		$tempRow['institution_id'] = $this->institutionId;
	}

	public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
		$importedUniqueCodes[] = $entity->student_id;
	}

	public function onImportPopulateAcademicPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		$modelData = $lookedUpTable->getAvailableAcademicPeriods(false);
		$translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
		$startDateLabel = $this->getExcelLabel($lookedUpTable, 'start_date');
		$endDateLabel = $this->getExcelLabel($lookedUpTable, 'end_date');
		$data[$sheetName]['formats'] = [
			$translatedReadableCol=>'string',
			$startDateLabel.'(Y-M-D)'=>'date',
			$endDateLabel.'(Y-M-D)'=>'date',
			$translatedCol=>'string'
		];
		if (!empty($modelData)) {
			foreach($modelData as $row) {
				$date = $row->start_date;
				$data[$sheetName]['data'][] = [
					$row->name,
					$row->start_date->format('Y-m-d H:i:s'),
					$row->end_date->format('Y-m-d H:i:s'),
					$row->$lookupColumn
				];
			}
		}
	}

	public function onImportPopulateEducationGradesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		$modelData = $lookedUpTable->find('all')
								->contain(['EducationProgrammes'])
								->select(['code', 'name', 'EducationProgrammes.name'])
								->where([
									$lookedUpTable->aliasField('visible').' = 1'
								])
								->order([
									$lookupModel.'.order',
									$lookupModel.'.education_programme_id'
								])
								->where([
									$lookedUpTable->aliasField('id').' IN' => $this->gradesInInstitution
								]);
		$programmeHeader = $this->getExcelLabel($lookedUpTable, 'education_programme_id');
		$translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
		$data[$sheetName][] = [$programmeHeader, $translatedReadableCol, $translatedCol];
		if (!empty($modelData)) {
			foreach($modelData->toArray() as $row) {
				$data[$sheetName][] = [
					$row->education_programme->name,
					$row->name,
					$row->$lookupColumn
				];
			}
		}
	}
	
	/**
	 * [onImportPopulateStudentsData description]
	 *
	 * Currently, this function populates all students in the system and then filter them if they are not attached to any school.
	 * It should be improved to populate students not attached to any school from the query instead of filtering after querying.
	 * 
	 * @todo improved to populate students not attached to any school from the query instead of filtering after querying
	 */
	public function onImportPopulateStudentsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		$modelData = $lookedUpTable->find('all')
								->contain([
									'Institutions'
								])
								->select(['id', 'first_name', 'middle_name', 'third_name', 'last_name', $lookupColumn])
								->where([
									$lookedUpTable->aliasField('is_student').' = 1',
									$lookedUpTable->aliasField('status').' = 1',
								])
								;
		$nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
		$columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
		$data[$sheetName][] = [
			$nameHeader,
			$columnHeader
		];
		if (!empty($modelData)) {
			foreach($modelData->toArray() as $row) {
				if (count($row->institutions)==0) {
					$data[$sheetName][] = [
						$row->name,
						$row->$lookupColumn
					];
				}
			}
		}
	}

	public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
		if (empty($tempRow['student_id'])) {
			return false;
		}

		if (!$this->institutionId) {
			$tempRow['duplicates'] = __('No active institution.');
			return false;
		}
		$tempRow['institution_id'] = $this->institutionId;

		$institutionStudent = $this->InstitutionStudents->find()->where([
			$this->InstitutionStudents->aliasfield('student_id') => $tempRow['student_id'],
			$this->InstitutionStudents->aliasfield('institution_id') => $this->institutionId
		])->first();
		if ($institutionStudent) {
			$tempRow['duplicates'] = __('Student is already enrolled in this institution.');
			return false;
		}
		unset($institutionStudent);

		if (empty($tempRow['start_date'])) {
			$tempRow['duplicates'] = __('No start date specified.');
			return false;
		}

		$period = $this->getAcademicPeriodByStartDate($tempRow['start_date']->format('Y-m-d'));
		if (!$period) {
			$tempRow['duplicates'] = __('No matching academic period.');
			return false;
		}
		if ($period->id != $tempRow['academic_period_id']) {
			$tempRow['duplicates'] = __('Start date is not within selected academic period.');
		}
		$tempRow['start_year'] = $period->start_year;
		$tempRow['end_date'] = $period->end_date;
		$tempRow['end_year'] = $period->end_year;

		$grades = array_flip($this->gradesInInstitution);
		$institutionGrade = $this->InstitutionGrades
								->find()
								->contain('EducationGrades.EducationProgrammes.EducationCycles')
								->where([$this->InstitutionGrades->aliasField('id') => $grades[$tempRow['education_grade_id']]])
								;
		if (!$institutionGrade) {
			$tempRow['duplicates'] = __('No matching education grade.');
			return false;
		}
		$institutionGrade = $institutionGrade->first();
		$gradeStartDate = $institutionGrade->start_date->toUnixString();
		$gradeEndDate = !empty($institutionGrade->end_date) ? $institutionGrade->end_date->toUnixString() : '';
		$periodStartDate = $period->start_date->toUnixString();
		$periodEndDate = $period->end_date->toUnixString();
		if (!empty($gradeEndDate) && $gradeEndDate < $periodEndDate) {
			$tempRow['duplicates'] = __('Selected education grade will end before academic period ends.');
			return false;
		}
		if ($gradeStartDate > $periodStartDate) {
			$tempRow['duplicates'] = __('Selected education grade start date should be before academic period starts.');
			return false;
		}

		$student = $this->Students->get($tempRow['student_id']);
		if (!$student) {
			$tempRow['duplicates'] = __('No such student in the system.');
			return false;
		}
		if (empty($student->date_of_birth)) {
			$tempRow['duplicates'] = __('Student\'s date of birth is empty. Please correct it at Directory page.');
			return false;
		}

		$admissionAge = $institutionGrade->education_grade->education_programme->education_cycle->admission_age;
		$ageOfStudent = Time::fromNow($student->date_of_birth);
		$ageOfStudent = $ageOfStudent->y;
		$enrolmentMinimumAge = $admissionAge - $this->admissionAgeMinus;
		$enrolmentMaximumAge = $admissionAge + $this->admissionAgePlus;

		if ($ageOfStudent > $enrolmentMaximumAge) {
			$tempRow['duplicates'] = __('Student\'s age is more than the acceptable age for this grade.');
			return false;
		}
		if ($ageOfStudent < $enrolmentMinimumAge) {
			$tempRow['duplicates'] = __('Student\'s age is less than the acceptable age for this grade.');
			return false;
		}
		
		return true;
	}
}
