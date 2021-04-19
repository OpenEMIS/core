<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use ArrayObject;
use Cake\I18n\Date;
use Cake\Collection\Collection;
use Cake\Controller\Component;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use DateTimeInterface;
use PHPExcel_Worksheet;

class ImportStaffTable extends AppTable
{
    use OptionsTrait;

    private $_institution;

    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'Staff']);
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
        $session = $this->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $institutionId = $session->read('Institution.Institutions.id');
            $this->_institution = $this->Institutions->get($institutionId);
        } else {
            $this->_institution = false;
        }
    }

    public function implementedEvents()
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

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $crumbTitle = $this->getHeader($this->alias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }

    public function onImportCheckUnique(Event $event, PHPExcel_Worksheet $sheet, $row, $columns, ArrayObject $tempRow, ArrayObject $importedUniqueCodes, ArrayObject $rowInvalidCodeCols)
    {
        $columns = new Collection($columns);
        $filtered = $columns->filter(function ($value, $key, $iterator) {
            return $value == 'staff_id';
        });
        $staffIdIndex = key($filtered->toArray());
        $staffId = $sheet->getCellByColumnAndRow($staffIdIndex, $row)->getValue();

        if (in_array($staffId, $importedUniqueCodes->getArrayCopy())) {
            $rowInvalidCodeCols['staff_id'] = $this->getExcelLabel('Import', 'duplicate_unique_key');
            return false;
        }

        $tempRow['entity'] = $this->InstitutionStaff->newEntity();
        $tempRow['start_year'] = false;
        $tempRow['staff_status_id'] = 1;
        $tempRow['end_date'] = '';
        $tempRow['end_year'] = '';
        $tempRow['institution_id'] = ($this->_institution instanceof AppTable) ? $this->_institution->id : false;
        // $tempRow['security_group_user_id'] = false;
        // Optional fields which will be validated should be set with a default value on initialisation
        $tempRow['FTE'] = 0;
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
        foreach ($this->positionTypes as $key=>$type) {
            if ($type['code']==$cellValue) {
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
        foreach($modelData as $row) {
            $data[$columnOrder]['data'][] = [
                $row['name'],
                $row[$lookupColumn]
            ];
        }
    }

    public function onImportGetFTEId(Event $event, $cellValue)
    {
        return $this->getFteId($cellValue);
    }

    protected function getFteId($value)
    {
        $id = '';
        foreach ($this->ftes as $key=>$fte) {
            if ($fte['value']==$value) {
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
        foreach($this->ftes as $fte) {
            $data[$columnOrder]['data'][] = [
                $fte['name'],
                $fte[$lookupColumn]
            ];
        }
    }

    public function onImportPopulateInstitutionPositionsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $institutionId = ($this->_institution instanceof Entity) ? $this->_institution->id : false;
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);

        $activeStatusId = $this->Workflow->getStepsByModelCode($lookedUpTable->registryAlias(), 'ACTIVE');

        //select necessary field for position which total FTE not used of not fully used based on the end_date of the staff
        $modelData = $lookedUpTable->find();
        $modelData = $modelData
                    ->select([
                        $lookedUpTable->aliasField('position_no'),
                        'StaffPositionTitles.name',
                        'StaffPositionTitles.type',
                        'Statuses.name',
                        $lookedUpTable->aliasField('is_homeroom'),
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
        $isHomeroomLabel = $this->getExcelLabel($lookedUpTable, 'is_homeroom');

        $yesNoOptions = $this->getSelectOptions('general.yesno');

        $data[$columnOrder]['lookupColumn'] = 1;
        $data[$columnOrder]['data'][] = [$codeLabel,$nameLabel, $typeLabel, $statusLabel, $isHomeroomLabel];
        if (!empty($modelData)) {
            foreach($modelData as $row) {
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
                    $yesNoOptions[$row->is_homeroom]
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
        if (empty($tempRow['staff_id'])) {
            return false;
        }
        try {
            $staff = $this->Staff->get($tempRow['staff_id']);
        } catch (RecordNotFoundException $e) {
            $rowInvalidCodeCols['staff_id'] = __('No such staff in the system');
            return false;
        } catch (InvalidPrimaryKeyException $e) {
            $rowInvalidCodeCols['staff_id'] = __('Invalid OpenEMIS ID');
            return false;
        }
        if (empty($staff->is_staff)) {
            $rowInvalidCodeCols['staff_id'] = __('This personnel is not a staff');
            return false;
        }
        $tempRow['staff_name'] = $tempRow['staff_id'];

        //logic to check whether staff tried to be imported from one institution to another.
        $staffRecord = $this->InstitutionStaff
            ->find()
            ->select([
                'institution_id'
            ])
            ->matching('StaffStatuses', function ($q) {
                return $q->where(['StaffStatuses.code' => 'ASSIGNED']);
            })
            ->where([$this->InstitutionStaff->aliasField('staff_Id') => $tempRow['staff_id']])
            ->distinct() //to cater when staff have few position on same institution
            ->toArray();

        //the result of institution_id can be array of multiple institution where the staff is assigned to
        foreach ($staffRecord as $key => $value) {
            $institutionList[] = $staffRecord[$key]['institution_id'];
        }

        if ((isset($institutionList)) && (!is_numeric(array_search($this->_institution->id, $institutionList)))) { //if the current session not on the institution list on where the staff assigned to.
            $rowInvalidCodeCols['staff_id'] = __('The staff is already assigned to another school');
            return false;
        }

        if (!$this->_institution instanceof Entity) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            return false;
        } elseif (!$this->_institution->date_opened instanceof DateTimeInterface) {
            $rowInvalidCodeCols['institution_id'] = __('Institution has an invalid date opened. Please rectify that before trying to import staff');
            return false;
        }
        $tempRow['institution_id'] = $this->_institution->id;

        if (empty($tempRow['start_date'])) {
            $rowInvalidCodeCols['start_date'] = __('No start date specified');
            return false;
        } else {
            // from string to dateObject
            $formattedDate = Date::createFromFormat('d/m/Y', $tempRow['start_date']);
            $tempRow['start_date'] = $formattedDate;

            if (!$tempRow['start_date'] instanceof DateTimeInterface) {
                $rowInvalidCodeCols['start_date'] = __('Unknown date format');
                return false;
            } elseif ($tempRow['start_date']->lt($this->_institution->date_opened)) {
                $rowInvalidCodeCols['start_date'] = __('Start Date should be later than Institution Date Opened');
                return false;
            }
        }
        $tempRow['start_year'] = $tempRow['start_date']->year;

        if (!$this->checkInstitutionPosition($this->_institution->id, $tempRow['institution_position_id'])) {
            $rowInvalidCodeCols['institution_position_id'] = __('Position is not Valid for the Current Institution');
            return false;
        }

        if (array_key_exists("position_type", $tempRow)) {
            if ($tempRow['position_type']=='PART_TIME') {
                if ($tempRow['FTE']==1) {
                    $rowInvalidCodeCols['FTE'] = __('FTE cannot be 100% if Position Type is Part Time. Please select a value other than 1');
                    return false;
                }
            } else { //for fulltime
                $tempRow['FTE'] = 1;
            }
        }
        return true;
    }

    public function onImportSetModelPassedRecord(Event $event, Entity $clonedEntity, $columns, ArrayObject $tempPassedRecord, ArrayObject $originalRow)
    {
        $flipped = array_flip($columns);
        $key = $flipped['staff_id'];
        $tempPassedRecord['data'][$key] = $originalRow[$key];
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
}
