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

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

class AlertLogsTable extends ControllerActionTable
{
    private $statusTypes = [
        0 => 'Pending',
        1 => 'Success',
        -1 => 'Failed'
    ];

    private $featureGrouping = [
        'general' => 'General',
        'workflow' => 'Workflow'
    ];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->toggle('add', false);
        $this->toggle('edit', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Workflow.afterTransition'] = 'alertAssigneeAfterTransition';
        $events['Model.Workflow.onAssignBack'] = 'alertAssigneeAfterTransition';
        return $events;
    }

    public function alertAssigneeAfterTransition(Event $mainEvent, Entity $recordEntity)
    {
        $model = TableRegistry::get($recordEntity->source());
        $modelAlias = $model->alias();
        $feature = __(Inflector::humanize(Inflector::underscore($modelAlias))); // feature for control filter

        $method = __('Email'); // method will be predefined

        if ($recordEntity->assignee_id > 0) {
            // get the query for the $vars on replace message function, auto contain the belongs to associations
            $query = $model->find()->where([$model->aliasField('id') => $recordEntity->id]);

            $extra = new ArrayObject([]);
            $contain = $model->getContains('belongsTo', $extra);

            if (!empty($contain)) {
                $query->contain($contain);
            }

            $vars = $query->hydrate(false)->first();
            $vars['feature'] = $feature;

            if (!empty($vars['assignee']['email'])) { // if no email will not insert to alertlog.
                $assigneeName = $vars['assignee']['first_name'] . ' ' . $vars['assignee']['last_name'];
                $assigneeEmail = $vars['assignee']['email'];
                $recipient = $assigneeName . ' <' . $assigneeEmail . '>';

                $defaultSubject = __('[${feature}] (${status.name}) ${created_user.first_name} ${created_user.last_name}');
                $subject = $this->replaceMessage($modelAlias, $defaultSubject, $vars);

                // email message
                $defaultMessage = __('This is a default message for [${feature} workflow feature], the status of this workflow is "${status.name}". ');
                $defaultMessage .= __('This [${feature} workflow feature] was created by: ${created_user.first_name} ${created_user.last_name}.');

                $message = $this->getWorkflowEmailMessage($recordEntity->source());

                if (is_null($message)) {
                    $message = $this->replaceMessage($modelAlias, $defaultMessage, $vars);
                } else {
                    $message = $this->replaceMessage($modelAlias, $message, $vars);
                }

                // insert to the alertLog and send the email
                $this->insertAlertLog($method, $modelAlias, $recipient, $subject, $message);

                // trigger the send email shell
                $this->triggerSendingAlertShell('SendingAlert');
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
                $featureOptions[__($this->featureGrouping['general'])][$key] = $value;
            } else if (array_key_exists($key, $workflowFeatures)) {
                $featureOptions[__($this->featureGrouping['workflow'])][$key] = $value;
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

    public function triggerSendingAlertShell($shellName)
    {
        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        exec($shellCmd);
        Log::write('debug', $shellCmd);
    }
}
