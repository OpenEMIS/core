<?php
namespace Institution\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use App\Model\Table\AppTable;
use Cake\Controller\Component;
use Cake\I18n\Date;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Workflow\Model\Behavior\WorkflowBehavior;

class ImportStaffLeaveTable extends AppTable
{
    private $institutionId;
    private $staffId;
    private $loggedInUserId;

    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.Import', [
            'plugin' => 'Institution',
            'model' => 'StaffLeave'
        ]);

        $this->Workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');
        $this->WorkflowSteps = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
        $this->WorkflowsFilters = TableRegistry::getTableLocator()->get('Workflow.WorkflowsFilters');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = ['callable' => 'onGetBreadcrumb', 'priority' => 15];
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons'; //POCOR-9584
        $events['Model.import.onImportPopulateStaffLeaveTypesData'] = 'onImportPopulateStaffLeaveTypesData';
        $events['Model.import.onImportPopulateWorkflowStepsData'] = 'onImportPopulateWorkflowStepsData';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    //POCOR-9584: start - beforeAction reads institution_id, staff_id, and logged-in user ID from
    // the encoded URL querystring. onGetBreadcrumb is NOT dispatched from StaffController, so it
    // cannot be relied upon. This follows the same pattern as ImportStaffAttendancesTable.
    public function beforeAction($event)
    {
        $session = $this->request->getSession();

        $this->institutionId = $this->ControllerAction->getQueryString('institution_id');
        $this->staffId = $this->ControllerAction->getQueryString('staff_id');
        if (!$this->staffId) {
            $this->staffId = $this->ControllerAction->getQueryString('user_id');
        }

        // Fall back to existing session keys managed by the controllers (read-only — no multi-tab risk).
        // The results redirect URL only encodes institution_id, so staff_id is not in the URL on that page.
        // We read Staff.Staff.id (set by StaffController) but never write our own session key here.
        if (!$this->institutionId && $session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }
        if (!$this->staffId && $session->check('Staff.Staff.id')) {
            $this->staffId = $session->read('Staff.Staff.id');
        }

        // Logged-in user — fallback for assignee_id if WorkflowBehavior::AUTO_ASSIGN cannot be used
        $this->loggedInUserId = $session->read('Auth.User.id');

        //Log::debug('@ImportStaffLeave::beforeAction institutionId=' . var_export($this->institutionId, true)
        //    . ' staffId=' . var_export($this->staffId, true)
        //    . ' loggedInUserId=' . var_export($this->loggedInUserId, true));
    }
    //POCOR-9584: end

    //POCOR-9584: fix back button URL — ImportBehavior::setupBackButtonUrl falls into the else
    // branch for plugin='Staff' and strips all pass params, producing a bare /Staff/Staff URL
    // that has no institution_id and crashes beforeFilter. We rebuild it here.
    public function onUpdateToolbarButtons(EventInterface $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        if (!isset($toolbarButtons['back']) || !$this->institutionId) {
            return;
        }

        $encodedParams = $this->paramsEncode([
            'institution_id' => $this->institutionId,
            'staff_id'       => $this->staffId,
            'user_id'        => $this->staffId,
        ]);

        $pass = $this->request->getParam('pass');
        $firstParam = $pass[0] ?? null;

        // pass[0]='index' is required so that ControllerAction->getQueryString() reads
        // institution_id from pass[1] (its standard position). Without it the encoded
        // params land at pass[0] and getInstitutionID() finds nothing → /Staff/Staff crash.
        $toolbarButtons['back']['url'] = [ //POCOR-9584: include pass[0]='index' + encoded params at pass[1]
            'plugin'     => 'Staff',
            'controller' => 'Staff',
            'action'     => 'StaffLeave',
            0            => 'index',
            1            => $encodedParams,
        ];

        //Log::debug('@ImportStaffLeave::onUpdateToolbarButtons action=' . $action
        //    . ' firstParam=' . var_export($firstParam, true)
        //    . ' backUrl=' . json_encode($toolbarButtons['back']['url']));
    }

    public function onGetBreadcrumb(EventInterface $event, Request $request, Component $Navigation, $persona)
    {
        $session = $request->session();
        $staffName = '';

        if (!empty($persona)) {
            $this->staffId = $persona->id;
            $staffName = $persona->name;
        } elseif (!is_null($request->query('user_id'))) {
            $this->staffId = $request->query('user_id');
            $Users = TableRegistry::getTableLocator()->get('Security.Users');
            $UserEntity = $Users
                ->find()
                ->select([
                    $Users->aliasField('first_name'),
                    $Users->aliasField('middle_name'),
                    $Users->aliasField('third_name'),
                    $Users->aliasField('last_name'),
                    $Users->aliasField('preferred_name'),
                ])
                ->where([$Users->aliasField('id') => $this->staffId])
                ->first();
            $staffName = $UserEntity->name;
        } elseif ($session->check('Staff.Staff.id')) {
            $this->staffId = $session->read('Staff.Staff.id');
            $Users = TableRegistry::getTableLocator()->get('Security.Users');
            $UserEntity = $Users
                ->find()
                ->select([
                    $Users->aliasField('first_name'),
                    $Users->aliasField('middle_name'),
                    $Users->aliasField('third_name'),
                    $Users->aliasField('last_name'),
                    $Users->aliasField('preferred_name'),
                ])
                ->where([$Users->aliasField('id') => $this->staffId])
                ->first();
            $staffName = $UserEntity->name;
        }

        //POCOR-9584: start - read institution_id from URL querystring first (like ImportStaffAttendancesTable),
        // fall back to session for backward compatibility
        $this->institutionId = $this->ControllerAction->getQueryString('institution_id');
        if (!$this->institutionId && $session->check('Institution.Institutions.id')) {
            $this->institutionId = $session->read('Institution.Institutions.id');
        }
        //POCOR-9584: end

        //Log::debug('@ImportStaffLeave::onGetBreadcrumb institutionId=' . var_export($this->institutionId, true)
        //    . ' staffId=' . var_export($this->staffId, true));

        $staffUrl = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Staff', 'institutionId' => $this->paramsEncode(['id' => $this->institutionId])];
        $personaUrl = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'StaffUser', 'view', $this->paramsEncode(['id' => $this->staffId])];
        $Navigation->substituteCrumb('Imports', 'Staff', $staffUrl);
        $Navigation->substituteCrumb($staffName, $staffName, $personaUrl);
    }

    public function onImportPopulateStaffLeaveTypesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        //Log::debug('@ImportStaffLeave::onImportPopulateStaffLeaveTypesData lookupPlugin=' . $lookupPlugin . ' lookupModel=' . $lookupModel);
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);

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

    public function onImportPopulateWorkflowStepsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        //Log::debug('@ImportStaffLeave::onImportPopulateWorkflowStepsData lookupPlugin=' . $lookupPlugin . ' lookupModel=' . $lookupModel);
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);

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
                [$this->WorkflowsFilters->getAlias() => $this->WorkflowsFilters->getTable()],
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

    /**
     * onImportPopulateAcademicPeriodsData method description.
     *
     * @param EventInterface $event The Event to use.
     * @param  $lookupPlugin value.
     * @param  $lookupModel value.
     * @param  $translatedCol value.
     * @param  $lookupColumn value.
     * @param  array|\ArrayObject $data .
     * @param $columnOrder .
     */
    public function onImportPopulateAcademicPeriodsData(
            EventInterface $event,
            $lookupPlugin,
            $lookupModel,
            $lookupColumn,
            $translatedCol,
            ArrayObject $data,
            $columnOrder
    ) {
        //Log::debug('@ImportStaffLeave::onImportPopulateAcademicPeriodsData lookupPlugin=' . $lookupPlugin . ' lookupModel=' . $lookupModel);
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);
        $modelData = $lookedUpTable->getAvailableAcademicPeriods(false);
        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $startDateLabel = $this->getExcelLabel($lookedUpTable, 'start_date');
        $endDateLabel = $this->getExcelLabel($lookedUpTable, 'end_date');
        $data[$columnOrder]['lookupColumn'] = 4;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $startDateLabel, $endDateLabel, $translatedCol];

        if (!empty($modelData)) {
            foreach ($modelData as $row) {

                if ($row->academic_period_level_id == 1) {
                    //validate that only period level "year" will be shown
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

    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
        //Log::debug('@ImportStaffLeave::onImportModelSpecificValidation institutionId=' . var_export($this->institutionId, true)
        //    . ' staffId=' . var_export($this->staffId, true)
        //    . ' loggedInUserId=' . var_export($this->loggedInUserId, true)
        //    . ' tempRow.status_id=' . var_export($tempRow['status_id'] ?? null, true)
        //    . ' tempRow.staff_leave_type_id=' . var_export($tempRow['staff_leave_type_id'] ?? null, true));

        if (!$this->institutionId) {
            Log::warning('@ImportStaffLeave::onImportModelSpecificValidation - no institutionId, aborting row');
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $tempRow['institution_id'] = false;
            return false;
        }

        if (!$this->staffId) {
            Log::warning('@ImportStaffLeave::onImportModelSpecificValidation - no staffId, aborting row');
            $rowInvalidCodeCols['staff_id'] = __('No staff id found');
            $tempRow['staff_id'] = false;
            return false;
        }

        //POCOR-9584: prefer workflow AUTO_ASSIGN; only fall back to logged-in user if AUTO_ASSIGN is unavailable
        $tempRow['assignee_id'] = WorkflowBehavior::AUTO_ASSIGN ?: $this->loggedInUserId;
        $tempRow['institution_id'] = $this->institutionId;
        $tempRow['staff_id'] = $this->staffId;

        //Log::debug('@ImportStaffLeave::onImportModelSpecificValidation - ids set OK, assignee_id='
        //    . var_export($tempRow['assignee_id'], true) . ', checking workflow step');

        $filterIdCondition = [$this->WorkflowSteps->aliasField('id') => $tempRow['status_id']];

        // find workflow for the specific staff leave type
        $filterStepsQuery = $this->Workflows
            ->find()
            ->matching('WorkflowModels', function ($q) {
                return $q->where(['WorkflowModels.model' => 'Institution.StaffLeave']);
            })
            ->matching($this->WorkflowSteps->getAlias())
            ->leftJoin(
                [$this->WorkflowsFilters->getAlias() => $this->WorkflowsFilters->getTable()],
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
            //Log::debug('@ImportStaffLeave::onImportModelSpecificValidation - no type-specific workflow, tried apply-to-all, result count=' . count($result->toArray()));
        } else {
            // if specific staff leave type can be found, use the query to find if the steps existed in the workflow
            $result = $filterStepsQuery
                ->where($filterIdCondition)
                ->all();
            //Log::debug('@ImportStaffLeave::onImportModelSpecificValidation - found type-specific workflow, result count=' . count($result->toArray()));
        }

        if ($result->isEmpty()) {
            Log::warning('@ImportStaffLeave::onImportModelSpecificValidation - status_id does not match leave type workflow');
            $rowInvalidCodeCols['status_id'] = __('Selected value does not match with Staff Leave Type');
            return false;
        }

        //Log::debug('@ImportStaffLeave::onImportModelSpecificValidation - row validated OK');
        return true;
    }
}
