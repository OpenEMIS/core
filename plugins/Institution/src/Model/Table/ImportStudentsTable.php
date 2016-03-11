<?php
namespace Institution\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\Controller\Component;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use App\Model\Table\AppTable;

class ImportStudentsTable extends AppTable {
	private $institutionId;
	private $gradesInInstitution;
	private $systemDateFormat;
	private $studentStatusId;
	private $availableSections;

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
			'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
	    	'Model.Navigation.breadcrumb' => 'onGetBreadcrumb'
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona) {
		$crumbTitle = $this->getHeader($this->alias());
		$Navigation->substituteCrumb($crumbTitle, $crumbTitle);
	}

	public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols) {
		$columns = new Collection($columns);
		$filtered = $columns->filter(function ($value, $key, $iterator) {
		    return $value == 'student_id';
		});
		$studentIdIndex = key($filtered->toArray());
		$studentId = $sheet->getCellByColumnAndRow($studentIdIndex, $row)->getValue();

		if (in_array($studentId, $importedUniqueCodes->getArrayCopy())) {
			$rowInvalidCodeCols['student_id'] = $this->getExcelLabel('Import', 'duplicate_unique_key');
			return false;
		}

		$tempRow['entity'] = $this->InstitutionStudents->newEntity();
		$tempRow['student_status_id'] = $this->studentStatusId;
		$tempRow['start_year'] = false;
		$tempRow['end_date'] = false;
		$tempRow['end_year'] = false;
		$tempRow['institution_id'] = $this->institutionId;
		// Optional fields which will be validated should be set with a default value on initialisation
		$tempRow['class'] = 0;
	}

	public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
		$importedUniqueCodes[] = $entity->student_id;
	}

	public function onImportPopulateAcademicPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		$modelData = $lookedUpTable->getAvailableAcademicPeriods(false);
		$translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
		$startDateLabel = $this->getExcelLabel($lookedUpTable, 'start_date');
		$endDateLabel = $this->getExcelLabel($lookedUpTable, 'end_date');
		$data[$columnOrder]['lookupColumn'] = 4;
		$data[$columnOrder]['data'][] = [$translatedReadableCol, $startDateLabel, $endDateLabel, $translatedCol];
		if (!empty($modelData)) {
			foreach($modelData as $row) {
				$date = $row->start_date;
				$data[$columnOrder]['data'][] = [
					$row->name,
					$row->start_date->format('Y-m-d H:i:s'),
					$row->end_date->format('Y-m-d H:i:s'),
					$row->$lookupColumn
				];
			}
		}
	}

	public function onImportPopulateEducationGradesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
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
		$data[$columnOrder]['lookupColumn'] = 3;
		$data[$columnOrder]['data'][] = [$programmeHeader, $translatedReadableCol, $translatedCol];
		if (!empty($modelData)) {
			foreach($modelData->toArray() as $row) {
				$data[$columnOrder]['data'][] = [
					$row->education_programme->name,
					$row->name,
					$row->$lookupColumn
				];
			}
		}
	}
	
	public function onImportPopulateStudentsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
		unset($data[$columnOrder]);
	}

	public function onImportPopulateInstitutionSectionsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
		try {
			$institution = $this->Institutions->get($this->institutionId);
			$modelData = $this->populateInstitutionSectionsData();

			$institutionNameLabel = $this->getExcelLabel('Imports', 'institution_name');
			$academicPeriodCodeLabel = $this->getExcelLabel('Imports', 'period_code');
			$classNameLabel = $this->getExcelLabel($lookupModel, 'name');
			$classCodeLabel = $this->getExcelLabel('Imports', 'institution_sections_code');
			
			// unset($data[$sheetName]);
			$sheetName = $this->getExcelLabel('Imports', $lookupModel);
			$data[$columnOrder]['sheetName'] = $sheetName;
			$data[$columnOrder]['lookupColumn'] = 4;
			$data[$columnOrder]['data'][] = [
				$institutionNameLabel,
				$academicPeriodCodeLabel,
				$classNameLabel,
				$classCodeLabel
			];
			if (!empty($modelData)) {
				foreach($modelData as $periodCode=>$periodClasses) {
					if (!empty($periodClasses)) {
						foreach($periodClasses as $id=>$name) {
							$data[$columnOrder]['data'][] = [
								$institution->name,
								$periodCode,
								$name,
								$id
							];
						}
					}
				}
			}
		} catch (\Exception $e) {
			$this->log($e->getMessage(), 'error');
		}

	}

	private function populateInstitutionSectionsData() {
		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$availableAcademicPeriods = $AcademicPeriods->getAvailableAcademicPeriods(false);

		$InstitutionSections = TableRegistry::get('Institution.InstitutionSections');
		$modelData = [];
		foreach ($availableAcademicPeriods as $key=>$value) {
			$modelData[$value->code] = $InstitutionSections->getSectionOptions($value->id, $this->institutionId);
		}
		return $modelData;
	}

	public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
		if (empty($tempRow['student_id'])) {
			return false;
		}
		try {
			$student = $this->Students->get($tempRow['student_id']);
		} catch (RecordNotFoundException $e) {
			$rowInvalidCodeCols['student_id'] = __('No such student in the system');
			return false;
		} catch (InvalidPrimaryKeyException $e) {
			$rowInvalidCodeCols['student_id'] = __('Invalid OpenEMIS ID');
			return false;
		}
		if (empty($student->date_of_birth)) {
			$rowInvalidCodeCols['date_of_birth'] = __('Student\'s date of birth is empty. Please correct it at Directory page');
			return false;
		}
		$tempRow['student_name'] = $tempRow['student_id'];

		if (!$this->institutionId) {
			$rowInvalidCodeCols['institution_id'] = __('No active institution');
			return false;
		}
		$tempRow['institution_id'] = $this->institutionId;

		if (empty($tempRow['start_date'])) {
			$rowInvalidCodeCols['start_date'] = __('No start date specified');
			return false;
		} else if (!$tempRow['start_date'] instanceof Time) {
			$rowInvalidCodeCols['start_date'] = __('Unknown date format');
			return false;
		}

		$periods = $this->getAcademicPeriodByStartDate($tempRow['start_date']->format('Y-m-d'));
		if (!$periods) {
			$rowInvalidCodeCols['start_date'] = __('No matching academic period based on the start date');
			return false;
		}
		$period='';
		foreach ($periods as $value) {
			if ($value->id == $tempRow['academic_period_id']) {
				$period = $value;
				break;
			}
		}
		if (empty($period)) {
			$rowInvalidCodeCols['start_date'] = __('Start date is not within selected academic period');
			return false;
		}
		if (!$period->start_date instanceof Time) {
			$rowInvalidCodeCols['academic_period_id'] = __('Please check the selected academic period start date in Administration');
			return false;
		}
		$periodStartDate = $period->start_date->toUnixString();
		if (!$period->end_date instanceof Time) {
			$rowInvalidCodeCols['academic_period_id'] = __('Please check the selected academic period end date in Administration');
			return false;
		}
		$periodEndDate = $period->end_date->toUnixString();
		$tempRow['start_year'] = $period->start_year;
		$tempRow['end_date'] = $period->end_date;
		$tempRow['end_year'] = $period->end_year;

		if (!in_array($tempRow['education_grade_id'], $this->gradesInInstitution)) {
			$rowInvalidCodeCols['education_grade_id'] = __('Selected education grade is not being offered in this institution');
			return false;
		}
		$institutionGrade = $this->InstitutionGrades
								->find()
								->contain('EducationGrades.EducationProgrammes.EducationCycles')
								->where([$this->InstitutionGrades->aliasField('education_grade_id') => $tempRow['education_grade_id']])
								;
		if ($institutionGrade->isEmpty()) {
			$rowInvalidCodeCols['education_grade_id'] = __('No matching education grade.');
			return false;
		}

		$institutionGrade = $institutionGrade->first();
		if (!$institutionGrade->start_date instanceof Time) {
			$rowInvalidCodeCols['education_grade_id'] = __('Please check the selected education grade start date at the institution');
			return false;
		}

		$gradeStartDate = $institutionGrade->start_date->toUnixString();
		$gradeEndDate = (!empty($institutionGrade->end_date) && (!$institutionGrade->end_date instanceof Time)) ? $institutionGrade->end_date->toUnixString() : '';
		if (!empty($gradeEndDate) && $gradeEndDate < $periodEndDate) {
			$rowInvalidCodeCols['education_grade_id'] = __('Selected education grade will end before academic period ends');
			return false;
		}
		if ($gradeStartDate > $periodStartDate) {
			$rowInvalidCodeCols['education_grade_id'] = __('Selected education grade start date should be before academic period starts');
			return false;
		}

		if (!empty($tempRow['class']) || $tempRow['class']>0) {
			if (empty($this->availableSections)) {
				$this->availableSections = $this->populateInstitutionSectionsData();
			}
			$selectedClassIdFound = null;
			if (!empty($this->availableSections)) {
				foreach($this->availableSections as $periodCode=>$periodClasses) {
					if (!empty($periodClasses)) {
						foreach($periodClasses as $id=>$name) {
							if ($id == $tempRow['class']) {
								if ($periodCode == $period->code) {
									$selectedClassIdFound = true;
								} else {
									$selectedClassIdFound = false;
								}
								break;
							}
						}
					}
				}
			}
			if (is_null($selectedClassIdFound)) {
				$rowInvalidCodeCols['class'] = __('Selected class does not exists in this institution');
				return false;
			} else if (!$selectedClassIdFound) {
				$rowInvalidCodeCols['class'] = __('Selected class does not exists during the selected Academic Period');
				return false;
			}
		}

		return true;
	}

	public function onImportSetModelPassedRecord(Event $event, Entity $clonedEntity, $columns, ArrayObject $tempPassedRecord, ArrayObject $originalRow) {
		$flipped = array_flip($columns);
		$key = $flipped['student_id'];
		$tempPassedRecord['data'][$key] = $originalRow[$key];
	}

}
