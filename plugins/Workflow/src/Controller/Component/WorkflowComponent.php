<?php
namespace Workflow\Controller\Component;

use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Utility\Inflector;
use Cake\Log\LogTrait;

class WorkflowComponent extends Component
{
    use LogTrait;

    private $controller;
    private $action;

    public $WorkflowModels;
    public $WorkflowSteps;
    public $WorkflowStatuses;
    public $WorkflowStatusesSteps;
    public $attachWorkflow = false;     // indicate whether the model require workflow
    public $hasWorkflow = false;    // indicate whether workflow is setup
    public $components = ['Auth', 'ControllerAction', 'AccessControl'];

    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
        $this->action = $this->request->params['action'];

        $this->WorkflowModels = TableRegistry::get('Workflow.WorkflowModels');
        $this->WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
        $this->WorkflowStatuses = TableRegistry::get('Workflow.WorkflowStatuses');
        $this->WorkflowStatusesSteps = TableRegistry::get('Workflow.WorkflowStatusesSteps');

        // To bypass the permission
        $session = $this->request->session();
        if ($session->check('Workflow.Workflows.models')) {
            $models = $session->read('Workflow.Workflows.models');
        } else {
            $models = $this->WorkflowModels
                ->find('list', ['keyField' => 'id', 'valueField' => 'model'])
                ->toArray();

            $session->write('Workflow.Workflows.models', $models);
        }
        // End
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.SecurityAuthorize.isActionIgnored'] = 'isActionIgnored';
        return $events;
    }

    public function isActionIgnored(Event $event, $action)
    {
        $pass = $this->request->pass;
        if (isset($pass[0]) && in_array($pass[0], ['processWorkflow', 'processReassign'])) {
            return true;
        }
    }

    /**
     *  Function to get the list of the workflow statuses base on the model name
     *
     *  @param $model The name of the model e.g. Institution.InstitutionSurveys
     *  @return array The list of the workflow statuses
     */
    public function getWorkflowStatuses($model)
    {
        $WorkflowModelTable = $this->WorkflowModels;
        return $WorkflowModelTable->getWorkflowStatuses($model);
    }

    /**
     *  Function to get the list of the workflow steps from the workflow status mappings table
     *  by a given workflow status
     *
     *  @param $workflowStatusId The workflow status id
     *  @return array The list of the workflow steps
     */
    public function getWorkflowSteps($workflowStatusId)
    {
        $WorkflowStepsTable = $this->WorkflowModels->WorkflowStatuses;
        return $WorkflowStepsTable->getWorkflowSteps($workflowStatusId);
    }

    /**
     *  Function to get the list of the workflow steps and workflow status name mapping
     *  by a given model id
     *
     *  @param string $model The name of the model e.g. Institution.InstitutionSurveys
     *  @return array The list of workflow steps status name mapping (key => workflow_step_id, value=>workflow_status_name)
     */
    public function getWorkflowStepStatusNameMappings($model)
    {
        $WorkflowStatusesTable = $this->WorkflowModels->WorkflowStatuses;
        return $WorkflowStatusesTable->getWorkflowStepStatusNameMappings($model);
    }

    /**
     *  Function to get the list of the workflow steps by a given workflow model's model and the workflow status code
     *
     *  @param string $model The name of the model e.g. Institution.InstitutionSurveys
     *  @param string $code The code of the workflow status
     *  @return array The list of workflow steps id
     */
    public function getStepsByModelCode($model, $code)
    {
        return $this->WorkflowModels
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'id'
            ])
            ->matching('WorkflowStatuses.WorkflowSteps')
            ->where([
                $this->WorkflowModels->aliasField('model') => $model,
                'WorkflowStatuses.code' => $code
            ])
            ->select(['id' => 'WorkflowSteps.id'])
            ->toArray();
    }

    /**
     *  Function to get the list of the workflow steps by a given workflow model's model
     *
     *  @param string $model The name of the model e.g. Institution.InstitutionSurveys
     *  @param array $excludedStatus The list of the workflow status code to be excluded
     *  @return array The list of workflow steps id
     */
    public function getStepsByModel($model, $excludedStatus = [])
    {
        $query = $this->WorkflowSteps
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'id'
            ])
            ->matching('Workflows.WorkflowModels')
            ->where([
                $this->WorkflowModels->aliasField('model') => $model
            ])
            ->select([
                'id' => $this->WorkflowSteps->aliasField('id')
            ]);

        if (!empty($excludedStatus)) {
            $excludedQuery = $this->WorkflowStatusesSteps
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'id'
                ])
                ->select(['id' => $this->WorkflowStatusesSteps->aliasField('workflow_step_id')])
                ->innerJoin(
                    [$this->WorkflowStatuses->alias() => $this->WorkflowStatuses->table()],
                    [
                        $this->WorkflowStatuses->aliasField('id = ') . $this->WorkflowStatusesSteps->aliasField('workflow_status_id'),
                        $this->WorkflowStatuses->aliasField("code IN ('") . implode("','", $excludedStatus) . "')"
                    ]
                )
                ->where([
                    $this->WorkflowStatusesSteps->aliasField('workflow_step_id = ') . $this->WorkflowSteps->aliasField('id')
                ]);

            $query->where(['NOT EXISTS ('.$excludedQuery->sql().')']);
        }

        $statuses = $query->toArray();

        return $statuses;
    }

    /**
     *  Function to get the list of the workflow steps where the login user has security access
     *
     *  @param array $institutionRoles The list schools and roles in each schools
     *  @param array $statusIds The list of the workflow steps id to check against
     *  @return array The list of workflow steps id
     */
    public function getAccessibleStatuses($institutionRoles, $statusIds)
    {
        $accessibleStatusIds = [];

        $WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');

        // Array to store security roles in each Workflow Step
        $stepRoles = [];
        foreach ($institutionRoles as $institutionId => $roles) {
            foreach ($statusIds as $key => $statusId) {
                if (!array_key_exists($statusId, $stepRoles)) {
                    $stepRoles[$statusId] = $WorkflowStepsRoles->getRolesByStep($statusId);
                }

                // logic to pre-insert survey in school only when user's roles is configured to access the step
                $hasAccess = count(array_intersect_key($roles, $stepRoles[$statusId])) > 0;
                if ($hasAccess) {
                    $accessibleStatusIds[$statusId] = $statusId;
                }
                // End
            }
        }
        // End

        return $accessibleStatusIds;
    }
}
