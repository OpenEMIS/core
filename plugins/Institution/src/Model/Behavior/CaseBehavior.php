<?php
namespace Institution\Model\Behavior;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\Routing\Router;

class CaseBehavior extends Behavior
{
    public function initialize(array $config)
    {
        parent::initialize($config);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        $events['ControllerAction.Model.delete.onInitialize'] = 'deleteOnInitialize';
        return $events;
    }

    public function afterSaveCommit(Event $event, Entity $entity, ArrayObject $options)
    {
        $model = $this->_table;

        $broadcaster = $model;
        $listeners = [];
        $listeners[] = TableRegistry::get('Cases.InstitutionCases');

        if (!empty($listeners)) {
            $model->dispatchEventToModels('Model.LinkedRecord.afterSave', [$entity], $broadcaster, $listeners);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $model = $this->_table;
        $showFieldBefore = isset($model->fields['modified_user_id']) ? 'modified_user_id' : 'create__user_id';

        $model->field('linked_cases', [
            'type' => 'custom_linked_cases',
            'valueClass' => 'table-full-width',
            'before' => $showFieldBefore
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $showFieldBefore = $entity->has('modified_user_id') ? 'modified_user_id' : 'create__user_id';

        $model = $this->_table;
        $model->field('linked_cases', [
            'type' => 'custom_linked_cases',
            'valueClass' => 'table-full-width',
            'before' => $showFieldBefore
        ]);
    }

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $linkedCaseQuery = $this->getLinkedCaseQuery($entity);
        $linkedCaseCount = $linkedCaseQuery->count();

        $extra['associatedRecords'][] = ['model' => 'Linked Cases', 'count' => $linkedCaseCount];
    }

    public function onGetCustomLinkedCasesElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $model = $this->_table;

        if ($action == 'index') {
            $linkedCaseQuery = $this->getLinkedCaseQuery($entity);

            $attr['value'] = $linkedCaseQuery->count();
        } else if ($action == 'view') {
            $tableHeaders = [__('Status'), __('Assignee'), __('Case Number'), __('Title')];
            $tableCells = [];

            $linkedCaseQuery = $this->getLinkedCaseQuery($entity);
            $linkedCaseResults = $linkedCaseQuery->all();
            if (!$linkedCaseResults->isEmpty()) {
                foreach ($linkedCaseResults as $key => $caseEntity) {
                    $rowData = [];

                    if (empty($caseEntity->assignee_id)) {
                        $assignee = '<span>&lt;'.$model->getMessage('general.unassigned').'&gt;</span>';
                    } else {
                        $assignee = $caseEntity->assignee->name;
                    }

                    $id = $model->getEncodedKeys($caseEntity);
                    $url = $event->subject()->Html->link($caseEntity->case_number, [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'Cases',
                        'view',
                        $id
                    ]);

                    $rowData[] = '<span class="status highlight">' . $caseEntity->status->name . '</span>';
                    $rowData[] = $assignee;
                    $rowData[] = $url;
                    $rowData[] = $caseEntity->title;

                    $tableCells[] = $rowData;
                }
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        }

        return $event->subject()->renderElement('Institution.Cases/linked_cases', ['attr' => $attr]);
    }

    public function getLinkedCaseQuery(Entity $entity)
    {
        $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
        $InstitutionCases = TableRegistry::get('Cases.InstitutionCases');

        $feature = $WorkflowRules->getFeatureByEntity($entity);
        $recordId = $entity->id;

        $query = $InstitutionCases
            ->find()
            ->contain(['Statuses', 'Assignees'])
            ->matching('LinkedRecords', function ($q) use ($feature, $recordId) {
                return $q->where([
                    'feature' => $feature,
                    'record_id' => $recordId
                ]);
            });

        return $query;
    }
}
