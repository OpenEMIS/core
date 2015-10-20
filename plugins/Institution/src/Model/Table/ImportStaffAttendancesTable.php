<?php
namespace Institution\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use App\Model\Table\AppTable;

class ImportStaffAttendancesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('import_mapping');
		parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'StaffAbsences']);
	    // $this->addBehavior('Import.Import');

	    $this->StaffAbsences = TableRegistry::get('Institution.StaffAbsences');
	    $this->Institutions = TableRegistry::get('Institution.Institutions');
	    $this->Staff = TableRegistry::get('Institution.Staff');
	    $this->Users = TableRegistry::get('User.Users');
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
			'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
			// 'Model.import.onImportPopulateDirectTableData' => 'onImportPopulateDirectTableData',
			'Model.import.onImportPopulateAcademicPeriodsData' => 'onImportPopulateAcademicPeriodsData',
			'Model.import.onImportPopulateInstitutionsData' => 'onImportPopulateInstitutionsData',
			'Model.import.onImportPopulateUsersData' => 'onImportPopulateUsersData',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes) {

		$tempRow['duplicates'] = false;
		$tempRow['entity'] = $this->StaffAbsences->newEntity();

	}

	public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
		// $importedUniqueCodes[] = $entity->code;
	}

	public function onImportPopulateAcademicPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
		$session = $this->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$institutionId = $session->read('Institution.Institutions.id');
		} else {
			$institutionId = false;
		}
		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		$selectFields = ['name', 'academic_period_level_id', 'start_date', 'end_date', 'Levels.name', $lookupColumn];

		$modelData = $lookedUpTable->find('all')->select($selectFields);

		$systemDateFormat = TableRegistry::get('ConfigItems')->value('date_format');
		$modelData->contain(['Levels'])->where(['parent_id <> 0']);

		$nameCol = $this->getExcelLabel($lookedUpTable, 'name');
		$periodCol = $this->getExcelLabel($lookedUpTable, 'academic_period_level_id');
		$startDateCol = $this->getExcelLabel($lookedUpTable, 'start_date');
		$endDateCol = $this->getExcelLabel($lookedUpTable, 'end_date');
		$data[$sheetName][] = [$periodCol, $nameCol, $startDateCol, $endDateCol, $translatedCol];
		if (!empty($modelData)) {
			foreach($modelData->toArray() as $row) {
				$data[$sheetName][] = [
					$row->level->name,
					$row->name,
					$row->start_date->format($systemDateFormat),
					$row->end_date->format($systemDateFormat),
					$row->$lookupColumn
				];
			}
		}
		return true;
	}

	public function onImportPopulateInstitutionsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
		$session = $this->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$institutionId = $session->read('Institution.Institutions.id');
		} else {
			$institutionId = false;
		}
		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		if ($lookedUpTable->hasField('name')) {
			if ($lookupModel == 'AcademicPeriods') {
				$selectFields = ['name', 'academic_period_level_id', 'start_date', 'end_date', 'Levels.name', $lookupColumn];
			} else {
				$selectFields = ['name', $lookupColumn];
			}
		} else {
			$selectFields = ['first_name', 'middle_name', 'third_name', 'last_name', $lookupColumn];
		}

		$modelData = $lookedUpTable->find('all')->select($selectFields);

		if ($lookupModel == 'AcademicPeriods') {
			$systemDateFormat = TableRegistry::get('ConfigItems')->value('date_format');
			$modelData->contain(['Levels'])->where(['parent_id <> 0']);

			$nameCol = $this->getExcelLabel($lookedUpTable, 'name');
			$periodCol = $this->getExcelLabel($lookedUpTable, 'academic_period_level_id');
			$startDateCol = $this->getExcelLabel($lookedUpTable, 'start_date');
			$endDateCol = $this->getExcelLabel($lookedUpTable, 'end_date');
			$data[$sheetName][] = [$periodCol, $nameCol, $startDateCol, $endDateCol, $translatedCol];
			if (!empty($modelData)) {
				foreach($modelData->toArray() as $row) {
					$data[$sheetName][] = [
						$row->level->name,
						$row->name,
						$row->start_date->format($systemDateFormat),
						$row->end_date->format($systemDateFormat),
						$row->$lookupColumn
					];
				}
			}
			return true;
		}
		
		if ($institutionId) {
			if ($lookupModel == 'Institutions') {
				$modelData->where(['id'=>$institutionId]);
			} else if ($lookupModel == 'Users') {
				$activeStaff = $this->Staff
									->find('all')
									->where([$this->Staff->aliasField('institution_site_id') => $institutionId])
									;
				$activeStaffIds = new Collection($activeStaff->toArray());
				$modelData->where([
					'id IN' => $activeStaffIds->extract('security_user_id')->toArray()
				]);
			}
		}

		$translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
		$data[$sheetName][] = [$translatedReadableCol, $translatedCol];
		if (!empty($modelData)) {
			foreach($modelData->toArray() as $row) {
				$data[$sheetName][] = [
					$row->name,
					$row->$lookupColumn
				];
			}
		}	
	}

	public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
		$session = $this->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$institutionId = $session->read('Institution.Institutions.id');
		} else {
			$institutionId = false;
		}
		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		if ($lookedUpTable->hasField('name')) {
			if ($lookupModel == 'AcademicPeriods') {
				$selectFields = ['name', 'academic_period_level_id', 'start_date', 'end_date', 'Levels.name', $lookupColumn];
			} else {
				$selectFields = ['name', $lookupColumn];
			}
		} else {
			$selectFields = ['first_name', 'middle_name', 'third_name', 'last_name', $lookupColumn];
		}

		$modelData = $lookedUpTable->find('all')->select($selectFields);

		if ($lookupModel == 'AcademicPeriods') {
			$systemDateFormat = TableRegistry::get('ConfigItems')->value('date_format');
			$modelData->contain(['Levels'])->where(['parent_id <> 0']);

			$nameCol = $this->getExcelLabel($lookedUpTable, 'name');
			$periodCol = $this->getExcelLabel($lookedUpTable, 'academic_period_level_id');
			$startDateCol = $this->getExcelLabel($lookedUpTable, 'start_date');
			$endDateCol = $this->getExcelLabel($lookedUpTable, 'end_date');
			$data[$sheetName][] = [$periodCol, $nameCol, $startDateCol, $endDateCol, $translatedCol];
			if (!empty($modelData)) {
				foreach($modelData->toArray() as $row) {
					$data[$sheetName][] = [
						$row->level->name,
						$row->name,
						$row->start_date->format($systemDateFormat),
						$row->end_date->format($systemDateFormat),
						$row->$lookupColumn
					];
				}
			}
			return true;
		}
		
		if ($institutionId) {
			if ($lookupModel == 'Institutions') {
				$modelData->where(['id'=>$institutionId]);
			} else if ($lookupModel == 'Users') {
				$activeStaff = $this->Staff
									->find('all')
									->where([$this->Staff->aliasField('institution_site_id') => $institutionId])
									;
				$activeStaffIds = new Collection($activeStaff->toArray());
				$modelData->where([
					'id IN' => $activeStaffIds->extract('security_user_id')->toArray()
				]);
			}
		}

		$translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
		$data[$sheetName][] = [$translatedReadableCol, $translatedCol];
		if (!empty($modelData)) {
			foreach($modelData->toArray() as $row) {
				$data[$sheetName][] = [
					$row->name,
					$row->$lookupColumn
				];
			}
		}
	}

	public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
		// pr('onImportModelSpecificValidation');
		// pr($tempRow);
		// die;

		$session = $this->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$institutionId = $session->read('Institution.Institutions.id');
		} else {
			$institutionId = false;
		}

		if (empty($tempRow['security_user_id'])) {
			return false;
		}
	
		$institution = $this->Institutions->exists($tempRow['institution_site_id']);
		if (!$institution) {
			$tempRow['duplicates'] = __('No such institution');
			$tempRow['institution_site_id'] = false;
			return false;
		} else if ($institutionId && $institutionId!=$tempRow['institution_site_id']) {
			$tempRow['duplicates'] = __('Wrong institution');
			$tempRow['institution_site_id'] = false;
			return false;
		}

		$staff = $this->Staff->find()->where([
			'institution_site_id' => $tempRow['institution_site_id'],
			'security_user_id' => $tempRow['security_user_id'],
		])->first();
		if (!$staff) {
			$tempRow['duplicates'] = __('No such staff in the institution');
			$tempRow['security_user_id'] = false;
			return false;
		}
		
		$tempRow['full_day'] = 1;

		return true;
	}
}
