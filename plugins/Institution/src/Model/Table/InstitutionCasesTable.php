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
        $this->hasMany('LinkedRecords', ['className' => 'Institution.InstitutionCaseRecords', 'foreignKey' => 'institution_case_id', 'dependent' => true, 'cascadeCallbacks' => true]);

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
        return $events;
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
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['LinkedRecords']);
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

    public function onGetCustomLinkedRecordsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if ($action == 'index') {
            if ($entity->has('linked_records')) {
                $attr['value'] = sizeof($entity->linked_records);
            }
        } else if ($action == 'view') {
            $tableHeaders = [__('Feature'), __('Summary')];
            $tableCells = [];

            if ($entity->has('linked_records')) {
                $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
                $featureOptions = $WorkflowRules->getFeatureOptions();

                foreach ($entity->linked_records as $recordEntity) {
                    $rowData = [];

                    $recordId = $recordEntity->record_id;
                    $url = $event->subject()->Html->link($recordId, [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StaffBehaviours',
                        'view',
                        $this->paramsEncode(['id' => $recordId])
                    ]);

                    $rowData[] = isset($featureOptions[$recordEntity->feature]) ? $featureOptions[$recordEntity->feature] : $recordEntity->feature;
                    $rowData[] = $url;

                    $tableCells[] = $rowData;
                }
            }

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
        }

        return $event->subject()->renderElement('Institution.Cases/linked_records', ['attr' => $attr]);
    }

    public function autoLinkRecordWithCases($linkedRecordEntity)
    {
        $WorkflowRules = TableRegistry::get('Workflow.WorkflowRules');
        $linkedRecordModel = TableRegistry::get($linkedRecordEntity->source());
        $registryAlias = $linkedRecordModel->registryAlias();
        $feature = $WorkflowRules->getFeatureByRegistryAlias($registryAlias);

        $statusId = 0;
        $assigneeId = 0;
        $institutionId = $linkedRecordEntity->has('institution_id') ? $linkedRecordEntity->institution_id : 0;
        $autoGenerateCode = $this->getAutoGenerateCode($institutionId);
        $recordId = $linkedRecordEntity->id;

        $title = $feature;
        $event = $linkedRecordModel->dispatchEvent('InstitutionCase.onSetCustomCaseTitle', [$linkedRecordEntity], $linkedRecordModel);
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

        // loop through each rule setup for the feature
        // if the record match the rule, then create a new case and linked it with the record
        if (!$workflowRuleResults->isEmpty()) {
            foreach ($workflowRuleResults as $workflowRuleEntity) {
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
                            'record_id' => $recordId,
                            'feature' => $feature
                        ];

                        $newData = [
                            'code' => $autoGenerateCode,
                            'title' => $title,
                            'status_id' => $statusId,
                            'assignee_id' => $assigneeId,
                            'institution_id' => $institutionId,
                            'workflow_rule_id' => $workflowRuleEntity->id, // required by workflow behavior to get the correct workflow
                            'linked_records' => $linkedRecords
                        ];

                        $newEntity = $this->newEntity();
                        $newEntity = $this->patchEntity($newEntity, $newData);
                        $this->save($newEntity);
                    }
                }
            }
        }
    }

    private function getAutoGenerateCode($institutionId)
    {
        $codePrefix = '';
        $codeSuffix = '';

        $institutionEntity = $this->Institutions
            ->find()
            ->where([
                $this->Institutions->aliasField('id') => $institutionId
            ])
            ->select([$this->Institutions->aliasField('code')])
            ->first();

        $todayDate = date("dmY");
        $codePrefix = $institutionEntity->code . "-" . $todayDate . "-";

        $currentStamp = time();
        $codeSuffix = $currentStamp;

        $autoGenerateCode = $codePrefix . $codeSuffix;

        return $autoGenerateCode;
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
