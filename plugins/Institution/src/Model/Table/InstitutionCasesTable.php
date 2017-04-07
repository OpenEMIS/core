<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class InstitutionCasesTable extends ControllerActionTable
{
    use OptionsTrait;

    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

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
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

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

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('title'),
                $this->aliasField('status_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Institutions->alias(), $this->CreatedUser->alias()])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'Cases',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = $row->_matchingData['Statuses']->name;
                    $row['request_title'] = $row->title;
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
