<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class InstitutionCasesTable extends ControllerActionTable
{
    use OptionsTrait;

	public function initialize(array $config)
	{
		parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Assignees', ['className' => 'User.Users']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsToMany('LinkedRecords', [
            'className' => 'Institution.StaffBehaviours',
            'joinTable' => 'institution_cases_records',
            'foreignKey' => 'institution_case_id',
            'targetForeignKey' => 'record_id',
            'through' => 'Institution.InstitutionCasesRecords',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Workflow.Workflow');

        $this->toggle('add', false);
	}

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.LinkedRecord.afterSave'] = 'linkedRecordAfterSave';
        $events['ControllerAction.Model.index.beforeAction'] = 'indexBeforeAction';
        $events['ControllerAction.Model.index.beforeQuery'] = 'indexBeforeQuery';
        $events['ControllerAction.Model.view.beforeQuery'] = 'viewBeforeQuery';
        $events['ControllerAction.Model.view.afterAction'] = 'viewAfterAction';
        return $events;
    }

    public function linkedRecordAfterSave(Event $event, Entity $linkedRecordEntity)
    {
        $statusId = 0;
        $assigneeId = 0;
        $institutionId = $linkedRecordEntity->has('institution_id') ? $linkedRecordEntity->institution_id : 0;
        $recordId = $linkedRecordEntity->id;

        $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
        $linkedRecordModel = TableRegistry::get($linkedRecordEntity->source());
        $registryAlias = $linkedRecordModel->registryAlias();
        $feature = $WorkflowRules->getFeatureByRegistryAlias($registryAlias);

        $title = $feature;
        $event = $linkedRecordModel->dispatchEvent('InstitutionCase.onGetCaseTitle', [$linkedRecordEntity], $linkedRecordModel);
        if ($event->isStopped()) { return $event->result; }
        if (!empty($event->result)) {
            $title = $event->result;
        }

        $workflowRuleResults = $WorkflowRules
            ->find()
            ->where([
                $WorkflowRules->aliasField('feature') => $feature
            ])
            ->all();

        if (!$workflowRuleResults->isEmpty()) {
            foreach ($workflowRuleResults as $key => $workflowRuleEntity) {
                $ruleArray = json_decode($workflowRuleEntity->rule, true);
                if (array_key_exists('where', $ruleArray)) {
                    $where = $ruleArray['where'];
                    $where['id'] = $recordId;

                    $query = $linkedRecordModel
                        ->find()
                        ->where($where);
                    
                    if ($query->count() > 0) {
                        $linkedRecords = [];
                        $linkedRecords[] = [
                            'id' => $recordId,
                            '_joinData' => [
                                'feature' => $feature
                            ]
                        ];

                        $newData = [
                            'title' => $title,
                            'status_id' => $statusId,
                            'assignee_id' => $assigneeId,
                            'institution_id' => $institutionId,
                            'linked_records' => $linkedRecords,
                            'workflow_rule_id' => $workflowRuleEntity->id // required by workflow behavior to get the correct workflow
                        ];
                        $patchOptions = [
                            'associated' => [
                                'LinkedRecords' => [
                                    'validate' => false
                                ]
                            ]
                        ];

                        $newEntity = $this->newEntity();
                        $newEntity = $this->patchEntity($newEntity, $newData, $patchOptions);
                        $this->save($newEntity);
                    }
                }
            }
        }
    }

    public function onGetLinkedRecords(Event $event, Entity $entity)
    {
        $linkedRecords = [];
        if ($entity->has('linked_records')) {
            foreach ($entity->linked_records as $linkedRecordEntity) {
                $id = $this->getEncodedKeys($linkedRecordEntity);

                $value = $linkedRecordEntity->description;
                if ($this->action == 'view') {
                    $url = $event->subject()->HtmlField->link($value, [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StaffBehaviours',
                        'view',
                        $id
                    ]);

                    $linkedRecords[] = $url;
                } else {
                    $linkedRecords[] = $value;
                }
            }
        }

        return !empty($linkedRecords) ? implode(", ", $linkedRecords) : '';
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('linked_records', [
            'type' => 'chosenSelect',
            'after' => 'description'
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['LinkedRecords']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('linked_records', [
            'type' => 'chosenSelect',
            'after' => 'description'
        ]);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['LinkedRecords']);
    }
}
