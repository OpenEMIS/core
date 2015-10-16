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
	    
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
			'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
			'Model.import.onImportDirectTableData' => 'onImportDirectTableData',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes) {
		// $columns = new Collection($columns);
		// $filtered = $columns->filter(function ($value, $key, $iterator) {
		//     return $value == 'code';
		// });
		// $codeIndex = key($filtered->toArray());
		// $code = $sheet->getCellByColumnAndRow($codeIndex, $row)->getValue();

		// if (in_array($code, $importedUniqueCodes->getArrayCopy())) {
		// 	$tempRow['duplicates'] = true;
		// 	return true;
		// }

		// // $tempRow['entity'] must be assigned!!!
		// $model = TableRegistry::get('Institution.Institutions');
		// $institution = $model->find()->where(['code'=>$code])->first();
		// if (!$institution) {
		// 	$tempRow['entity'] = $model->newEntity();
		// }
	}

	public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
		// $importedUniqueCodes[] = $entity->code;
	}

	public function onImportDirectTableData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
		$session = $this->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$institutionId = $session->read('Institution.Institutions.id');
		} else {
			$institutionId = false;
		}
		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		if ($lookedUpTable->hasField('name')) {
			$selectFields = ['name', $lookupColumn];
		} else {
			$selectFields = ['first_name', 'middle_name', 'third_name', 'last_name', $lookupColumn];
		}

		$modelData = $lookedUpTable->find('all')
			->select($selectFields)
			;

		if ($institutionId) {
			if ($lookupModel == 'Institutions') {
				$modelData->where(['id'=>$institutionId]);
			} else if ($lookupModel == 'Users') {
				$Staff = TableRegistry::get('Institution.InstitutionSiteStaff');
				$activeStaff = $Staff->find('all')
									->find('byInstitution', ['Institutions.id'=>$institutionId])
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
			try {
				$modelData = $modelData->toArray();
			} catch (\Exception $e) {
				pr($modelData->sql());die;
			}
			foreach($modelData as $row) {
				$data[$sheetName][] = [
					$row->name,
					$row->$lookupColumn
				];
			}
		}
	}

}
