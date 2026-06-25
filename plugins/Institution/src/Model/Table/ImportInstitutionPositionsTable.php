<?php
namespace Institution\Model\Table;

use ArrayObject;
use PHPExcel_Worksheet;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Controller\Component;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Workflow\Model\Behavior\WorkflowBehavior;
use Cake\Datasource\ConnectionManager;

class ImportInstitutionPositionsTable extends AppTable
{
    use OptionsTrait;

    private $institutionId;

    public function initialize(array $config): void
    {
        $this->setTable('import_mapping');
        parent::initialize($config);

        $this->addBehavior('Import.ImportPosition', [
            'plugin' => 'Institution',
            'model' => 'InstitutionPositions'
        ]);

        $this->Workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');
        $this->InstitutionPositions = TableRegistry::getTableLocator()->get('Institution.InstitutionPositions');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';

        $events['Model.import.onImportGetHomeroomTeacherId'] = 'onImportGetHomeroomTeacherId';
        $events['Model.import.onImportPopulateStaffPositionTitlesData'] = 'onImportPopulateStaffPositionTitlesData';
        $events['Model.import.onImportPopulateShiftOptionsData'] = 'onImportPopulateShiftOptionsData'; //POCOR-7684
        $events['Model.import.onImportPopulateHomeroomTeacherData'] = 'onImportPopulateHomeroomTeacherData';
        $events['Model.import.onImportPopulateWorkflowStepsData'] = 'onImportPopulateWorkflowStepsData';
        $events['Model.import.onImportSetModelPassedRecord'] = 'onImportSetModelPassedRecord';
        $events['Model.import.onImportModelSpecificValidation'] = 'onImportModelSpecificValidation';
        return $events;
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona)
    {
        // POCOR-7799 start
        $queryString = $this->getQueryString();
        $institutionId = $queryString['institution_id'];
        $this->institutionId = $institutionId;
        // POCOR-7799 end
        $crumbTitle = $this->getHeader($this->getAlias());
        $Navigation->substituteCrumb($crumbTitle, $crumbTitle);
    }
    //POCOR-7684:: Start
    public function onImportPopulateShiftOptionsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . "InstitutionShifts");
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $periodEntity = $AcademicPeriods->getCurrent();
        // POCOR-7799 start
        $queryString = $this->getQueryString();
        $institutionId = $queryString['institution_id'];
        // POCOR-7799 end
        $InstitutionShiftsResults = $lookedUpTable
            ->find()
            ->contain(['ShiftOptions','AcademicPeriods'])
            ->select([
                'shift_id' => $lookedUpTable->aliasField('id'),
                'id' => 'ShiftOptions.id',
                'name' => 'ShiftOptions.name'
            ])
            ->where([
                $lookedUpTable->aliasField('academic_period_id') => $periodEntity,
                $lookedUpTable->aliasField('location_institution_id') => $institutionId,
            ])
//            ->autoFields(false) start
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
    //POCOR-7684:: End

    public function onImportGetHomeroomTeacherId(EventInterface $event, $cellValue)
    {
        $options = $this->getSelectOptions('general.yesno');
        foreach ($options as $key => $value) {
            if ($cellValue == $key) {
                return $cellValue;
            }
        }
        return null;
    }

    public function onImportPopulateStaffPositionTitlesData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);

        $staffTitleResults = $lookedUpTable
            ->find()
            ->innerJoinWith('SecurityRoles')
            ->select([
                'type' => $lookedUpTable->aliasField('type'),
                'id' => $lookedUpTable->aliasField('id'),
                'name' => $lookedUpTable->aliasField('name')
            ])
            ->order([
                $lookedUpTable->aliasField('type') => 'DESC',
                $lookedUpTable->aliasField('order'),
            ])
//            ->autoFields(false) // POCOR-779
            ->all();

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 3;
        $data[$columnOrder]['data'][] = [__('Staff Type'), $translatedReadableCol, $translatedCol];

        if (!$staffTitleResults->isEmpty()) {
            $modelData = $staffTitleResults->toArray();

            foreach ($modelData as $row) {
                $teachingType = $row->type == 0 ? __('Non-Teaching') : __('Teaching');

                $data[$columnOrder]['data'][] = [
                    $teachingType,
                    $row->name,
                    $row->id
                ];
            }
        }
    }

    public function onImportPopulateHomeroomTeacherData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $translatedReadableCol = $this->getExcelLabel('Homeroom Teacher', 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];

        $options = $this->getSelectOptions('general.yesno');
        foreach ($options as $key => $value) {
            $data[$columnOrder]['data'][] = [
                $value,
                $key
            ];
        }
    }

    public function onImportPopulateWorkflowStepsData(EventInterface $event, $lookupPlugin, $lookupModel, $lookupColumn, $translatedCol, ArrayObject $data, $columnOrder)
    {
        $lookedUpTable = TableRegistry::getTableLocator()->get($lookupPlugin . '.' . $lookupModel);

        $workflowResult = $this->Workflows
            ->find()
            ->select([
                'workflow_id' => $this->Workflows->aliasField('id'),
                'workflow_step_id' => $lookedUpTable->aliasField('id'),
                'workflow_step_name' => $lookedUpTable->aliasField('name')
            ])
            ->matching('WorkflowModels', function ($q) {
                return $q->where(['WorkflowModels.model' => 'Institution.InstitutionPositions']);
            })
            ->matching($lookedUpTable->getAlias())
            ->order([
                $this->Workflows->aliasField('name'),
                $lookupModel.'.category'
            ])
            ->all();

        $translatedReadableCol = $this->getExcelLabel($lookedUpTable, 'name');
        $data[$columnOrder]['lookupColumn'] = 2;
        $data[$columnOrder]['data'][] = [$translatedReadableCol, $translatedCol];

        if (!$workflowResult->isEmpty()) {
            $modelData = $workflowResult->toArray();

            foreach ($modelData as $row) {
                $data[$columnOrder]['data'][] = [
                    $row->workflow_step_name,
                    $row->workflow_step_id
                ];
            }
        }
    }

    public function onImportSetModelPassedRecord(EventInterface $event, Entity $clonedEntity, $columns, ArrayObject $tempPassedRecord, ArrayObject $originalRow)
    {
        $flipped = array_flip($columns);
        $key = $flipped['position_no'];
        $tempPassedRecord['data'][$key] = $clonedEntity->position_no;
    }

    //POCOR-9472 code change
    public function onImportModelSpecificValidation(EventInterface $event, $references, ArrayObject $tempRow, ArrayObject $originalRow,ArrayObject $rowInvalidCodeCols) 
    {
        $queryString = $this->getQueryString();
        $institutionId = $queryString['institution_id'] ?? null;
        if (empty($institutionId)) {
            $rowInvalidCodeCols['institution_id'] = __('No active institution');
            $tempRow['institution_id'] = false;
            return false;
        }
        $this->institutionId = $institutionId;
        $tempRow['institution_id'] = $institutionId;

        // POCOR-7417 Auto-assign user
        $conn = ConnectionManager::get('default');
        $status = $tempRow['status_id'];

        $sqlStr = "
            SELECT MIN(sgu.security_user_id) AS user_id
            FROM security_group_users sgu
            WHERE sgu.security_group_id IN
            (
                SELECT sgi.security_group_id
                FROM security_group_institutions sgi
                WHERE sgi.institution_id = $institutionId

                UNION

                SELECT sga.security_group_id
                FROM security_group_areas sga
                INNER JOIN institutions i
                    ON i.area_id = sga.area_id
                   AND i.id = $institutionId

                UNION

                SELECT i.security_group_id
                FROM institutions i
                WHERE i.id = $institutionId
            )
            AND sgu.security_role_id IN
            (
                SELECT wsr.security_role_id
                FROM workflow_steps_roles wsr
                INNER JOIN workflow_steps ws ON ws.id = wsr.workflow_step_id
                INNER JOIN workflows w ON w.id = ws.workflow_id
                INNER JOIN workflow_models wm ON wm.id = w.workflow_model_id
                WHERE wm.name LIKE 'Institutions > Positions'
                  AND ws.id = $status
            )
        ";

        $rows = $conn->execute($sqlStr)->fetch('assoc');
        $fallbackUserId = $rows['user_id'] ?? null;

        // Default AUTO_ASSIGN
        $tempRow['assignee_id'] = WorkflowBehavior::AUTO_ASSIGN;

        if ($tempRow['assignee_id'] == WorkflowBehavior::AUTO_ASSIGN && !empty($fallbackUserId)) {
            $tempRow['assignee_id'] = $fallbackUserId;
        }

        // POCOR-7800 Workflow-based assignment

        if (empty($tempRow['assignee_id'])) {
            $WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
            $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
            $Institutions = TableRegistry::get('Institution.Institutions');

            $stepRoles = $WorkflowStepsRoles->getRolesByStep($tempRow['status_id']);

            //REQUIRED CHECK
            if (empty($stepRoles)) {
                if (!empty($fallbackUserId)) {
                    $tempRow['assignee_id'] = $fallbackUserId;
                } else {
                    $rowInvalidCodeCols['assignee_id'] = __(
                        'No assignee found. Workflow roles are not configured for this status.'
                    );
                    return false;
                }
            } else {
                $institutionObj = $Institutions
                    ->find()
                    ->where([$Institutions->aliasField('id') => $institutionId])
                    ->contain(['Areas'])
                    ->first();

                $securityGroupId = $institutionObj->security_group_id;
                $areaObj = $institutionObj->area;

                // School-based assignees
                $where = [
                    'OR' => [
                        [$SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId],
                        ['Institutions.id' => $institutionId]
                    ],
                    $SecurityGroupUsers->aliasField('security_role_id IN') => $stepRoles
                ];

                $schoolBasedAssigneeOptions = $SecurityGroupUsers
                    ->find('userList', ['where' => $where])
                    ->leftJoinWith('SecurityGroups.Institutions')
                    ->toArray();

                // Region-based assignees
                $where = [
                    $SecurityGroupUsers->aliasField('security_role_id IN') => $stepRoles
                ];

                $regionBasedAssigneeOptions = $SecurityGroupUsers
                    ->find('userList', ['where' => $where, 'area' => $areaObj])
                    ->toArray();

                $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;

                if (!empty($assigneeOptions)) {
                    $tempRow['assignee_id'] = array_key_first($assigneeOptions);
                }
            }
        }

        // validation
        if (empty($tempRow['assignee_id'])) {
            $rowInvalidCodeCols['assignee_id'] = __('Assignee could not found .');
            return false;
        }

        if (!isset($tempRow['position_no'])) {
            $tempRow['position_no'] = $this->InstitutionPositions
                ->getUniquePositionNo($this->institutionId);
        }

        return true;
    }


    public function onImportModelSpecificValidationbkp(Event $event, $references, ArrayObject $tempRow, ArrayObject $originalRow, ArrayObject $rowInvalidCodeCols)
    {
            //POCOR-7417:Start
            $conn = ConnectionManager::get('default');
            $status = $tempRow['status_id'];
            $queryString = $this->getQueryString();
            $institutionId = $queryString['institution_id'];
            $insId = $institutionId;
            $this->institutionId = $institutionId;
            $sqlStr = "SELECT MIN(security_group_users.security_user_id)
            FROM security_group_users
            WHERE security_group_users.security_group_id IN
            (
                SELECT security_group_institutions.security_group_id
                FROM security_group_institutions
                WHERE security_group_institutions.institution_id = $insId

                UNION

                SELECT security_group_areas.security_group_id
                FROM security_group_areas
                INNER JOIN institutions
                ON institutions.area_id = security_group_areas.area_id
                AND institutions.id = $insId

                UNION

                SELECT institutions.security_group_id
                FROM institutions
                WHERE institutions.id = $insId
            )
            AND security_group_users.security_role_id IN
            (
                SELECT workflow_steps_roles.security_role_id
                FROM workflow_steps_roles
                INNER JOIN workflow_steps
                ON workflow_steps.id = workflow_steps_roles.workflow_step_id
                INNER JOIN workflows
                ON workflows.id = workflow_steps.workflow_id
                INNER JOIN workflow_models
                ON workflow_models.id = workflows.workflow_model_id
                WHERE workflow_models.name LIKE 'Institutions > Positions'
                AND workflow_steps.id = $status -- This values is coming from Template > References > Status
            )";
            $result = $conn->execute($sqlStr);
            $rows = $result->fetch('assoc');
            $userRow = $rows['MIN(security_group_users.security_user_id)'];
            //POCOR-7417:end
            if (!$this->institutionId) {
                $rowInvalidCodeCols['institution_id'] = __('No active institution');
                $tempRow['institution_id'] = false;
                return false;
            }

            $tempRow['assignee_id'] = WorkflowBehavior::AUTO_ASSIGN;
            //POCOR-7417:Start
            if($tempRow['assignee_id'] == '-1'){
                $tempRow['assignee_id'] = $userRow;
            }
            //POCOR-7417:end
            $tempRow['institution_id'] = $this->institutionId;

            //POCOR-7800::Start
            if(empty($tempRow['assignee_id'])){
                $WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
                $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                $Institutions = TableRegistry::get('Institution.Institutions');
                $stepRoles = $WorkflowStepsRoles->getRolesByStep($tempRow['status_id']);
                $institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $tempRow['institution_id']])->contain(['Areas'])->first();
                $securityGroupId = $institutionObj->security_group_id;
                $areaObj = $institutionObj->area;


                $where = [
                    'OR' => [[$SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId],
                            ['Institutions.id' => $institutionId]],
                    $SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles
                ];
                $schoolBasedAssigneeQuery = $SecurityGroupUsers
                        ->find('userList', ['where' => $where])
                        ->leftJoinWith('SecurityGroups.Institutions');
                $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();


                $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                $regionBasedAssigneeQuery = $SecurityGroupUsers
                                                ->find('UserList', ['where' => $where, 'area' => $areaObj]);
                $regionBasedAssigneeOptions = $regionBasedAssigneeQuery->toArray();
                $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;
                $tempRow['assignee_id'] = array_key_first($assigneeOptions);
            }
            //POCOR-7800::End

            if (!isset($tempRow['position_no'])) {
                $tempRow['position_no'] = $this->InstitutionPositions->getUniquePositionNo($this->institutionId);
            }

            return true;
    }
}
