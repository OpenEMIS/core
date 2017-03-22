<?php
namespace Alert\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class AlertLogsTable extends ControllerActionTable
{
    use OptionsTrait;

    private $statusTypes = [
        0 => 'Pending',
        1 => 'Success',
        -1 => 'Failed'
    ];

    private $featureGrouping = [];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->featureGrouping = $this->getSelectOptions($this->aliasField('feature_grouping'));

        $this->toggle('add', false);
        $this->toggle('edit', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Workflow.afterSave'] = 'alertAssigneeAfterSave';
        return $events;
    }

    public function alertAssigneeAfterSave(Event $mainEvent, Entity $recordEntity)
    {
        $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');
        $WorkflowModels = TableRegistry::get('Workflow.WorkflowModels');
        $Users = TableRegistry::get('User.Users');
        $model = TableRegistry::get($recordEntity->source());
        $modelAlias = $model->alias();
        $modelRegistryAlias = $model->registryAlias();
        $feature = __(Inflector::humanize(Inflector::underscore($modelAlias))); // feature for control filter

        $method = 'Email'; // method will be predefined

        if ($recordEntity->has('assignee_id') && $recordEntity->assignee_id > 0) {
            // to get the comment inputted on the workflow popup
            $workflowModelId = $WorkflowModels->find()
                ->where([$WorkflowModels->aliasField('model') => $modelRegistryAlias])
                ->first()->id;

            $records = $WorkflowTransitions->find()
                ->where([
                    $WorkflowTransitions->aliasField('model_reference') => $recordEntity->id,
                    $WorkflowTransitions->aliasField('workflow_model_id') => $workflowModelId
                ])
                ->last();

            // get the query for the $vars on replace message function, auto contain the belongs to associations
            $query = $model->find()->where([$model->aliasField('id') => $recordEntity->id]);

            $extra = new ArrayObject([]);
            $contain = $model->getContains('belongsTo', $extra);

            if (!empty($contain)) {
                $query->contain($contain);
            }

            $lastExecutorId = !empty($records->modified_user_id) ? $records->modified_user_id : $records->created_user_id;
            $lastExecutorName = $Users->get($lastExecutorId)->name;

            $vars = $query->hydrate(false)->first();
            $vars['feature'] = $feature;
            $vars['last_executor_id'] = $lastExecutorId;
            $vars['last_executor_name'] = $lastExecutorName;
            $vars['workflow_comment'] = $records->comment;

            if (!empty($vars['assignee']['email'])) { // if no email will not insert to alertlog.
                $assigneeName = $Users->get($vars['assignee']['id'])->name;
                $assigneeEmail = $vars['assignee']['email'];
                $recipient = $assigneeName . ' <' . $assigneeEmail . '>';

                $defaultSubject = __('[${feature}] (${status.name}) ${created_user.first_name} ${created_user.last_name}');
                $subject = $this->replaceMessage($modelAlias, $defaultSubject, $vars);

                $defaultMessage = __('Your action is required for [${feature} Workflow].');
                $defaultMessage .= "\n"; // line break
                $defaultMessage .= "\n" . __('Status')      . ': ' . "\t \t"    . '${status.name}' ;
                $defaultMessage .= "\n" . __('Sent By')     . ': ' . "\t \t"    . '${last_executor_name}' ;
                $defaultMessage .= "\n" . __('Comments')    . ': ' . "\t"    . '${workflow_comment}' ;

                $message = $this->getWorkflowEmailMessage($recordEntity->source());

                if (is_null($message)) {
                    $message = $this->replaceMessage($modelAlias, $defaultMessage, $vars);
                } else {
                    $message = $this->replaceMessage($modelAlias, $message, $vars);
                }

                // insert to the alertLog and send the email
                $this->insertAlertLog($method, $modelAlias, $recipient, $subject, $message);
            }// end no assignee email in the $vars
        }// end if have assignee id in the recordEntity
    }

    public function insertAlertLog($method, $feature, $email, $subject=null, $message=null)
    {
        $today = Time::now();
        $todayDate = Date::now();

        // checksum hash($subject,$message)
        $checksum = Security::hash($subject . ',' . $message, 'sha256');

        // to update and add new records into the alert_logs
        if ($this->exists(['checksum' => $checksum]) && $feature == 'Attendance') {
            $record = $this->find()
                ->where(['checksum' => $checksum])
                ->first();

            if ($record->status == 0) {
                $this->save($record);
            }
        } else {
            $entity = $this->newEntity([
                'feature' => $feature,
                'method' => $method,
                'destination' => $email,
                'status' => 0,
                'subject' => $subject,
                'message' => $message,
                'checksum' => $checksum
            ]);
            $this->save($entity);
        }
    }

    public function replaceMessage($feature, $message, $vars)
    {
        $format = '${%s}';
        $strArray = explode('${', $message);
        array_shift($strArray); // first element will not contain the placeholder

        $availablePlaceholder = [];
        if ($feature == 'Attendance') {
            // for feature from alert Rule to get the availablePlaceholder
            $AlertRules = TableRegistry::get('Alert.AlertRules');
            $alertTypeDetails = $AlertRules->getAlertTypeDetailsByFeature($feature);

            $availablePlaceholder = $alertTypeDetails[$feature]['placeholder'];
        }

        foreach ($strArray as $key => $str) {
            $pos = strpos($str, '}');

            if ($pos !== false) {
                $placeholder = substr($str, 0, $pos);
                $replace = sprintf($format, $placeholder);

                if (empty($availablePlaceholder)) {
                    // for workflow alert
                    $value = Hash::get($vars, $placeholder);
                    $message = str_replace($replace, $value, $message);
                } else if (array_key_exists('${' . $placeholder . '}', $availablePlaceholder)) {
                    // for attendance alert (alert rules)
                    $value = Hash::get($vars, $placeholder);
                    $message = str_replace($replace, $value, $message);
                }
            }
        }

        return $message;
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        return $this->statusTypes[$entity->status];
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('status', ['after' => 'message']);
        $this->field('checksum', ['visible' => false]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('message', ['visible' => false]);
        $this->field('destination', ['visible' => false]);
        $this->field('method', ['after' => 'feature', 'sort' => false]);

        // element control
        $featureOptions = $this->getFeatureOptions();
        $selectedFeature = $this->queryString('feature', $featureOptions);
        $extra['selectedFeature'] = $selectedFeature;

        $extra['elements']['control'] = [
            'name' => 'Alert/controls',
            'data' => [
                'featureOptions'=>$featureOptions,
                'selectedFeature'=>$selectedFeature,
            ],
            'options' => [],
            'order' => 3
        ];
        // end element control
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $selectedFeature = $extra['selectedFeature'];
        $featureOptions = $this->getFeatureOptions();

        if ($selectedFeature != 'AllFeatures') {
            $query->where([$this->aliasField('feature') => $selectedFeature]);
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // trigger the send email shell
        $this->triggerSendingAlertShell('SendingAlert', $entity->feature, $entity->id);
    }

    public function getFeatureOptions()
    {
        // feature from alert to be classified under general
        $AlertRules = TableRegistry::get('Alert.AlertRules');
        $alertFeatures = $AlertRules->getFeatureOptions();
        ksort($alertFeatures); // sort alphabetical

        // feature from workflow to be classified under workflow
        $WorkflowModels = TableRegistry::get('Workflow.WorkflowModels');
        $workflowFeatures = $WorkflowModels->getFeatureOptions();
        ksort($workflowFeatures); // sort alphabetical

        $features = array_merge($alertFeatures,$workflowFeatures); // combine the alert and workflow feature

        $featureOptions['AllFeatures'] = 'All Features'; // to show all the records
        foreach ($features as $key => $value) {
            if (array_key_exists($key, $alertFeatures)) {
                $featureOptions[$this->featureGrouping['general']][$key] = $value;
            } else if (array_key_exists($key, $workflowFeatures)) {
                $featureOptions[$this->featureGrouping['workflow']][$key] = $value;
            }
        }

        return $featureOptions;
    }

    public function getWorkflowEmailMessage($registryAlias)
    {
        $WorkflowModels = TableRegistry::get('Workflow.WorkflowModels');
        $Workflows = TableRegistry::get('Workflow.Workflows');

        $results = $Workflows
            ->find()
            ->matching('WorkflowModels', function($q) use ($WorkflowModels, $registryAlias) {
                return $q->where([
                    $WorkflowModels->aliasField('model') => $registryAlias
                ]);
            })
            ->first();

        if (empty($results)) {
            $this->controller->Alert->warning('Workflows.noWorkflows');
        } else {
            return $results->message;
        }
    }

    public function triggerSendingAlertShell($shellName, $feature=null, $alertLogId=0)
    {
        $args = '';
        $args .= !is_null($feature) ? ' '.$feature : '';
        $args .= !is_null($alertLogId) ? ' '.$alertLogId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }
}
