<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use DateTime;
use PHPExcel_Worksheet;

class ImportStaffAttendancesTable extends AppTable {
    private $institutionId = false;

    public function initialize(array $config) {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'StaffAbsences']);

        $this->StaffAbsences = TableRegistry::get('Institution.StaffAbsences');
        $this->Institutions = TableRegistry::get('Institution.Institutions');
        $this->Staff = TableRegistry::get('Institution.Staff');
        $this->Users = TableRegistry::get('User.Users');
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
    }

    public function beforeAction($event) {
        $session = $this->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }
        $this->systemDateFormat = TableRegistry::get('Configuration.ConfigItems')->value('date_format');
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.import.onImportCheckUnique' => 'onImportCheckUnique',
            'Model.import.onImportUpdateUniqueKeys' => 'onImportUpdateUniqueKeys',
            'Model.import.onImportPopulateUsersData' => 'onImportPopulateUsersData',
            'Model.import.onImportPopulateAbsenceTypesData' => 'onImportPopulateAbsenceTypesData',
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
        $tempRow['entity'] = $this->StaffAbsences->newEntity();
        $tempRow['full_day'] = 1;
        $tempRow['institution_id'] = false;
        $tempRow['academic_period_id'] = false;
    }

    public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity) {}

    public function onImportPopulateUsersData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')->select(['id', 'first_name', 'middle_name', 'third_name', 'last_name', $lookupColumn]);

        $allStaff = $this->Staff
                        ->find('all')
                        ->where([$this->Staff->aliasField('institution_id') => $this->institutionId])
                        ;
        // when extracting the staff_id from $allStaff collection, there will be no duplicates
        $allStaff = new Collection($allStaff->toArray());
        $modelData->where([
            'id IN' => $allStaff->extract('staff_id')->toArray()
        ]);

        $institution = $this->Institutions->get($this->institutionId);
        $institutionHeader = $this->getExcelLabel('Imports', 'institution_id') . ": " . $institution->name;
        $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [
            $institutionHeader,
            $nameHeader,
            $columnHeader
        ];
        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $institution->name,
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateAbsenceTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->find('all')->select(['id', 'name', $lookupColumn]);

        $nameHeader = $this->getExcelLabel($lookedUpTable, 'name');
        $columnHeader = $this->getExcelLabel($lookedUpTable, $lookupColumn);
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [
            $nameHeader,
            $columnHeader
        ];

        if (!empty($modelData)) {
            foreach($modelData->toArray() as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
        if (empty($tempRow['staff_id'])) {
            $rowInvalidCodeCols['staff_id'] = __('OpenEMIS ID was not defined');
            return false;
        }

        if (!$this->institutionId) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $tempRow['institution_id'] = false;
            return false;
        }
        $tempRow['institution_id'] = $this->institutionId;

        $currentPeriodId = $this->AcademicPeriods->getCurrent();
        if (!$currentPeriodId) {
            $array = $this->AcademicPeriods->getAvailableAcademicPeriods();
            reset($array);
            $currentPeriodId = key($array);
        }
        $isEditable = $this->AcademicPeriods->getAvailableAcademicPeriods($currentPeriodId);
        if (!$isEditable) {
            $rowInvalidCodeCols['academic_period_id'] = __('No data changes can be made for the current academic period');
            $tempRow['academic_period_id'] = false;
            return false;
        }

        if (empty($tempRow['start_date'])) {
            $rowInvalidCodeCols['start_date'] = __('This field cannot be left empty');
            return false;
        } else {
            // from string to dateObject
            $formattedDate = DateTime::createFromFormat('d/m/Y', $tempRow['start_date']);
            $tempRow['start_date'] = $formattedDate;

            $periods = $this->getAcademicPeriodByStartDate($tempRow['start_date']);
            if (!$periods) {
                $rowInvalidCodeCols['academic_period_id'] = __('No matching academic period based on the start date');
                $tempRow['academic_period_id'] = false;
                return false;
            }
            $periods = new Collection($periods);
            $periodIds = $periods->extract('id');
            $periodIds = $periodIds->toArray();
            if (!in_array($currentPeriodId, $periodIds)) {
                $rowInvalidCodeCols['academic_period_id'] = __('Date is not within current academic period');
                $tempRow['academic_period_id'] = false;
                return false;
            }
            $tempRow['academic_period_id'] = $currentPeriodId;
        }

        $staff = $this->Staff->find()->where([
            'institution_id' => $tempRow['institution_id'],
            'staff_id' => $tempRow['staff_id'],
        ])->first();
        if (!$staff) {
            $rowInvalidCodeCols['staff_id'] = __('No such staff in the institution');
            $tempRow['staff_id'] = false;
            return false;
        }

        return true;
    }

    public function onImportSetModelPassedRecord(Event $event, Entity $clonedEntity, $columns, ArrayObject $tempPassedRecord, ArrayObject $originalRow) {
        $flipped = array_flip($columns);
        $key = $flipped['staff_id'];
        $tempPassedRecord['data'][$key] = $originalRow[$key];
    }

}
