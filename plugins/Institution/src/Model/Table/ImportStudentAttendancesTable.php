<?php
namespace Institution\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use App\Model\Table\AppTable;

class ImportStudentAttendancesTable extends AppTable {
	private $institutionId = false;

	public function initialize(array $config) {
		$this->table('import_mapping');
		parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'InstitutionStudentAbsences']);

	    $this->StudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
	    $this->Institutions = TableRegistry::get('Institution.Institutions');
	    $this->Students = TableRegistry::get('Institution.Students');
	    $this->Users = TableRegistry::get('User.Users');
	    $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$this->InstitutionSections = TableRegistry::get('Institution.InstitutionSections');
	}

	public function beforeAction($event) {
		$session = $this->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$this->institutionId = $session->read('Institution.Institutions.id');
		}
		$this->systemDateFormat = TableRegistry::get('ConfigItems')->value('date_format');
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
			'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
			'Model.import.onImportPopulateUsersData' => 'onImportPopulateUsersData',
			'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes) {
		$tempRow['duplicates'] = false;
		$tempRow['entity'] = $this->StudentAbsences->newEntity();

		$tempRow['full_day'] = 1;
		$tempRow['institution_site_id'] = false;
		$tempRow['academic_period_id'] = false;

	}

	public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
		// $importedUniqueCodes[] = $entity->code;
	}

	/**
	 * Currently only populates students based on current academic period
	 */
	public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		$currentPeriodId = $this->AcademicPeriods->getCurrent();
		if (!$currentPeriodId) {
			$array = $this->AcademicPeriods->getAvailableAcademicPeriods();
			reset($array);
			$currentPeriodId = key($array);
		}
		$currentPeriod = $this->AcademicPeriods->get($currentPeriodId);
		$allStudents = $this->Students
							->find('all')
							->select([
								'student_id',
								'EducationGrades.name','EducationGrades.order',
								'Users.first_name', 'Users.middle_name', 'Users.third_name', 'Users.last_name', 'Users.'.$lookupColumn
							])
							->where([
								$this->Students->aliasField('academic_period_id') => $currentPeriodId,
								$this->Students->aliasField('institution_id') => $this->institutionId,
								'Users.id IS NOT NULL',
							])
							->contain([
								'EducationGrades',
								'Users'
							])
							// ->join([
							// 	'InstitutionSectionStudents' => [
							// 		'table' => 'institution_site_section_students',
							// 		'alias' => 'InstitutionSectionStudents',
							// 		// 'type' => 'LEFT',
							// 		'conditions' => 'InstitutionSectionStudents.student_id = '.$this->Students->aliasField('student_id'),
							// 	],
							// ])
							->order(['EducationGrades.order'])
							;
		$institution = $this->Institutions->get($this->institutionId);
		$institutionHeader = $this->getExcelLabel('Imports', 'institution_site_id') . ": " . $institution->name;
		$periodHeader = $this->getExcelLabel($lookedUpTable, 'academic_period_id') . ": " . $currentPeriod->name;
		$gradeHeader = $this->getExcelLabel($lookedUpTable, 'education_grade_id');
		$nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
		$columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
		$data[$sheetName][] = [
			$institutionHeader,
			$periodHeader,
			$gradeHeader,
			$nameHeader,
			$columnHeader
		];
		if (!empty($allStudents)) {
			foreach($allStudents->toArray() as $row) {
				$data[$sheetName][] = [
					$institution->name,
					$currentPeriod->name,
					$row->education_grade->name,
					$row->user->name,
					$row->user->$lookupColumn
				];
			}
		}
	}

	// public function onImportPopulateUsersDataBasedOnAllAcademicPeriods(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
	// 	$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
	// 	$editablePeriods = $this->AcademicPeriods->getAvailableAcademicPeriods();
	// 	$allStudents = $this->Students
	// 						->find('all')
	// 						->select([
	// 							'EducationGrades.name', 'AcademicPeriods.name', 'AcademicPeriods.order', 'Users.id', 'Users.first_name', 'Users.middle_name', 'Users.third_name', 'Users.last_name', 'Users.'.$lookupColumn
	// 						])
	// 						->where([
	// 							$this->Students->aliasField('academic_period_id').' IN' => array_keys($editablePeriods),
	// 							$this->Students->aliasField('institution_id') => $this->institutionId
	// 						])
	// 						->contain([
	// 							'EducationGrades',
	// 							'AcademicPeriods',
	// 							'Users'
	// 						])
	// 						->order(['AcademicPeriods.order'])
	// 						;
	// 	$nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
	// 	$periodHeader = $this->getExcelLabel($lookedUpTable, 'academic_period_id');
	// 	$gradeHeader = $this->getExcelLabel($lookedUpTable, 'education_grade_id');
	// 	$columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
	// 	$data[$sheetName][] = [
	// 		$nameHeader,
	// 		$periodHeader,
	// 		$gradeHeader,
	// 		$columnHeader
	// 	];
	// 	if (!empty($allStudents)) {
	// 		foreach($allStudents->toArray() as $row) {
	// 			$data[$sheetName][] = [
	// 				$row->Users->name,
	// 				$row->AcademicPeriods->name,
	// 				$row->EducationGrades->name,
	// 				$row->Users->$lookupColumn
	// 			];
	// 		}
	// 	}
	// }

	public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
		if (empty($tempRow['security_user_id'])) {
			return false;
		}

		if (!$this->institutionId) {
			$tempRow['duplicates'] = __('No active institution');
			$tempRow['institution_site_id'] = false;
			return false;
		}
		$tempRow['institution_site_id'] = $this->institutionId;

		$currentPeriodId = $this->AcademicPeriods->getCurrent();
		if (!$currentPeriodId) {
			$array = $this->AcademicPeriods->getAvailableAcademicPeriods();
			reset($array);
			$currentPeriodId = key($array);
		}
		$isEditable = $this->AcademicPeriods->getAvailableAcademicPeriods($currentPeriodId);
		if (!$isEditable) {
			$tempRow['duplicates'] = __('No data changes can be made for the current academic period');
			$tempRow['academic_period_id'] = false;
			return false;
		}
		$period = $this->getAcademicPeriodByStartDate($tempRow['start_date']);
		if (!$period) {
			$tempRow['duplicates'] = __('No matching academic period');
			$tempRow['academic_period_id'] = false;
			return false;
		}
		if ($period->id != $currentPeriodId) {
			$tempRow['duplicates'] = __('Date is not within current academic period');
			$tempRow['academic_period_id'] = false;
			return false;
		}
		$tempRow['academic_period_id'] = $period->id;

		$student = $this->Students->find()->where([
			'academic_period_id' => $tempRow['academic_period_id'],
			'institution_id' => $tempRow['institution_site_id'],
			'student_id' => $tempRow['security_user_id'],
		])->first();
		if (!$student) {
			$tempRow['duplicates'] = __('No such student in the institution');
			$tempRow['security_user_id'] = false;
			return false;
		}
		
		return true;
	}
}
