<?php
namespace Workflow\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\ORM\Behavior;
use Institution\Model\Table\InstitutionStaffTransfersTable as InstitutionStaffTransfers;

class StaffTransferBehavior extends Behavior
{
    private $transferWorkflowIds = [];
    private $institutionOwnerOptions = [];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->transferWorkflowIds = $this->_table->Workflows->find()
                ->matching('WorkflowModels')
                ->where(['WorkflowModels.model' => 'Institution.InstitutionStaffTransfers'])
                ->extract('id')
                ->toArray();

        $this->institutionOwnerOptions = [
            InstitutionStaffTransfers::INCOMING => 'Incoming',
            InstitutionStaffTransfers::OUTGOING => 'Outgoing'
        ];
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.afterAction'] = 'indexAfterAction';
        $events['ControllerAction.Model.add.afterAction'] = 'addAfterAction';
        $events['ControllerAction.Model.edit.afterAction'] = 'editAfterAction';
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        return $events;
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
        if (!is_null($this->_table->request->query('workflow'))) {
            $selectedWorkflowId = $this->_table->request->query('workflow');
            $this->setupInstitutionOwnerField($selectedWorkflowId);
        }
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $workflowId = $entity->workflow_id;
        $this->setupInstitutionOwnerField($workflowId, $entity);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $workflowId = $entity->workflow_id;
        $this->setupInstitutionOwnerField($workflowId, $entity);
    }

    private function setupInstitutionOwnerField($workflowId, Entity $entity = null)
    {
        if (in_array($workflowId, $this->transferWorkflowIds)) {
            $attr = [];
            if (!is_null($entity)) {
                $attr['entity'] = $entity;
            }

            $this->_table->ControllerAction->field('institution_owner', $attr);
            $this->_table->ControllerAction->setFieldOrder(['workflow_model_id', 'workflow_id', 'name', 'institution_owner', 'security_roles', 'category', 'is_editable', 'is_removable']);
        }
    }

    public function onGetInstitutionOwner(Event $event, Entity $entity)
    {
        $value = '';
        if (!empty($entity->params)) {
            $params = json_decode(html_entity_decode($entity->params), true);
            $value = array_key_exists('institution_owner', $params) ? $this->institutionOwnerOptions[$params['institution_owner']] : '';
        }
        return $value;
    }

    public function onUpdateFieldInstitutionOwner(Event $event, array $attr, $action, Request $request)
    {
        if (in_array($action, ['add', 'edit'])) {
            if ($action == 'edit' && !empty($attr['entity']->params)) {
                $params = json_decode($attr['entity']->params, true);
                $institutionOwner = array_key_exists('institution_owner', $params) ? $params['institution_owner'] : '';
                $attr['default'] = $institutionOwner;
            }

            $attr['type'] = 'select';
            $attr['options'] = $this->institutionOwnerOptions;
            return $attr;
        }
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['submit']) && $data['submit'] == 'save') {
            if (in_array($data['workflow_id'], $this->transferWorkflowIds)) {
                $validator = $this->_table->validator();
                $validator->notEmpty('institution_owner');

                if (isset($data['institution_owner']) && !empty($data['institution_owner'])) {
                    $institutionOwner = ['institution_owner' => $data['institution_owner']];
                    $data->offsetSet('params', json_encode($institutionOwner));
                }
            }
        }
    }
}
