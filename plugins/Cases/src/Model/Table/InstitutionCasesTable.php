<?php

namespace Cases\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Datasource\ResultSetInterface;
use Cake\Log\Log;
use Cake\Utility\Text;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;
use Workflow\Model\Table\WorkflowStepsTable as WorkflowSteps;
use Workflow\Model\Behavior\WorkflowBehavior;
use Cake\Http\ServerRequest;

class InstitutionCasesTable extends ControllerActionTable
{
    use OptionsTrait;

    private $features = [];
    const ACTIVE = 1;//POCOR-7439 for institution active
    const INACTIVE = 1;//POCOR-7439 for institution inactive

    public function initialize(array $config): void
    {
        parent::initialize($config);;
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->hasMany('LinkedRecords', ['className' => 'Cases.InstitutionCaseRecords', 'foreignKey' => 'institution_case_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsTo('CaseTypes', ['className' => 'Cases.CaseTypes', 'foreignKey' => 'case_type_id']);//POCOR-7613
        $this->belongsTo('CasePriority', ['className' => 'Cases.CasePriorities', 'foreignKey' => 'case_priority_id']);//POCOR-7613
        $this->addBehavior('Workflow.WorkflowCase', ['controller' => $this]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
        $this->hasMany('InstitutionCaseComments', ['className' => 'Cases.InstitutionCaseComments', 'foreignKey' => 'case_id']);//POCOR-7613

        // $this->toggle('add', false);
        $WorkflowRules = TableRegistry::getTableLocator()->get('Workflow.WorkflowRules');
        $this->features = $WorkflowRules->getFeatureOptionsWithClassName();

        $this->addBehavior('User.UserTab', [
            'appliedAction' => ['Cases' =>
                ['status_id', 'assignee_id', 'institution_id']
            ]
        ]);


        $this->addBehavior('Excel', ['pages' => ['index']]);
    }


    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.LinkedRecord.afterSave'] = 'linkedRecordAfterSave';
        return $events;
    }
    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->notEmptyString('tytle')
            ->notEmptyString('description')
            ->notEmptyString('case_type_id')
        ->notEmptyString('case_priority_id');
        return $validator;
    }
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        //POCOR-7367::Start
        //POCOR-7613 start
        if ($this->request->getParam('controller') == "Profiles") {
            if ($entity->assignee_id == 0 || empty($entity->assignee_id)) {
                $this->Alert->warning('Cases.noAssignee', ['reset' => true]);
                return false;
            }
        }
        //POCOR-7613 end
        $workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');
        $workflowSteps = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
        $wfData = $workflows->find()->where(['name' => 'Cases - General'])->first();
        $WFSdata = $workflowSteps->find()->where(['name' => 'Open', 'workflow_id' => $wfData->id])->first();
        $entity->status_id = $WFSdata->id;
        //POCOR-7367::end
        if ($entity->isNew()) {
            $autoGenerateCaseNumber = $this->getAutoGenerateCaseNumber($entity->institution_id);
            $entity->case_number = $autoGenerateCaseNumber;
        }
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {

        if ($entity->isNew()) {
            $linkedRecord = TableRegistry::getTableLocator()->get('Cases.InstitutionCaseRecords');
            $newCaseNumber = $entity->case_number . "-" . $entity->id;
            $this->updateAll(
                ['case_number' => $newCaseNumber],
                ['id' => $entity->id]
            );
            if ($entity->submit == 'save') {
                $requestData = $this->request->getQuery('feature');
                if (empty($requestData)) {
                    $this->request = $this->request->withQueryParams(['feature' => 'StudentAttendances']);
                }

                $features = $this->request->getQuery('feature');

                $params['feature'] = $features;
                $params['institution_case_id'] = $entity->id;
                $params['record_id'] = 0;
                $params['id'] = Text::uuid();
                $params['created_user_id'] = $entity->created_user_id;
                $params['created'] = date('Y-m-d H:i:s');
                $newEntity = $linkedRecord->newEntity($params);
                $linkedRecord->save($newEntity);
            }
        }
    }

    public function linkedRecordAfterSave(EventInterface $event, Entity $linkedRecordEntity)
    {
        $this->autoLinkRecordWithCases($linkedRecordEntity);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('linked_records', [
            'type' => 'custom_linked_records',
            'valueClass' => 'table-full-width',
            'after' => 'description',
            'visible' => 'false'//POCOR-7613
        ]);
        $this->field('created', [
            'visible' => true,
            'after' => 'linked_records'
        ]);

        if (is_null($this->request->getQuery['sort'])) { // comment cakephp4
            $this->request->getQuery['sort'] = 'created';
            $this->request->getQuery['direction'] = 'desc';
        }

        $WorkflowRules = TableRegistry::getTableLocator()->get('Workflow.WorkflowRules');
        $featureOptions = $WorkflowRules->getFeatureOptions();

        $newFeatureOption = [];

        //Order to follow what is defined at OptionsTrait
        foreach ($this->getSelectOptions("WorkflowRules.features") as $key => $value) {
            if (array_key_exists($key, $featureOptions)) {
                $newFeatureOption[$key] = $featureOptions[$key];
            }
        }

        $featureOptions = $newFeatureOption;

        $featureOptions = ['-1' => '-- ' . __('All') . ' --'] + $featureOptions;
        if (!is_null($this->request->getQuery['feature']) && array_key_exists($this->request->getQuery('feature'), $featureOptions)) {
            $selectedFeature = $this->request->getQuery['feature'];
        } else {
            $selectedFeature = key($featureOptions);
            $this->request = $this->request->withQueryParams(['feature' => $selectedFeature]);
        }

        $this->controller->set(compact('featureOptions', 'selectedFeature'));

        $selectedModel = $this->features[$selectedFeature];
        $session = $this->request->getSession();
        $requestQuery = $this->request->getQuery();
        $institutionId = $session->read('Institution.Institutions.id');

        $params = new ArrayObject([
            'element' => ['filter' => ['name' => 'Cases.controls', 'order' => 2]],
            'options' => [],
            'query' => $this->request->getQuery()
        ]);
        if (!empty($selectedModel)) {
            $featureModel = TableRegistry::getTableLocator()->get($selectedModel);
            $featureModel->dispatchEvent('InstitutionCase.onSetFilterToolbarElement', [$params, $institutionId], $featureModel);
        }

        $extra['elements'] = $params['element'] + $extra['elements'];
        //$this->request->query = $params['query'];
        $this->request = $this->request->withQueryParams(['query' => $params['query']]);

        if (!empty($params['options'])) {
            $this->controller->set($params['options']);
        }

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions', 'Cases', 'Cases');
        if (!empty($is_manual_exist)) {

            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;


            // End POCOR-5188
        }
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $requestQuery = $this->request->getQuery('query');
        $selectedFeature = $requestQuery['feature'];
        $featureModel = !empty($this->features[$selectedFeature]) ? TableRegistry::getTableLocator()->get($this->features[$selectedFeature]) : '';
        //$featureModel = TableRegistry::getTableLocator()->get($this->features[$selectedFeature]);

        //POCOR-7437 start
        $controllerName = $this->request->getParam('controller');
        if ($controllerName == "Profiles") {
            $userId = $this->getUserID();
            $where = [$this->aliasField('created_user_id') => $userId];
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
                    [$this->LinkedRecords->getAlias() => $this->LinkedRecords->getTable()],
                    [
                        [$this->LinkedRecords->aliasField('institution_case_id = ') . $this->aliasField('id')],
                        //[$this->LinkedRecords->aliasField('feature = ') . '"' . $selectedFeature . '"']// comment cakephp 4
                    ]
                )
                ->where($where)//POCOR-7668
                ->group($this->aliasField('id'));
        } else {//POCOR-7437 end
            if ($selectedFeature != -1) { //start POCOR-6210
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
                        [$this->LinkedRecords->getAlias() => $this->LinkedRecords->getTable()],
                        [
                            [$this->LinkedRecords->aliasField('institution_case_id = ') . $this->aliasField('id')],
                            [$this->LinkedRecords->aliasField('feature = ') . '"' . $selectedFeature . '"']
                        ]
                    )
                    ->where([$this->LinkedRecords->aliasField('record_id NOT IN') => 0])//start POCOR-6210
                    ->group($this->aliasField('id'));
            } else {
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
                        [$this->LinkedRecords->getAlias() => $this->LinkedRecords->getTable()],
                        [
                            [$this->LinkedRecords->aliasField('institution_case_id = ') . $this->aliasField('id')],
                            //[$this->LinkedRecords->aliasField('feature = ') . '"' . $selectedFeature . '"']
                        ]
                    )
                    ->group($this->aliasField('id'));

            }
        }

        // $featureModel->dispatchEvent('InstitutionCase.onCaseIndexBeforeQuery', [$requestQuery, $query], $featureModel);
        if ($selectedFeature != 'StudentAttendances') {
            if (!empty($featureModel)) {
                $featureModel->dispatchEvent('InstitutionCase.onCaseIndexBeforeQuery', [$requestQuery, $query], $featureModel);
            }
        }
    }

    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        //$query->contain(['LinkedRecords']);
        $this->field('case_number', ['visible' => true]);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        //start POCOR-6210
        $this->field('case_number', ['visible' => true]);//POCOR-7613
        if ($entity->linked_records[0]['record_id'] != 0) {
            $this->field('linked_records', [
                'type' => 'custom_linked_records',
                'valueClass' => 'table-full-width',
                'after' => 'description'
            ]);
        }
        $this->setFieldOrder([//POCOR-7613
            'case_number', 'status_id', 'assignee_id', 'title', 'case_type_id', 'case_priority_id', 'description',
        ]);
        //POCOR-7613 start
        if ($this->request->getParam('controller') == "Profiles") {

            $this->field('modified', ['visible' => false]); //POCOR-7613
            $this->field('modified_user_id', ['visible' => 'false']);
            $this->field('created', ['visible' => false]); //POCOR-7613
            $this->field('created_user_id', ['visible' => 'false']);
            $this->field('assignee_id', ['visible' => 'false']);
            $this->field('workflow_status', ['type' => 'hidden']);
            // $this->field('personal_comment', ['type'=>'element','element' => 'custom_personal_comment', 'valueClass' => 'table-full-width',]);
            $fieldKey = 'comment';
            $tableHeaders = [__('Comment'), __('Created By'), __('Created On')];
            $tableCells = [];
            $Comments = TableRegistry::getTableLocator()->get('Cases.InstitutionCaseComments');
            $case_id = $this->paramsDecode($this->request->getParam('pass')[1])['id'];
            $userTable = TableRegistry::getTableLocator()->get('Security.Users');
            $commentResults = $Comments->find()
                ->select([
                    "user_id" => $Comments->aliasField('created_user_id'),
                    "first_name" => $userTable->aliasField('first_name'),
                    "last_name" => $userTable->aliasField('last_name'),
                    "openemis_no" => $userTable->aliasField('openemis_no'),
                    "case_id" => $Comments->aliasField('case_id'),
                    "comment" => $Comments->aliasField('comment'),
                    "comment_id" => $Comments->aliasField('id'),
                    "created" => $Comments->aliasField('created'),

                ])
                ->leftJoin([$userTable->getAlias() => $userTable->getTable()], [
                    $userTable->aliasField('id =') . $Comments->aliasField('created_user_id')
                ])
                ->where([
                    $Comments->aliasField('case_id') => $case_id,
                    $Comments->aliasField('created_user_id') => $this->Auth->user('id')
                ])->toArray();

            if (!empty($commentResults)) {
                foreach ($commentResults as $commentObj) {
                    $rowData = [];
                    $rowData[] = $commentObj->comment;
                    $rowData[] = $commentObj->openemis_no . " - " . $commentObj->first_name . " " . $commentObj->last_name;
                    $rowData[] = $commentObj->created->format('Y-m-d h:i:s');

                    // table cells
                    $tableCells[] = $rowData;
                }
            }
            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
            $this->field('new_comment', [
                'type' => 'element',
                'element' => 'Cases.comment',
                'override' => true,
                'tableHeaders' => $tableHeaders,
                'tableCells' => $tableCells,
            ]);

            $this->setFieldOrder([ //POCOR-7613
                'case_number', 'title', 'description', 'case_type_id', 'case_priority_id', 'institution_id', 'comm'
            ]);
        }
        //POCOR-7613 end
        //End POCOR-6210
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('case_number', ['visible' => true, 'type' => "readonly"]);//POCOR-7613
        $this->field('title');
        $this->setFieldOrder([//POCOR-7613
            'case_number', 'title', 'description', 'case_type_id', 'case_priority_id', 'assignee_id',
        ]);
    }

    public function onGetCustomLinkedRecordsElement(EventInterface $mainEvent, $action, $entity, $attr, $options = [])
    {
        if ($action == 'index') {
            if ($entity->has('linked_records')) {
                if ($entity->linked_records[0]['record_id'] != 0) {//start POCOR-6210
                    //link Record count
                    $caselinktable = TableRegistry::getTableLocator()->get('Cases.InstitutionCaseLinks');
                    $caseLinkCount = $caselinktable->find()->where(['parent_case_id' => $entity->id])->count();
                    $attr['value'] = $caseLinkCount;
                }
            }
        } elseif ($action == 'view') {
            $tableHeaders = [__('Feature'), __('Date'), __('Absence Type'), __('Reason'), __('Comment')];//POCOR-4864
            $tableCells = [];

            if ($entity->has('linked_records')) {
                $WorkflowRules = TableRegistry::getTableLocator()->get('Workflow.WorkflowRules');
                $featureOptions = $WorkflowRules->getFeatureOptions();
                $featureAttr = $this->getSelectOptions('WorkflowRules.features');

                foreach ($entity->linked_records as $recordEntity) {
                    $rowData = [];

                    $recordId = $recordEntity->record_id;
                    $feature = $recordEntity->feature;

                    $className = $featureAttr[$feature]['className'];
                    $recordModel = TableRegistry::getTableLocator()->get($className);
                    $summary = $recordId;
                    $event = $recordModel->dispatchEvent('InstitutionCase.onSetCustomCaseSummary', [$recordId], $recordModel);
                    if ($event->isStopped()) {
                        return $event->getResult();
                    }
                    if (!empty($event->getResult())) {
                        $summary = $event->getResult();
                    }

                    if (is_array($summary) && isset($summary[1]) && $summary[1] === true) {
                        $baseUrl = $featureAttr[$feature]['url'];
                        $baseUrl[] = 'view';
                        $baseUrl[] = $this->paramsEncode(['id' => $recordId]);

                        $url = $mainEvent->getSubject()->Html->link($summary[0]['title'], $baseUrl);//POCOR-4864
                    } elseif (is_array($summary)) {
                        if (isset($summary[1]) && $summary[1] !== false) {
                        $url = $mainEvent->getSubject()->Html->link($summary[0]['title'], $summary[1]);//POCOR-4684
                        } else {
                            $url = $summary[0]['title'];//POCOR-4684
                        }
                    } else {
                        $url = $summary[0]['title'];//POCOR-48684
                    }

                    $rowData[] = isset($featureOptions[$recordEntity->feature]) ? $featureOptions[$recordEntity->feature] : $recordEntity->feature;
                    //POCOR-4864 start
                    $rowData[] = date_format($recordEntity->created, 'F d, Y');
                    $rowData[] = $url;
                    $rowData[] = $summary[0]['reason'];
                    $rowData[] = $summary[0]['comment'];
                    //POCOR-4864 ends
                    $tableCells[] = $rowData;
                }
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        }

        return $mainEvent->getSubject()->renderElement('Institution.Cases/linked_records', ['attr' => $attr]);
    }

    public function autoLinkRecordWithCases($linkedRecordEntity)
    {
        $WorkflowRules = TableRegistry::getTableLocator()->get('Workflow.WorkflowRules');
        $linkedRecordModel = TableRegistry::getTableLocator()->get($linkedRecordEntity->getSource());
        $registryAlias = $linkedRecordModel->getRegistryAlias();
        $feature = $WorkflowRules->getFeatureByRegistryAlias($registryAlias);

        $statusId = WorkflowBehavior::STATUS_OPEN;
        $assigneeId = WorkflowBehavior::AUTO_ASSIGN;
        $institutionId = $linkedRecordEntity->has('institution_id') ? $linkedRecordEntity->institution_id : 0;
        $recordId = $linkedRecordEntity->id;

        $title = $feature;
        $event = $linkedRecordModel->dispatchEvent('InstitutionCase.onSetCustomCaseTitle', [$linkedRecordEntity], $linkedRecordModel);
        if ($event->isStopped()) {
            return $event->getResult();
        }
        if (!empty($event->getResult())) {
            $title = $event->getResult();
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
                if (isset($ruleArray['where'])) {
                    $where = $ruleArray['where'];
                    $where['id'] = $recordId;

                    $query = $linkedRecordModel
                        ->find();

                    $event = $linkedRecordModel->dispatchEvent('InstitutionCase.onSetLinkedRecordsCheckCondition', [$query, $where], $linkedRecordModel);

                    if ($event->getResult() || $event->getResult() === false) {
                        $checkCondition = $event->getResult();
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
                            if (!empty($event->getResult())) {
                                $caseData = $event->getResult();
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
                                        return $event->getResult();
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
        // POCOR-9014 start
        $controller = $options['_controller'];
        $session = $controller->getRequest()->getSession();
        $userId = $session->read('Auth.User.id');
        // POCOR-9014 end
        $Statuses = $this->Statuses;
        $doneStatus = WorkflowSteps::DONE;
        if($userId) {
            $where = [$this->aliasField('assignee_id') => $userId];
        }
        $where['Assignees.super_admin IS NOT'] = 1;
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
            ->contain([$this->Institutions->getAlias(), $this->CreatedUser->getAlias(), 'Assignees'])
            ->matching($this->Statuses->getAlias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where($where)//POCOR-7102
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
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $institutionId = $this->getInstitutionID() ? $this->getInstitutionID() : 0;
        $assignee_id = $this->getUserID();
        //$assignee = $this->Assignees->get($assignee_id);
        // for getting selected feature
        $WorkflowRules = TableRegistry::getTableLocator()->get('Workflow.WorkflowRules');
        $featureOptions = $WorkflowRules->getFeatureOptions();
        $newFeatureOption = [];
        //Order to follow what is defined at OptionsTrait
        foreach ($this->getSelectOptions("WorkflowRules.features") as $key => $value) {
            if (array_key_exists($key, $featureOptions)) {
                $newFeatureOption[$key] = $featureOptions[$key];
            }
        }
        $featureOptions = $newFeatureOption;
        if (!is_null($this->request->getQuery('feature')) && array_key_exists($this->request->getQuery('feature'), $featureOptions)) {
            $selectedFeature = $this->request->getQuery('feature');
        } else {
            $selectedFeature = key($featureOptions);
            $this->request->getQuery['feature'] = $selectedFeature;
        }
        // for getting selected feature

        // query start

        if (intval($institutionId) > 0) {
            $whereInstitution = [
                'InstitutionCases.institution_id' => $institutionId
            ];
        }
        $controllerName = $this->request->getParam('controller');
        if ($controllerName == "Profiles") {
            $userID = $this->getUserID();
            $whereInstitution[$this->aliasField('created_user_id')] = $userID;
        }
        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('case_number'),
                $this->aliasField('title'),
                'status' => 'Statuses.name',
                'assignee' => $this->Assignees->find()->func()->concat([
                    $this->Assignees->aliasField('first_name') => 'literal',
                    " ",
                    $this->Assignees->aliasField('last_name') => 'literal'
                ]),
                'type' => 'CaseTypes.name',//POCOR-7613
                'priority' => 'CasePriority.name',//POCOR-7613
                $this->aliasField('description'),
                $this->aliasField('status_id'),
                $this->aliasField('assignee_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified_user_id'),
                $this->aliasField('modified'),
                $this->aliasField('created_user_id'),
                $this->aliasField('created'),
            ])
            ->contain(['LinkedRecords', 'CaseTypes', 'CasePriority'])//POCOR-7613
            ->innerJoin(
                [$this->LinkedRecords->getAlias() => $this->LinkedRecords->getTable()],
                [
                    [$this->LinkedRecords->aliasField('institution_case_id = ') . $this->aliasField('id')],
                    [$this->LinkedRecords->aliasField('feature = ') . '"' . $selectedFeature . '"']
                ]
            )
            ->LeftJoin([$this->Assignees->getAlias() => $this->Assignees->getTable()], [
                $this->Assignees->aliasField('id') . ' = ' . 'InstitutionCases.assignee_id'
            ])
            ->LeftJoin([$this->Statuses->getAlias() => $this->Statuses->getTable()], [
                $this->Statuses->aliasField('id') . ' = ' . 'InstitutionCases.status_id'
            ])
            ->where($whereInstitution)
            ->group($this->aliasField('id'))
            ->order([$this->aliasField('created') => 'DESC']);//POCOR-7613

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                $row['total_linked_record'] = count($row->linked_records);
                return $row;
            });
        });
        // query end

        // when user select academic period , feature ,instituion class and grade filter
        $requestQuery = $this->request->getQuery();
        $featuredTable = $this->features[$selectedFeature];

        //POCOR-7613 for proper records in excel
        if ($selectedFeature != 'StudentAttendances') {
            try {
                $featureModel = TableRegistry::getTableLocator()->get($featuredTable);
            } catch (\Exception $exception) {
                $this->log($exception->getMessage(), 'debug');
                return;
            }
            $featureModel->dispatchEvent('InstitutionCase.onCaseIndexBeforeQuery', [$requestQuery, $query], $featureModel);
        }

        // $featureModel->dispatchEvent('InstitutionCase.onCaseIndexBeforeQuery', [$requestQuery, $query], $featureModel);
    }
    // POCOR-6170
    // POCOR-6170
    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {   //POCOR-7613 start
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
            'key' => 'CaseTypes.name',
            'field' => 'type',
            'type' => 'string',
            'label' => __('Type')
        ];

        $extraField[] = [
            'key' => 'CasePriority.name',
            'field' => 'priority',
            'type' => 'string',
            'label' => __('Priority')
        ];

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
            'key' => 'InstitutionCases.created',
            'field' => 'created',
            'type' => 'date',
            'label' => __('Created')
        ];

        $extraField[] = [
            'key' => 'InstitutionCases.modified',
            'field' => 'modified',
            'type' => 'date',
            'label' => __('Updated')
        ];
        //POCOR-7613 end
        $fields->exchangeArray($extraField);
    }

    // POCOR-6170

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('institution_id');//POCOR-7437
        $this->field('case_number', ['visible' => 'true']);//POCOR-7613
        $this->field('case_type_id');//POCOR-7613
        $this->field('case_priority_id');//POCOR-7613
        $this->setFieldOrder([//POCOR-7613
            'case_number', 'title', 'description', 'assignee_id'
        ]);
    }

    //POCOR-7437 start
    public function indexAfterAction(EventInterface $event, $data)
    {
        $this->field('case_number', ['visible' => true]);
        $this->field('status_id', ['visible' => true, 'after' => 'created']);
        $this->field('modified', ['visible' => true]);
        $this->fields['modified']['sort'] = false;
        $this->field('description', ['visible' => false]);
        $this->field('linked_records', ['visible' => false]);
        $this->field('institution_id', ['visible' => false]);
        if ($this->request->getParam('controller') == "Profiles") { //POCOR-7613
            $this->field('institution_id', ['visible' => true]);
            $this->field('assignee_id', ['visible' => false]);
        }

        $this->fields['created']['sort'] = false;
        $this->fields['status_id']['sort'] = true;
        $this->setFieldOrder([
            'case_number', 'created', 'modified', 'title', 'status_id'
        ]);
    }

    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, $request)
    {

        if ($request->getParam('controller') == "Profiles") {

            $institutionList = $this->Institutions
                ->find('list', [
                    'keyField' => 'id',
                    'valueField' => 'code_name'
                ])
                ->where([
                    $this->Institutions->aliasField('institution_status_id') => self::ACTIVE
                ])
                ->order([
                    $this->Institutions->aliasField('code') => 'ASC',
                    $this->Institutions->aliasField('name') => 'ASC'
                ])
                ->toArray();
            if (count($institutionList) > 1) {

                $institutionOptions = ['' => __('-- Select --')] + $institutionList;
            } else {
                $institutionOptions = $institutionList;
            }
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['options'] = $institutionOptions;
            $attr['onChangeReload'] = true;//POCOR-7668
            if ($action == "edit") {
                $attr['type'] = 'readOnly';
            }
        }
        return $attr;
    }
    //POCOR-7437 end
    //POCOR-7642 start
    public function getModelAlertData($threshold)
    {
        $dayBefore = $threshold['value'];
        $workflowCategory = $threshold['workflow_steps'];
        $sqlConditions = [
            1 => ('DATEDIFF( NOW(),InstitutionCases.created)' . '>' . $dayBefore), // before
        ];
        $caseResults = $this->find()
            ->contain(['Institutions', 'Assignees', 'Statuses'])
            ->where([
                $this->Statuses->aliasField('id In') => $workflowCategory,
                $this->aliasField('modified is null'),
                $this->aliasField('modified_user_id is null'),
                $sqlConditions
            ]);
        return $caseResults->toArray();
    }
    //POCOR-7642 end
    //POCOR-7668 start
    public function onGetAssigneeId(EventInterface $event, Entity $entity)
    {
        return $entity->assignee->name;
    }
    //POCOR-7668 end
    //POCOR-7613 start
    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'case_number':
                return __('Case Number');
            case 'title':
                return __('Title');
            case 'description':
                return __('Description');
            case 'institution_id':
                return __('Institution');
            case 'case_type_id':
                return __('Type');
            case 'case_priority_id':
                return __('Priority');
            case 'status_id':
                return __('Status');
            case 'assignee_id':
                return __('Assignee');
            case 'modified':
                return __('Updated');
            case 'modified_user_id':
                return __('Modified By');
            case 'created_user_id':
                return __('Created By');
            case 'created':
                return __('Created');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onUpdateFieldCaseTypeId(EventInterface $event, array $attr, $action, $request)
    {
        $CaseTypes = TableRegistry::getTableLocator()->get('Cases.CaseTypes');
        $CaseTypeList = $CaseTypes
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->toArray();
        $attr['type'] = 'select';
        $attr['options'] = $CaseTypeList;
        if ($request->getParam('controller') == "Profiles") {//POCOR-7613
            if ($action == "edit") {
                $attr['type'] = 'readonly';
            }
        }
        return $attr;
    }

    public function onUpdateFieldCasePriorityId(EventInterface $event, array $attr, $action, $request)
    {
        $CasePriority = TableRegistry::getTableLocator()->get('Cases.CasePriorities');
        $CasePriorityList = $CasePriority
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'name'
            ])
            ->toArray();
        $attr['type'] = 'select';
        $attr['options'] = $CasePriorityList;
        if ($request->getParam('controller') == "Profiles") {//POCOR-7613
            if ($action == "edit") {
                $attr['type'] = 'readonly';
            }
        }
        return $attr;
    }

    public function onGetCaseTypeId(EventInterface $event, Entity $entity)
    {
        return $entity->case_type->name;
    }

    public function onGetCasePriorityId(EventInterface $event, Entity $entity)
    {
        return $entity->case_priority->name;
    }

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('case_number', ['visible' => false]);
        $this->setFieldOrder([ //POCOR-7613
            'case_number', 'title', 'description', 'case_type_id', 'case_priority_id', 'institution_id'
        ]);
    }

    public function onGetCustomPersonalCommentElement(EventInterface $event, $action, $entity, $attr, $options = [])
    {
        $fieldKey = 'comment';
        $tableHeaders = [__('Comment'), _('Created By'), _('Created On')];
        $tableCells = [];
        $Comments = TableRegistry::getTableLocator()->get('Cases.InstitutionCaseComments');
        $case_id = $this->paramsDecode($this->request->getAttribute('params')['pass'][1])['id'];
        $userTable = TableRegistry::getTableLocator()->get('Security.Users');
        $commentResults = $Comments->find()
            ->select([
                "user_id" => $Comments->aliasField('created_user_id'),
                "first_name" => $userTable->aliasField('first_name'),
                "last_name" => $userTable->aliasField('last_name'),
                "openemis_no" => $userTable->aliasField('openemis_no'),
                "case_id" => $Comments->aliasField('case_id'),
                "comment" => $Comments->aliasField('comment'),
                "comment_id" => $Comments->aliasField('id'),
                "created" => $Comments->aliasField('created'),

            ])
            ->leftJoin([$userTable->getAlias() => $userTable->getTable()], [
                $userTable->aliasField('id =') . $Comments->aliasField('created_user_id')
            ])
            ->where([
                $Comments->aliasField('case_id') => $case_id,
                $Comments->aliasField('created_user_id') => $this->Auth->user('id')
            ])->toArray();

        if (!empty($commentResults)) {
            foreach ($commentResults as $commentObj) {
                $rowData = [];
                $rowData[] = $commentObj->comment;
                $rowData[] = $commentObj->openemis_no . " - " . $commentObj->first_name . " " . $commentObj->last_name;
                $rowData[] = $commentObj->created->format('Y-m-d h:i:s');

                // table cells
                $tableCells[] = $rowData;
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;
        return $event->getSubject()->renderElement('Cases.comment', ['attr' => $attr]);
    }
    //POCOR-7613 end
}
