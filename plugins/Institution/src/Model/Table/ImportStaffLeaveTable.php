<?php
namespace Institution\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use App\Model\Table\AppTable;
use Cake\Controller\Component;
use Cake\I18n\Date;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Workflow\Model\Behavior\WorkflowBehavior;

class ImportStaffLeaveTable extends AppTable
{
    private $institutionId;
    private $staffId;

    public function initialize(array $config)
    {
        $this->table('import_mapping');
        parent::initialize($config);
        
        $this->addBehavior('Import.Import', [
            'plugin' => 'Institution',
            'model' => 'StaffLeave'
        ]);

        $this->Workflows = TableRegistry::get('Workflow.Workflows');
        $this->WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
        $this->WorkflowsFilters = TableRegistry::get('Workflow.WorkflowsFilters');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = ['callable' => 'onGetBreadcrumb', 'priority' => 15];
        $events['Model.import.onImportPopulateStaffLeaveTypesData'] = 'onImportPopulateStaffLeaveTypesData';
        $events['Model.import.onImportPopulateWorkflowStepsData'] = 'onImportPopulateWorkflowStepsData';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    // public function beforeAction($event)
    // {
    //     if (!is_null($this->request->query('user_id'))) {
    //         $this->staffId = $this->request->query('user_id');
    //         $Users = TableRegistry::get('Security.Users');
    //     } else {
    //         $session = $this->request->session();
    //         if ($session->check('Staff.Staff.id')) {
    //             $this->staffId = $session->read('Staff.Staff.id');
    //             $this->request->query['user_id'] = $this->staffId;
    //         }
    //     }

    //     $session = $this->request->session();
    //     if ($session->check('Institution.Institutions.id')) {
    //         $this->institutionId = $session->read('Institution.Institutions.id');
    //     }
    // }

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        if (!is_null($persona)) {
            $this->staffId = $persona->id;
        } elseif (!is_null($request->query('user_id'))) {
            $this->staffId = $request->query('user_id');
        } else {
            $session = $request->session();
            if ($session->check('Staff.Staff.id')) {
                $this->staffId = $session->read('Staff.Staff.id');
            }
        }

        $session = $this->request->session();
        if ($session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }
        
        $staffUrl = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Staff', 'institutionId' => $this->paramsEncode(['id' => $this->institutionId])];
        $personaUrl = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffUser', 'view', $this->paramsEncode(['id' => $this->staffId])];
        $Navigation->substituteCrumb('Imports', 'Staff', $staffUrl);
        $Navigation->substituteCrumb($persona->name, $persona->name, $personaUrl);
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

    public function onImportModelSpecificValidation(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        if (!$this->institutionId) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $tempRow['institution_id'] = false;
            return false;
        }

        if (!$this->staffId) {
            $rowInvalidCodeCols['staff_id'] = __('No staff id found');
            $tempRow['staff_id'] = false;
            return false;
        }

        $tempRow['assignee_id'] = WorkflowBehavior::AUTO_ASSIGN;
        $tempRow['institution_id'] = $this->institutionId;
        $tempRow['staff_id'] = $this->staffId;

        $filterIdCondition = [$this->WorkflowsFilters->aliasField('filter_id') => $tempRow['staff_leave_type_id']];

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
            ->where([$filterIdCondition]);

        $filterStepsResult = $filterStepsQuery->all();

        if ($filterStepsResult->isEmpty()) {
            // if specific staff leave type cannot be found
            // override the existing where condition, and find with apply to all filter (0)
            $result = $filterStepsQuery
                ->where($filterIdCondition, [], true)
                ->where([
                    $this->WorkflowsFilters->aliasField('filter_id') => 0,
                    $this->WorkflowSteps->aliasField('id') => $tempRow['status_id']
                ])
                ->all();
        } else {
            // if specific staff leave type can be found
            // use the query to find if the steps existed in the workflow
            $result = $filterStepsQuery
                ->where([
                    $this->WorkflowSteps->aliasField('id') => $tempRow['status_id']
                ])
                ->all();
        }

        if ($result->isEmpty()) {
            $rowInvalidCodeCols['status_id'] = __('Selected value does not match with Staff Leave Type');
            return false;
        }

        return true;
    }
}
