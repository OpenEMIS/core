<?php

namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use ArrayObject;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use DateTimeInterface;
use PHPExcel_Worksheet;
use Cake\Log\Log;
use Cake\I18n\FrozenDate;

// POCOR-9080

// POCOR-9080

class ImportStaffTable extends AppTable
{
    use OptionsTrait;

    private $_institution;

    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin' => 'Institution', 'model' => 'Staff']);
        $this->addBehavior('Institution.ImportStaff');

        // register the target table once
        $this->Institutions = TableRegistry::get('Institution.Institutions');
        $this->InstitutionStaff = TableRegistry::get('Institution.Staff');
        $this->InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
        $this->Staff = TableRegistry::get('Security.Users');

        $positionTypes = $this->getSelectOptions('Position.types');
        $this->positionTypes = [];
        foreach ($positionTypes as $key => $type) {
            $this->positionTypes[] = [
                'id' => $key,
                'code' => $key,
                'name' => $type
            ];
        }

        $ftes = $this->getSelectOptions('StaffPositionProfiles.FTE');
        $this->ftes = [];
        foreach ($ftes as $key => $fte) {
            $this->ftes[] = [
                'value' => $key,
                'name' => $type
            ];
        }
    }

    public function beforeAction($event)
    {
        $institutionId = $this->getQueryString('institution_id'); // POCOR-9080
        if ($institutionId && is_numeric($institutionId) && $institutionId > 0) {
            $this->_institution = $this->Institutions->get($institutionId);
        } else {
            $this->_institution = false;

        }
    }

    public function implementedEvents(): array
    {
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

    public function onGetBreadcrumb(Event $event, ServerRequest $request, Component $Navigation, $persona)
    {
        $crumbTitle = $this->getHeader($this->getAlias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportCheckUnique(Event $event, $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols)
    {
        $institutionId = $this->getQueryString('institution_id'); // POCOR-9080
        $this->_institution = $this->Institutions->get($institutionId); // POCOR-9080
        $columns = new Collection($columns);
        $filtered = $columns->filter(function ($value, $key, $iterator) {
            return $value == 'staff_id';
        });
        $staffIdIndex = key($filtered->toArray());
        $staffId = $sheet->getCellByColumnAndRow($staffIdIndex, $row)->getValue();
        $error = false; // POCOR-9080
        if (in_array($staffId, $importedUniqueCodes->getArrayCopy())) {
            $rowInvalidCodeCols['staff_id'] = $this->getExcelLabel('Import', 'duplicate_unique_key');
            $error = true;
        }

//        $tempRow['start_year'] = false;
        $tempRow['staff_status_id'] = 1;
        $tempRow['end_date'] = '';
        $tempRow['end_year'] = '';
        // POCOR-9080 start
        $tempRow['institution_id'] = $institutionId; // ($this->_institution instanceof AppTable) ? $this->_institution->id : false;
        $tempRow['FTE'] = 0;
        $tempRow['entity'] = $this->InstitutionStaff->newEntity($tempRow->getArrayCopy()); // POCOR-9080
        if ($error) {
            Log::debug('ImportStaffTable::onImportCheckUnique() tempRow has errors: ' . print_r($tempRow, true));
            return false;
        } else {
            return true;
        }
        // POCOR-9080 end
    }

    public function onImportUpdateUniqueKeys(Event $event, ArrayObject $importedUniqueCodes, Entity $entity)
    {
        $importedUniqueCodes[] = $entity->staff_id;
    }

    public function onImportGetPositionTypesId(Event $event, $cellValue)
    {
        return $this->getPositionTypeId($cellValue);
    }

    protected function getPositionTypeId($cellValue)
    {
        $positionType = '';
        foreach ($this->positionTypes as $key => $type) {
            if ($type['code'] == $cellValue) {
                $positionType = $type['id'];
                break;
            }
        }
        return $positionType;
    }

    public function onImportPopulatePositionTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $translatedReadableCol = $this->getExcelLabel('Imports', 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        $modelData = $this->positionTypes;
        foreach ($modelData as $row) {
            $data[$columnOrder]['data'][] = [
                $row['name'],
                $row[$lookupColumn]
            ];
        }
    }

    //POCOR-7711 :: Start
    public function onImportPopulateStaffPositionGradesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . "StaffPositionGrades");
        $InstitutionShiftsResults = $lookedUpTable
            ->find()
            ->select([
                'id' => 'StaffPositionGrades.id',
                'name' => 'StaffPositionGrades.name'
            ])
            ->enableAutoFields(false)//POCOR-8856
            ->all();
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        if (!$InstitutionShiftsResults->isEmpty()) {
            $modelData = $InstitutionShiftsResults->toArray();
            foreach ($modelData as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->id
                ];
            }
        }
    }

    //POCOR-7711 :: End

    public function onImportGetFTEId(Event $event, $cellValue)
    {
        return $this->getFteId($cellValue);
    }

    protected function getFteId($value)
    {
        $id = '';
        foreach ($this->ftes as $key => $fte) {
            if ($fte['value'] == $value) {
                $id = $fte['value'];
                break;
            }
        }
        return $id;
    }

    public function onImportPopulateFTEData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $translatedReadableCol = $this->getExcelLabel('Imports', 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];
        foreach ($this->ftes as $fte) {
            $data[$columnOrder]['data'][] = [
                $fte['name'],
                $fte[$lookupColumn]
            ];
        }
    }

    public function onImportPopulateInstitutionPositionsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $institutionId = ($this->_institution instanceof Entity) ? $this->_institution->id : false;
        //POCOR-8947 -- START
        if (!$institutionId) {
            $institutionId = $this->getQueryString('institution_id');
        }
        //POCOR-8947 -- END
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);//POCOR-8856
        $activeStatusId = $this->Workflow->getStepsByModelCode($lookedUpTable->getRegistryAlias(), 'ACTIVE');//POCOR-8856

        $modelData = $lookedUpTable->find();
        $modelData = $modelData
            ->select([
                $lookedUpTable->aliasField('position_no'),
                'StaffPositionTitles.name',
                'StaffPositionTitles.type',
                'Statuses.name',
//                        'is_homeroom' =>'Staff.is_homeroom',//POCOR-7260
                // $lookedUpTable->aliasField('is_homeroom'), //POCOR-7260
                'total_fte' => $modelData->func()->sum('InstitutionStaff.FTE')
            ])
            ->contain(['StaffPositionTitles', 'Statuses'])
            ->leftJoin(['InstitutionStaff' => 'institution_staff'], [
                'InstitutionStaff.institution_position_id = ' . $lookedUpTable->aliasField('id'),
                'OR' => [
                    'DATE(InstitutionStaff.end_date) > DATE(NOW())',
                    'InstitutionStaff.end_date IS NULL'
                ]
            ])
            ->leftJoin(['Staff' => 'institution_staff'], [ //POCOR-7260
                'Staff.institution_position_id = ' . $lookedUpTable->aliasField('id')
            ])
            ->where([
                $lookedUpTable->aliasField('institution_id') => $institutionId,
                $lookedUpTable->aliasField('status_id IN ') => $activeStatusId
            ])
            ->group($lookedUpTable->aliasField('id'))
            ->having([
                'OR' => [
                    'total_fte < 1',
                    'total_fte IS NULL' //FTE not used at all
                ]
            ])
            ->toArray();

        $codeLabel = $this->getExcelLabel($lookedUpTable, 'code');
        $typeLabel = $this->getExcelLabel($lookedUpTable, 'type');
        $nameLabel = $this->getExcelLabel($lookedUpTable, 'name');
        $statusLabel = $this->getExcelLabel($lookedUpTable, 'status');
//         $isHomeroomLabel = $this->getExcelLabel($lookedUpTable, 'is_homeroom');

        $yesNoOptions = $this->getSelectOptions('general.yesno');

        $data[$columnOrder]['lookupColumn'] = 1;
        // POCOR-9080 start
        $data[$columnOrder]['data'][] = [$codeLabel, $nameLabel, $typeLabel, $statusLabel
//            , $isHomeroomLabel
        ];
        // POCOR-9080 end
        if (!empty($modelData)) {
            foreach ($modelData as $row) {
                $positionTitleType = $row->staff_position_title->type;
                if ($positionTitleType) {
                    $positionTitleType = __('Teaching');
                } else {
                    $positionTitleType = __('Non-Teaching');
                }
                $data[$columnOrder]['data'][] = [
                    $row->position_no,
                    $row->staff_position_title->name,
                    $positionTitleType,
                    $row->status->name,
//                    $yesNoOptions[$row->is_homeroom]
                ];
            }
        }
    }

    public function onImportPopulateStaffData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        unset($data[$columnOrder]);
    }

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        // POCOR-9080 start
        $tempRow = $tempRow->getArrayCopy();
        $staff_id = $tempRow['staff_id'];
        $error = false;
        if (empty($staff_id)) {
            $error = true;
//            return false;
        }
        try {
            $staff = $this->Staff->get($staff_id);
        } catch (RecordNotFoundException $e) {
            $rowInvalidCodeCols['staff_id'] = __('No such staff in the system');
            $error = true;
        } catch (InvalidPrimaryKeyException $e) {
            $rowInvalidCodeCols['staff_id'] = __('Invalid OpenEMIS ID');
            $error = true;
        } catch (\Exception $e) {
            $rowInvalidCodeCols['staff_id'] = __('Error: ') . $e->getMessage();
            $error = true;
        }
        if (empty($staff->is_staff)) {
            $rowInvalidCodeCols['staff_id'] = __('This personnel is not a staff');
            $error = true;
        }
        $tempRow['staff_name'] = $staff_id;

        //logic to check whether staff tried to be imported from one institution to another.
        if (is_numeric($staff_id)) {
            $staffRecord = $this->InstitutionStaff
                ->find()
                ->select([
                    'institution_id'
                ])
                ->matching('StaffStatuses', function ($q) {
                    return $q->where(['StaffStatuses.code' => 'ASSIGNED']);
                })
                ->where([$this->InstitutionStaff->aliasField('staff_Id') => $staff_id])
                ->distinct() //to cater when staff have few position on same institution
                ->toArray();

            //the result of institution_id can be array of multiple institution where the staff is assigned to
            foreach ($staffRecord as $key => $value) {
                $institutionList[] = $staffRecord[$key]['institution_id'];
            }

            if ((isset($institutionList)) && (!is_numeric(array_search($this->_institution->id, $institutionList)))) { //if the current session not on the institution list on where the staff assigned to.
                $rowInvalidCodeCols['staff_id'] = __('The staff is already assigned to another school');
                $error = true;
            }
        }
        $institutionId = $this->getQueryString('institution_id');
        $this->_institution = $this->Institutions->get($institutionId);
        if (!$this->_institution instanceof Entity) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $error = true;
        } elseif (!$this->_institution->date_opened instanceof DateTimeInterface) {
            $rowInvalidCodeCols['institution_id'] = __('Institution has an invalid date opened. Please rectify that before trying to import staff');
            $error = true;
        }
        $tempRow['institution_id'] = $this->_institution->id;

        if (empty($tempRow['start_date'])) {
            Log::debug(print_r('Look no start' . $tempRow, true));
            $rowInvalidCodeCols['start_date'] = __('No start date specified for staff');
            $error = true;
        }

// Attempt to create a FrozenDate from the input
        $formattedDate = FrozenDate::createFromFormat('d/m/Y', $tempRow['start_date']);

// Check if the date was parsed successfully
        $errors = FrozenDate::getLastErrors();
        if ($formattedDate === false || !empty($errors['warning_count']) || !empty($errors['error_count'])) {
            $rowInvalidCodeCols['start_date'] = __('Unknown date format');
            $error = true;
        }

// Confirm instance type and business logic
        if (!$formattedDate instanceof DateTimeInterface) {
            $rowInvalidCodeCols['start_date'] = __('Invalid date instance');
            $error = true;
        }

        if ($formattedDate->lt($this->_institution->date_opened)) {
            $rowInvalidCodeCols['start_date'] = __('Start Date should be later than Institution Date Opened');
            $error = true;
        }

// Assign parsed date and year
        $tempRow['start_date'] = $formattedDate->toDateTimeString();
        $tempRow['start_year'] = $formattedDate->year;

        if (!$this->checkInstitutionPosition($this->_institution->id, $tempRow['institution_position_id'])) {
            $rowInvalidCodeCols['institution_position_id'] = __('Position is not Valid for the Current Institution');
            $error = true;
        }

        if (array_key_exists("position_type", $tempRow)) {
            if ($tempRow['position_type'] == 'PART_TIME') {
                if ($tempRow['FTE'] == 1) {
                    $rowInvalidCodeCols['FTE'] = __('FTE cannot be 100% if Position Type is Part Time. Please select a value other than 1');
                    $error = true;
                }
            } else { //for fulltime
                $tempRow['FTE'] = 1;
            }
        }
        if ($error) {
            Log::debug('ImportStaffTable::onImportModelSpecificValidation()');
            Log::debug('ImportStaffTable::onImportModelSpecificValidation() rowInvalidCodeCols: ' . print_r($rowInvalidCodeCols, true));
            Log::debug('ImportStaffTable::onImportModelSpecificValidation() tempRow has error: ' . print_r($tempRow, true));
            return false;
        }
        return true;
    }

    private function checkInstitutionPosition($institutionId, $positionId)
    {
        $InstitutionPositions = TableRegistry::get('Institution.InstitutionPositions');
        $checkPosition = $InstitutionPositions
            ->find()
            ->where([
                $InstitutionPositions->aliasField('institution_id') => $institutionId,
                $InstitutionPositions->aliasField('id') => $positionId,

            ])
            ->count();
        return $checkPosition;
    }

    public function onImportSetModelPassedRecord(Event $event, Entity $clonedEntity, $columns, ArrayObject $tempPassedRecord, ArrayObject $originalRow)
    {
        $flipped = array_flip($columns);
        $key = $flipped['staff_id'];
        $tempPassedRecord['data'][$key] = $originalRow[$key];
    }
}
