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
			'Model.import.onImportPopulateDirectTableData' => 'onImportPopulateDirectTableData',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes) {
		$session = $this->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$institutionId = $session->read('Institution.Institutions.id');
		} else {
			$institutionId = false;
		}

		$columns = new Collection($columns);
		$filtered = $columns->filter(function ($value, $key, $iterator) {
		    return $value == 'institution_site_id';
		});
		$codeIndex = key($filtered->toArray());
		$institutionCode = $sheet->getCellByColumnAndRow($codeIndex, $row)->getValue();

		$filtered = $columns->filter(function ($value, $key, $iterator) {
		    return $value == 'security_user_id';
		});
		$idIndex = key($filtered->toArray());
		$openemis_no = $sheet->getCellByColumnAndRow($idIndex, $row)->getValue();

		if (empty($openemis_no)) {
			return false;
		}
	
		if ($institutionCode) {
			$institution = $this->Institutions->find()->where(['code'=>$institutionCode])->first();
			if (!$institution) {
				$tempRow['duplicates'] = 'institutionCode exists but no such institution';
				$tempRow['institution_site_id'] = false;
				return false;
			}

			if ($institutionId && $institutionId!=$institution->id) {
				$tempRow['duplicates'] = true;
				$tempRow['institution_site_id'] = false;
				return false;
			}
		}

		if (!isset($institution) && !$institutionId) {
			$tempRow['duplicates'] = true;
			$tempRow['institution_site_id'] = false;
			return false;
		}

		$user = $this->Users->find()->where(['openemis_no'=>$openemis_no])->first();
		if (!$user) {
			$tempRow['duplicates'] = true;
			$tempRow['security_user_id'] = false;
			return false;
		}

		$staff = $this->Staff->find()->where([
			'institution_site_id' => $institutionId,
			'security_user_id' => $user->id,
		])->first();
		if (!$staff) {
			$tempRow['duplicates'] = true;
			$tempRow['security_user_id'] = false;
			return false;
		}

		$tempRow['full_day'] = 1;
		$tempRow['institution_site_id'] = $institutionId;
		$tempRow['security_user_id'] = $user->id;
		$tempRow['entity'] = $this->StaffAbsences->newEntity();

	}

	public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
		// $importedUniqueCodes[] = $entity->code;
	}

	public function onImportPopulateDirectTableData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
		$session = $this->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$institutionId = $session->read('Institution.Institutions.id');
		} else {
			$institutionId = false;
		}
		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		if ($lookedUpTable->hasField('name')) {
			if ($lookupModel == 'AcademicPeriods') {
				$selectFields = ['name', 'academic_period_level_id', $lookupColumn];
			} else {
				$selectFields = ['name', $lookupColumn];
			}
		} else {
			$selectFields = ['first_name', 'middle_name', 'third_name', 'last_name', $lookupColumn];
		}

		$translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
		$data[$sheetName][] = [$translatedReadableCol, $translatedCol];

		$modelData = $lookedUpTable->find('all')
			->select($selectFields)
			;

		if ($lookupModel == 'AcademicPeriods') {
			
			if (!empty($modelData)) {
				foreach($modelData->toArray() as $row) {
					$data[$sheetName][] = [
						$row->name,
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
		return true;
	}
}
