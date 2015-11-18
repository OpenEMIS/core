<?php
namespace Institution\Model\Table;

use DateTime;
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

	    $this->StaffAbsences = TableRegistry::get('Institution.StaffAbsences');
	    $this->Institutions = TableRegistry::get('Institution.Institutions');
	    $this->Staff = TableRegistry::get('Institution.Staff');
	    $this->Users = TableRegistry::get('User.Users');
	}

	public function beforeAction($event) {
		$session = $this->request->session();
		if ($session->check('Institution.Institutions.id')) {
			$this->institutionId = $session->read('Institution.Institutions.id');
		} else {
			$this->institutionId = false;
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
		$tempRow['entity'] = $this->StaffAbsences->newEntity();
	}

	public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {
		// $importedUniqueCodes[] = $entity->code;
	}

	public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $sheetName, $translatedCol, ArrayObject $data) {
		$lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
		$modelData = $lookedUpTable->find('all')->select(['id', 'first_name', 'middle_name', 'third_name', 'last_name', $lookupColumn]);

		$allStaff = $this->Staff
							->find('all')
							->where([$this->Staff->aliasField('institution_site_id') => $this->institutionId])
							;
		// when extracting the security_user_id from $allStaff collection, there will be no duplicates
		$allStaff = new Collection($allStaff->toArray());
		$modelData->where([
			'id IN' => $allStaff->extract('security_user_id')->toArray()
		]);

		$institution = $this->Institutions->get($this->institutionId);
		$institutionHeader = $this->getExcelLabel('Imports', 'institution_site_id') . ": " . $institution->name;
		$nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
		$columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
		$data[$sheetName][] = [
			$institutionHeader,
			$nameHeader,
			$columnHeader
		];
		if (!empty($modelData)) {
			foreach($modelData->toArray() as $row) {
				$focusedStaff = $allStaff->indexBy('security_user_id')->toArray()[$row->id];
				$data[$sheetName][] = [
					$institution->name,
					$row->name,
					$row->$lookupColumn
				];
			}
		}
	}

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

		$period = $this->getAcademicPeriodByStartDate($tempRow['start_date']);
		if (!$period) {
			$tempRow['duplicates'] = __('No matching academic period');
			$tempRow['academic_period_id'] = false;
			return false;
		}
		$tempRow['academic_period_id'] = $period->id;

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
