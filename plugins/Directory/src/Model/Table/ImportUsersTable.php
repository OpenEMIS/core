<?php
namespace Directory\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;
use App\Model\Table\AppTable;

class ImportUsersTable extends AppTable {
	public function initialize(array $config) {
		$this->table('import_mapping');
		parent::initialize($config);

	    $this->addBehavior('Import.Import', ['plugin'=>'User', 'model'=>'Users']);

	    // register table once 
		$this->Users = TableRegistry::get('User.Users');
	    $this->ConfigItems = TableRegistry::get('ConfigItems');

	    $this->accountTypes = [
	    	'is_student' => [
	    		'id' => 'is_student',
	    		'code' => 'STU',
	    		'name' => __('Students'),
	    		'model' => 'Student',
	    		'prefix' => '',
	    	],
	    	'is_staff' => [
	    		'id' => 'is_staff',
	    		'code' => 'STA',
	    		'name' => __('Staff'),
	    		'model' => 'Staff',
	    		'prefix' => '',
	    	],
	    	'is_guardian' => [
	    		'id' => 'is_guardian',
	    		'code' => 'GUA',
	    		'name' => __('Guardians'),
	    		'model' => 'Guardian',
	    		'prefix' => '',
	    	],
	    	'others' => [
	    		'id' => 'others',
	    		'code' => 'OTH',
	    		'name' => __('Others'),
	    		'model' => '',
	    		'prefix' => '',
	    	]
	    ];

		$studentPrefix = $this->ConfigItems->value('student_prefix');
		$studentPrefix = explode(",", $studentPrefix);
		$this->accountTypes['is_student']['prefix'] = (isset($studentPrefix[1]) && $studentPrefix[1]>0) ? $studentPrefix[0] : '';

		$staffPrefix = $this->ConfigItems->value('staff_prefix');
		$staffPrefix = explode(",", $staffPrefix);
		$this->accountTypes['is_staff']['prefix'] = (isset($staffPrefix[1]) && $staffPrefix[1]>0) ? $staffPrefix[0] : '';

		$guardianPrefix = $this->ConfigItems->value('guardian_prefix');
		$guardianPrefix = explode(",", $guardianPrefix);
		$this->accountTypes['is_guardian']['prefix'] = (isset($guardianPrefix[1]) && $guardianPrefix[1]>0) ? $guardianPrefix[0] : '';

	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
			'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
			'Model.import.onImportPopulateAreaAdministrativesData' => 'onImportPopulateAreaAdministrativesData',
			'Model.import.onImportPopulateGendersData' => 'onImportPopulateGendersData',
			'Model.import.onImportPopulateAccountTypesData' => 'onImportPopulateAccountTypesData',
			'Model.import.onImportGetAccountTypesId' => 'onImportGetAccountTypesId',
			'Model.import.onImportModelSpecificValidation' => 'onImportModelSpecificValidation',
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes) {
		$columns = new Collection($columns);
		$extractedOpenemisNo = $columns->filter(function ($value, $key, $iterator) {
		    return $value == 'openemis_no';
		});
		$openemisNoIndex = key($extractedOpenemisNo->toArray());
		$openemisNo = $sheet->getCellByColumnAndRow($openemisNoIndex, $row)->getValue();

		if (in_array($openemisNo, $importedUniqueCodes->getArrayCopy())) {
			$tempRow['duplicates'] = true;
			return false;
		}

		$accountType = $columns->filter(function ($value, $key, $iterator) {
		    return $value == 'account_type';
		});
		$accountTypeIndex = key($accountType->toArray());
		$accountType = $sheet->getCellByColumnAndRow($accountTypeIndex, $row)->getValue();
		$tempRow['account_type'] = $this->getAccountTypeId($accountType);
		if (empty($tempRow['account_type'])) {
			$tempRow['duplicates'] = __('Account type cannot be empty.');
			return false;
		}

		$user = $this->Users->find()->where(['openemis_no'=>$openemisNo])->first();
		if (!$user) {
			$tempRow['entity'] = $this->Users->newEntity();
			$tempRow['openemis_no'] = $this->getNewOpenEmisNo($importedUniqueCodes, $row, $tempRow['account_type']);
		} else {
			$tempRow['entity'] = $user;
		}

		if (!empty($tempRow['account_type'])) {
			// setting is_student = 1, or is_staff = 1, or is_guardian = 1
			$tempRow[$tempRow['account_type']] = 1;
		}
	}

	public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
		$importedUniqueCodes[] = $entity->openemis_no;
	}

	public function onImportGetAccountTypesId(Event $event, $cellValue) {
		return $this->getAccountTypeId($cellValue);
	}

	public function onImportPopulateAccountTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
		$translatedReadableCol = $this->getExcelLabel('Imports', 'name');
		$data[$columnOrder]['lookupColumn'] = 2;
		$data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
		$modelData = $this->accountTypes;
		foreach($modelData as $row) {
			$data[$columnOrder]['data'][] = [
				$row['name'],
				$row[$lookupColumn]
			];
		}
	}

	public function onImportPopulateAreaAdministrativesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		$modelData = $lookedUpTable->find('all')
								->select(['name', $lookupColumn])
								->order($lookupModel.'.area_administrative_level_id', $lookupModel.'.order')
								;

		$translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
		$data[$columnOrder]['lookupColumn'] = 2;
		$data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
		if (!empty($modelData)) {
			foreach($modelData->toArray() as $row) {
				$data[$columnOrder]['data'][] = [
					$row->name,
					$row->$lookupColumn
				];
			}
		}
	}

	public function onImportPopulateGendersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		$modelData = $lookedUpTable->find('all')
								->select(['name', $lookupColumn])
								->order([$lookupModel.'.order'])
								;

		$translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
		$data[$columnOrder]['lookupColumn'] = 2;
		$data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
		if (!empty($modelData)) {
			foreach($modelData->toArray() as $row) {
				$data[$columnOrder]['data'][] = [
					$row->name,
					$row->$lookupColumn
				];
			}
		}
	}

	public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
		return true;
	}

	protected function getNewOpenEmisNo(ArrayObject $importedUniqueCodes, $row, $accountType) {
		$model = $this->accountTypes[$accountType]['model'];
		$importedCodes = $importedUniqueCodes->getArrayCopy();
		if (count($importedCodes)>0) {
			if (empty($accountType)) {
				$prefix = '';
			} else {
				$prefix = $this->accountTypes[$accountType]['prefix'];
			}
			$val = reset($importedCodes);

			foreach ($this->accountTypes as $key => $value) {
				if (!empty($value['prefix']) && substr_count($val, $value['prefix'])>0) {
					$val = substr($val, strlen($value['prefix']));
				}				
			}
			$val = $prefix . (intval($val) + $row);
			$user = $this->Users->find()->select(['id'])->where(['openemis_no'=>$val])->first();
			if ($user) {
				$importedUniqueCodes[] = $val;
				$val = $this->Users->getUniqueOpenemisId(['model' => $model]);
			}
		} else {
			$val = $this->Users->getUniqueOpenemisId(['model' => $model]);
		}
		return $val;
	}

	protected function getAccountTypeId($cellValue) {
		$accountType = '';
		foreach ($this->accountTypes as $key=>$type) {
			if ($type['code']==$cellValue) {
				$accountType = $type['id'];
				break;
			}
		}
		return $accountType;
	}

}
