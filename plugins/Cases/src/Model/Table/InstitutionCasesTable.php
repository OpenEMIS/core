<?php
namespace Cases\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;
use Workflow\Model\Behavior\WorkflowBehavior;

class InstitutionCasesTable extends ControllerActionTable
{
    use OptionsTrait;

    private $features = [];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->hasMany('LinkedRecords', ['className' => 'Cases.InstitutionCaseRecords', 'foreignKey' => 'institution_case_id', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        $this->toggle('add', false);

        $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
        $this->features = $WorkflowRules->getFeatureOptionsWithClassName();

        $this->addBehavior('Excel', ['pages' => ['index']]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.LinkedRecord.afterSave'] = 'linkedRecordAfterSave';
        return $events;
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $autoGenerateCaseNumber = $this->getAutoGenerateCaseNumber($entity->institution_id);
            $entity->case_number = $autoGenerateCaseNumber;
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $newCaseNumber = $entity->case_number . "-" . $entity->id;
            $this->updateAll(
                ['case_number' => $newCaseNumber],
                ['id' => $entity->id]
            );
        }
    }

    public function linkedRecordAfterSave(Event $event, Entity $linkedRecordEntity)
    {
        $this->autoLinkRecordWithCases($linkedRecordEntity);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('linked_records', [
            'type' => 'custom_linked_records',
            'valueClass' => 'table-full-width',
            'after' => 'description'
        ]);
        $this->field('created', [
            'visible' => true,
            'after' => 'linked_records'
        ]);

        if (is_null($this->request->query('sort'))) {
            $this->request->query['sort'] = 'created';
            $this->request->query['direction'] = 'desc';
        }

        $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
        $featureOptions = $WorkflowRules->getFeatureOptions();

        $newFeatureOption = [];

        //Order to follow what is defined at OptionsTrait
        foreach($this->getSelectOptions("WorkflowRules.features") as $key => $value) {
            if(array_key_exists($key, $featureOptions)) {
                $newFeatureOption[$key] = $featureOptions[$key];
            }
        }

        $featureOptions = $newFeatureOption;

        if (!is_null($this->request->query('feature')) && array_key_exists($this->request->query('feature'), $featureOptions)) {
            $selectedFeature = $this->request->query('feature');
        } else {
            $selectedFeature = key($featureOptions);
            $this->request->query['feature'] = $selectedFeature;
        }

        $this->controller->set(compact('featureOptions', 'selectedFeature'));

        $selectedModel = $this->features[$selectedFeature];
        $featureModel = TableRegistry::get($selectedModel);
        $session = $this->request->session();
        $requestQuery = $this->request->query;
        $institutionId = $session->read('Institution.Institutions.id');

        $params = new ArrayObject([
            'element' => ['filter' => ['name' => 'Cases.controls', 'order' => 2]],
            'options' => [],
            'query' => $this->request->query
        ]);

        $featureModel->dispatchEvent('InstitutionCase.onSetFilterToolbarElement', [$params, $institutionId], $featureModel);

        $extra['elements'] = $params['element'] + $extra['elements'];
        $this->request->query = $params['query'];

        if (!empty($params['options'])) {
            $this->controller->set($params['options']);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $requestQuery = $this->request->query;
        $selectedFeature = $requestQuery['feature'];
        $featureModel = TableRegistry::get($this->features[$selectedFeature]);
        $session = $this->Session;
        $username = $session->read('Auth.User');
        // if(strtolower($username['username']) == 'superrole' || strtolower($username['username']) == 'admin' || strtolower($username['username']) == 'administrator')
        // {
        //     $userId = 0;  
        // }else{
        //     $userId = $session->read('Auth.User.id');
        // }
        $userId = $session->read('Auth.User.id');

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('case_number'),
                $this->aliasField('title'),
                $this->aliasField('description'),
                $this->aliasField('status_id'),
                $this->aliasField('assignee_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified_user_id'),
                $this->aliasField('modified'),
                $this->aliasField('created_user_id'),
                $this->aliasField('created'),
                $this->Assignees->aliasField('first_name'),
                $this->Assignees->aliasField('middle_name'),
                $this->Assignees->aliasField('last_name'),
                $this->Assignees->aliasField('third_name'),
                $this->Assignees->aliasField('preferred_name')
            ])
            ->contain(['LinkedRecords'])
            ->innerJoin(
                [$this->LinkedRecords->alias() => $this->LinkedRecords->table()],
                [
                    [$this->LinkedRecords->aliasField('institution_case_id = ') . $this->aliasField('id')],
                    [$this->LinkedRecords->aliasField('feature = ') . '"' . $selectedFeature . '"']
                ]
            )
            //->where([$this->aliasField('assignee_id') => $userId])
            ->group($this->aliasField('id'));


        $featureModel->dispatchEvent('InstitutionCase.onCaseIndexBeforeQuery', [$requestQuery, $query], $featureModel);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['LinkedRecords']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('linked_records', [
            'type' => 'custom_linked_records',
            'valueClass' => 'table-full-width',
            'after' => 'description'
        ]);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('case_number', ['type' => 'readonly']);
        $this->field('title', ['type' => 'readonly']);
    }

    public function onGetCustomLinkedRecordsElement(Event $mainEvent, $action, $entity, $attr, $options = [])
    {
        if ($action == 'index') {
            if ($entity->has('linked_records')) {
                $attr['value'] = sizeof($entity->linked_records);
            }
        } elseif ($action == 'view') {
            $tableHeaders = [__('Feature'), __('Summary')];
            $tableCells = [];

            if ($entity->has('linked_records')) {
                $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
                $featureOptions = $WorkflowRules->getFeatureOptions();
                $featureAttr = $this->getSelectOptions('WorkflowRules.features');

                foreach ($entity->linked_records as $recordEntity) {
                    $rowData = [];

                    $recordId = $recordEntity->record_id;
                    $feature = $recordEntity->feature;

                    $className = $featureAttr[$feature]['className'];
                    $recordModel = TableRegistry::get($className);
                    $summary = $recordId;
                    $event = $recordModel->dispatchEvent('InstitutionCase.onSetCustomCaseSummary', [$recordId], $recordModel);
                    if ($event->isStopped()) {
                        return $event->result;
                    }
                    if (!empty($event->result)) {
                        $summary = $event->result;
                    }

                    if (is_array($summary) && isset($summary[1]) && $summary[1] === true) {
                        $baseUrl = $featureAttr[$feature]['url'];
                        $baseUrl[] = 'view';
                        $baseUrl[] = $this->paramsEncode(['id' => $recordId]);

                        $url = $mainEvent->subject()->Html->link($summary[0], $baseUrl);
                    } elseif (is_array($summary)) {
                        if (isset($summary[1]) && $summary[1] !== false) {
                            $url = $mainEvent->subject()->Html->link($summary[0], $summary[1]);
                        } else {
                            $url = $summary[0];
                        }
                    } else {
                        $url = $summary;
                    }

                    $rowData[] = isset($featureOptions[$recordEntity->feature]) ? $featureOptions[$recordEntity->feature] : $recordEntity->feature;
                    $rowData[] = $url;

                    $tableCells[] = $rowData;
                }
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        }

        return $mainEvent->subject()->renderElement('Institution.Cases/linked_records', ['attr' => $attr]);
    }

    public function autoLinkRecordWithCases($linkedRecordEntity)
    {
        $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
        $linkedRecordModel = TableRegistry::get($linkedRecordEntity->source());
        $registryAlias = $linkedRecordModel->registryAlias();
        $feature = $WorkflowRules->getFeatureByRegistryAlias($registryAlias);

        $statusId = WorkflowBehavior::STATUS_OPEN;
        $assigneeId = WorkflowBehavior::AUTO_ASSIGN;
        $institutionId = $linkedRecordEntity->has('institution_id') ? $linkedRecordEntity->institution_id : 0;
        $recordId = $linkedRecordEntity->id;

        $title = $feature;
        $event = $linkedRecordModel->dispatchEvent('InstitutionCase.onSetCustomCaseTitle', [$linkedRecordEntity], $linkedRecordModel);
        if ($event->isStopped()) {
            return $event->result;
        }
        if (!empty($event->result)) {
            $title = $event->result;
        }

        $workflowRuleResults = $WorkflowRules
            ->find()
            ->contain('WorkflowRuleEvents')
            ->where([$WorkflowRules->aliasField('feature') => $feature])
            ->all();

        // loop through each rule setup for the feature
        // if the record match the rule, then create a new case and linked it with the record
        if (!$workflowRuleResults->isEmpty()) {
            foreach ($workflowRuleResults as $workflowRuleEntity) {
                $ruleArray = json_decode($workflowRuleEntity->rule, true);
                if (array_key_exists('where', $ruleArray)) {
                    $where = $ruleArray['where'];
                    $where['id'] = $recordId;

                    $query = $linkedRecordModel
                        ->find();

                    $event = $linkedRecordModel->dispatchEvent('InstitutionCase.onSetLinkedRecordsCheckCondition', [$query, $where], $linkedRecordModel);

                    if ($event->result || $event->result === false) {
                        $checkCondition = $event->result;
                    } else {
                        $checkCondition = $query->where($where)->count() > 0;
                    }

                    if ($checkCondition) {
                        $existingLinkedCaseResults = $this
                            ->find()
                            ->matching('LinkedRecords', function ($q) use ($recordId, $feature) {
                                return $q->where([
                                    'record_id' => $recordId,
                                    'feature' => $feature
                                ]);
                            })
                            ->all();

                        if ($existingLinkedCaseResults->isEmpty()) {
                            $extra = new ArrayObject();
                            $extra['record_id'] = $recordId;
                            $extra['feature'] = $feature;
                            $extra['title'] = $title;
                            $extra['status_id'] = $statusId;
                            $extra['assignee_id'] = $assigneeId;
                            $extra['institution_id'] = $institutionId;
                            $extra['workflow_rule_id'] = $workflowRuleEntity->id;

                            $event = $linkedRecordModel->dispatchEvent('InstitutionCase.onSetCaseRecord', [$extra], $linkedRecordModel);
                            if (!empty($event->result)) {
                                $caseData = $event->result;
                            } else {
                                $linkedRecords = [];
                                $linkedRecords[] = [
                                    'record_id' => $recordId,
                                    'feature' => $feature
                                ];

                                $caseData = [
                                    'case_number' => '',
                                    'title' => $title,
                                    'status_id' => $statusId,
                                    'assignee_id' => $assigneeId,
                                    'institution_id' => $institutionId,
                                    'workflow_rule_id' => $workflowRuleEntity->id, // required by workflow behavior to get the correct workflow
                                    'linked_records' => $linkedRecords
                                ];
                            }

                            $patchOptions = ['validate' => false];

                            $newEntity = $this->newEntity();
                            $newEntity = $this->patchEntity($newEntity, $caseData, $patchOptions);
                            $this->save($newEntity);

                            $ruleExtra = new ArrayObject([]);
                            $ruleExtra['assigneeFound'] = false;

                            // Trigger rule Post Events
                            if ($workflowRuleEntity->has('workflow_rule_events') && !empty($workflowRuleEntity->workflow_rule_events)) {
                                $ruleEvents = $workflowRuleEntity->workflow_rule_events;

                                foreach ($ruleEvents as $ruleEvent) {
                                    $event = $linkedRecordModel->dispatchEvent($ruleEvent->event_key, [$newEntity, $linkedRecordEntity, $ruleExtra], $linkedRecordModel);
                                    if ($event->isStopped()) {
                                        return $event->result;
                                    }

                                    if ($ruleExtra['assigneeFound']) {
                                        break;
                                    }
                                }
                            }
                            // End
                        }
                    }
                }
            }
        }
    }

    private function getAutoGenerateCaseNumber($institutionId = 0)
    {
        $autoGenerateCaseNumber = '';
        $institutionEntity = $this->Institutions
            ->find()
            ->where([
                $this->Institutions->aliasField('id') => $institutionId
            ])
            ->select([$this->Institutions->aliasField('code')])
            ->first();

        if (!empty($institutionId)) {
            $autoGenerateCaseNumber .= $institutionEntity->code . "-";
        }

        $todayDate = date("dmY");
        $autoGenerateCaseNumber .= $todayDate;

        return $autoGenerateCaseNumber;
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = WorkflowSteps::DONE;

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
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = $row->title;
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    // POCOR-6170
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
		$institutionId = $this->Session->read('Institution.Institutions.id');
        $assignee = TableRegistry::get('security_users');

        // for getting selected feature
        $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
        $featureOptions = $WorkflowRules->getFeatureOptions();
        $newFeatureOption = [];
        //Order to follow what is defined at OptionsTrait
        foreach($this->getSelectOptions("WorkflowRules.features") as $key => $value) {
            if(array_key_exists($key, $featureOptions)) {
                $newFeatureOption[$key] = $featureOptions[$key];
            }
        }
        $featureOptions = $newFeatureOption;
        if (!is_null($this->request->query('feature')) && array_key_exists($this->request->query('feature'), $featureOptions)) {
            $selectedFeature = $this->request->query('feature');
        } else {
            $selectedFeature = key($featureOptions);
            $this->request->query['feature'] = $selectedFeature;
        }
        // for getting selected feature

		// query start
        $query
        ->select([
            $this->aliasField('id'),
            $this->aliasField('case_number'),
            $this->aliasField('title'),
            'status' => 'Statuses.name',
            'assignee' => $assignee->find()->func()->concat([
                'first_name' => 'literal',
                " ",
                'last_name' => 'literal'
            ]),
            $this->aliasField('description'),
            $this->aliasField('status_id'),
            $this->aliasField('assignee_id'),
            $this->aliasField('institution_id'),
            $this->aliasField('modified_user_id'),
            $this->aliasField('modified'),
            $this->aliasField('created_user_id'),
            $this->aliasField('created'),
        ])
        ->contain(['LinkedRecords'])
        ->innerJoin(
            [$this->LinkedRecords->alias() => $this->LinkedRecords->table()],
            [
                [$this->LinkedRecords->aliasField('institution_case_id = ') . $this->aliasField('id')],
                [$this->LinkedRecords->aliasField('feature = ') . '"' . $selectedFeature . '"']
            ]
        )
        ->LeftJoin([$this->Assignees->alias() => $this->Assignees->table()],[
            $this->Assignees->aliasField('id').' = ' . 'InstitutionCases.assignee_id'
        ])  
        ->LeftJoin([$this->Statuses->alias() => $this->Statuses->table()],[
            $this->Statuses->aliasField('id').' = ' . 'InstitutionCases.status_id'
        ])
        ->where([
            'InstitutionCases.institution_id' =>  $institutionId
        ])
        ->group($this->aliasField('id'));

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $row['total_linked_record'] = count($row->linked_records);

                return $row;
            });
        });
        // query end

        // when user select academic period , feature ,instituion class and grade filter 
        $requestQuery = $this->request->query;
        $featureModel = TableRegistry::get($this->features[$selectedFeature]);

        $featureModel->dispatchEvent('InstitutionCase.onCaseIndexBeforeQuery', [$requestQuery, $query], $featureModel);
    }
    // POCOR-6170
    // POCOR-6170
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraField[] = [
            'key' => 'Statuses.name',
            'field' => 'status',
            'type' => 'string',
            'label' => __('Status')
        ];

        $extraField[] = [
            'key' => 'Assignees.assignee',
            'field' => 'assignee',
            'type' => 'string',
            'label' => __('Assignee')
        ];

        $extraField[] = [
            'key' => 'InstitutionCases.case_number',
            'field' => 'case_number',
            'type' => 'string',
            'label' => __('Case Number')
        ];

        $extraField[] = [
            'key' => 'InstitutionCases.title',
            'field' => 'title',
            'type' => 'string',
            'label' => __('Title')
        ];

        $extraField[] = [
            'key' => 'InstitutionCases.description',
            'field' => 'description',
            'type' => 'string',
            'label' => __('Description')
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'total_linked_record',
            'type' => 'string',
            'label' => __('Linked Records')
        ];

        $extraField[] = [
            'key' => 'InstitutionCases.created',
            'field' => 'created',
            'type' => 'date',
            'label' => __('Created On')
        ];

        $fields->exchangeArray($extraField);
    }
    // POCOR-6170

}
