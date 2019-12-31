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
use Workflow\Model\Behavior\WorkflowBehavior;

class ImportStaffAttendancesTable extends AppTable {
    private $institutionId = false;

    public function initialize(array $config) {
        $this->table('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', ['plugin'=>'Institution', 'model'=>'StaffAbsences',
            'backUrl' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'InstitutionStaffAttendances']]);

        $this->StaffAbsences = TableRegistry::get('Institution.StaffAbsences');
        $this->Institutions = TableRegistry::get('Institution.Institutions');
        $this->Staff = TableRegistry::get('Institution.Staff');
        $this->Users = TableRegistry::get('User.Users');
        $this->AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        $this->Workflows = TableRegistry::get('Workflow.Workflows');
        $this->WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
        $this->WorkflowsFilters = TableRegistry::get('Workflow.WorkflowsFilters');
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
            'Model.import.onImportPopulateStaffLeaveTypesData' => 'onImportPopulateStaffLeaveTypesData',
            'Model.import.onImportPopulateAcademicPeriodsData' => 'onImportPopulateAcademicPeriodsData',
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

    public function onImportPopulateStaffLeaveTypesData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);

        $result = $lookedUpTable
            ->find('all')
            ->select([
                $lookedUpTable->aliasField('name'),
                $lookedUpTable->aliasField($lookupColumn)
            ])
            ->order([
                $lookedUpTable->aliasField('name')
            ])
            ->all();

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];

        if (!$result->isEmpty()) {
            $modelData = $result->toArray();
            foreach ($modelData as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->name,
                    $row->{$lookupColumn}
                ];
            }
        }
    }

    public function onImportPopulateWorkflowStepsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);

        $workflowResult = $this->Workflows
            ->find()
            ->select([
                'workflow_id' => $this->Workflows->aliasField('id'),
                'workflow_name' => $this->Workflows->aliasField('name'),
                'workflow_step_id' => $lookedUpTable->aliasField('id'),
                'workflow_step_name' => $lookedUpTable->aliasField('name'),
                'workflow_filter_id' => $this->WorkflowsFilters->aliasField('filter_id')
            ])
            ->matching('WorkflowModels', function ($q) {
                return $q->where(['WorkflowModels.model' => 'Institution.StaffLeave']);
            })
            ->matching($lookupModel)
            ->leftJoin(
                [$this->WorkflowsFilters->alias() => $this->WorkflowsFilters->table()],
                [$this->Workflows->aliasField('id = ') . $this->WorkflowsFilters->aliasField('workflow_id')]
            )
            ->order([
                $this->Workflows->aliasField('name'),
                $lookupModel.'.category'
            ])
            ->all();

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 4;
        $data[$columnOrder]['data'][] = [__('Staff Leave Type Id'), __('Workflow'), $translatedReadableCol, $translatedCol];

        if (!$workflowResult->isEmpty()) {
            $modelData = $workflowResult->toArray();

            foreach ($modelData as $row) {
                $leaveTypeId = ($row->workflow_filter_id == 0) ? __('Apply To All') : $row->workflow_filter_id;

                $data[$columnOrder]['data'][] = [
                    $leaveTypeId,
                    $row->workflow_name,
                    $row->workflow_step_name,
                    $row->workflow_step_id
                ];
            }
        }
    }

    public function onImportPopulateAcademicPeriodsData(Event $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder) 
    {
        $lookedUpTable = TableRegistry::get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->getAvailableAcademicPeriods(false);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $startDateLabel = $this->getExcelLabel($lookedUpTable, 'start_date');
        $endDateLabel = $this->getExcelLabel($lookedUpTable, 'end_date');
        $data[$columnOrder]['lookupColumn'] = 4;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $startDateLabel, $endDateLabel, $translatedCol];
        if (!empty($modelData)) {
            foreach($modelData as $row) {
                if ($row->academic_period_level_id == 1) { //validate that only period level "year" will be shown
                    $date = $row->start_date;
                    $data[$columnOrder]['data'][] = [
                        $row->name,
                        $row->start_date->format('d/m/Y'),
                        $row->end_date->format('d/m/Y'),
                        $row->{$lookupColumn}
                    ];
                }
            }
        }
    }   

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols) {
        if (!$this->institutionId) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $tempRow['institution_id'] = false;
            return false;
        }

        

        $tempRow['assignee_id'] = WorkflowBehavior::AUTO_ASSIGN;
        $tempRow['institution_id'] = $this->institutionId;

        $filterIdCondition = [$this->WorkflowSteps->aliasField('id') => $tempRow['status_id']];

        // find workflow for the specific staff leave type
        $filterStepsQuery = $this->Workflows
            ->find()
            ->matching('WorkflowModels', function ($q) {
                return $q->where(['WorkflowModels.model' => 'Institution.StaffLeave']);
            })
            ->matching($this->WorkflowSteps->alias())
            ->leftJoin(
                [$this->WorkflowsFilters->alias() => $this->WorkflowsFilters->table()],
                [$this->Workflows->aliasField('id = ') . $this->WorkflowsFilters->aliasField('workflow_id')]
            )
            ->where([$this->WorkflowsFilters->aliasField('filter_id') => $tempRow['staff_leave_type_id']]);

        $filterStepsResult = $filterStepsQuery->all();

        if ($filterStepsResult->isEmpty()) {
            // if specific staff leave type cannot be found, override the existing where condition, and find with apply to all filter (0)
            $result = $filterStepsQuery
                ->where($filterIdCondition, [], true)
                ->where([$this->WorkflowsFilters->aliasField('filter_id') => 0])
                ->all();
        } else {
            // if specific staff leave type can be found, use the query to find if the steps existed in the workflow
            $result = $filterStepsQuery
                ->where($filterIdCondition)
                ->all();
        }

        if ($result->isEmpty()) {
            $rowInvalidCodeCols['status_id'] = __('Selected value does not match with Staff Leave Type');
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