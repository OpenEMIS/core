<?php
namespace Workflow\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\ORM\Behavior;
use Institution\Model\Table\InstitutionStaffTransfersTable as InstitutionStaffTransfers;

class TransferBehavior extends Behavior
{
    private $transferWorkflowIds = [];
    private $institutionTypeOptions = [];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $transferWorkflowModels = [
            'Institution.StaffTransferIn',
            'Institution.StaffTransferOut',
            'Institution.StudentTransferIn',
            'Institution.StudentTransferOut',
            'Institution.StaffReleaseIn',
            'Institution.StaffRelease'
        ];

        $this->transferWorkflowIds = $this->_table->Workflows->find()
                ->matching('WorkflowModels', function ($q) use ($transferWorkflowModels) {
                    return $q->where(['model IN' => $transferWorkflowModels]);
                })
                ->extract('id')
                ->toArray();

        $this->institutionTypeOptions = [
            InstitutionStaffTransfers::INCOMING => 'Receiving Institution',
            InstitutionStaffTransfers::OUTGOING => 'Sending Institution'
        ];
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.afterAction'] = 'indexAfterAction';
        $events['ControllerAction.Model.add.afterAction'] = 'addAfterAction';
        $events['ControllerAction.Model.edit.onInitialize'] = 'editOnInitialize';
        $events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        return $events;
    }

    public function validationTransferWorkflow(Validator $validator)
    {
        $validator = $this->_table->validationDefault($validator);
        return $validator->notEmpty('institution_owner');
    }

    public function indexAfterAction(Event $event, $data)
    {
        if (!is_null($this->_table->request->query('workflow'))) {
            $selectedWorkflowId = $this->_table->request->query('workflow');
            $this->setupInstitutionOwnerField($selectedWorkflowId);
        }
    }

    public function addAfterAction(Event $event, Entity $entity)
    {
        $model = $this->_table;
        if (isset($model->request->data[$model->alias()]['workflow_id']) && !empty($model->request->data[$model->alias()]['workflow_id'])) {
            $selectedWorkflowId = $model->request->data[$model->alias()]['workflow_id'];
            $this->setupInstitutionOwnerField($selectedWorkflowId);
        }
    }

    public function editOnInitialize(Event $event, Entity $entity)
    {
        // populate params data
        if ($entity->has('workflow_steps_params') && !empty($entity->workflow_steps_params)) {
            foreach ($entity->workflow_steps_params as $param) {
                if ($param->name == 'institution_owner') {
                    $institutionOwner = $param->value;
                } else if ($param->name == 'validate_approve') {
                    $validateApprove = $param->value;
                }
            }
            $entity->institution_owner = isset($institutionOwner) ? $institutionOwner : '';
            $entity->validate_approve = isset($validateApprove) ? $validateApprove : '';
        }
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $workflowId = $entity->workflow_id;
        $this->setupInstitutionOwnerField($workflowId);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $workflowId = $entity->workflow_id;
        $this->setupInstitutionOwnerField($workflowId);
    }

    private function setupInstitutionOwnerField($workflowId)
    {
        if (in_array($workflowId, $this->transferWorkflowIds)) {
            $this->_table->ControllerAction->field('institution_owner', ['type' => 'select', 'options' => $this->institutionTypeOptions]);
            $this->_table->ControllerAction->field('validate_approve', ['type' => 'hidden']);
            $this->_table->ControllerAction->setFieldOrder(['workflow_model_id', 'workflow_id', 'name', 'institution_owner', 'security_roles', 'category', 'is_editable', 'is_removable']);
        }
    }

    public function onGetInstitutionOwner(Event $event, Entity $entity)
    {
        $value = ' ';
        if ($entity->has('workflow_steps_params') && !empty($entity->workflow_steps_params)) {
            $params = $entity->workflow_steps_params;
            $key = array_search('institution_owner', array_column($params, 'name'));
            $value = $this->institutionTypeOptions[$params[$key]['value']];
        }
        return $value;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['submit']) && $data['submit'] == 'save') {
            if (in_array($data['workflow_id'], $this->transferWorkflowIds)) {
                $options['validate'] = 'transferWorkflow';

                // format params to save
                $params = [];
                if (isset($data['institution_owner']) && !empty($data['institution_owner'])) {
                    $params[] = ['name' => 'institution_owner', 'value' => $data['institution_owner']];
                }
                if (isset($data['validate_approve']) && !empty($data['validate_approve'])) {
                    $params[] = ['name' => 'validate_approve', 'value' => $data['validate_approve']];
                }
                if (!empty($params)) {
                    $data->offsetSet('workflow_steps_params', $params);
                    $options['associated']['WorkflowStepsParams'] = ['validate' => false];
                }
            }
        }
    }
}
