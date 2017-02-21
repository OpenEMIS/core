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

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->toggle('add', false);
        $this->toggle('edit', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.Workflow.afterTransition'] = 'workflowAfterTransition';
        return $events;
    }

    public function workflowAfterTransition(Event $mainEvent, Entity $workflowAfterTransitionEntity)
    {
        $Users = TableRegistry::get('Security.Users');
        $WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');

        $modelKey = $workflowAfterTransitionEntity->source();
        $statusId = $workflowAfterTransitionEntity->status_id;
        $statusName = $WorkflowSteps->get($statusId)->name;

        $assigneeId = $workflowAfterTransitionEntity->assignee_id;
        $assigneeName = $Users->get($assigneeId)->first_name . ' ' . $Users->get($assigneeId)->last_name;
        $assigneeEmail = $Users->get($assigneeId)->email; // destination email
        $recipient = $assigneeName . ' <' . $assigneeEmail . '>';

        $creatorId = $workflowAfterTransitionEntity->created_user_id;
        $creatorName = $Users->get($creatorId)->first_name . ' ' . $Users->get($creatorId)->last_name;

        $method = 'Email'; // method will be predefined

        $workflowModel = TableRegistry::get($modelKey);
        $workflowAlias = $workflowModel->alias();
        $feature = Inflector::humanize(Inflector::underscore($workflowAlias)); // feature for control filter

        // default subject and message. if the subject and message null.
        // check if workflow table have message set, if not will used the default subject and message.
        // placeholder need to used the replaceMessage()
        $subject = '[' . $feature . '] (' . $creatorName .  ' - ' . $statusName . ')';
        $message = 'default message';

        // pr('workflowAfterSave - AlertLogsTable');
        // pr('modelKey '.$modelKey);
        // pr('assigneeId '.$assigneeId);
        // pr('statusId '.$statusId);
        // pr('workflowModel '.$workflowModel);
        // pr('workflowAlias '.$workflowAlias);
        // pr('feature '.$feature);
        // pr('subject '.$subject);
        // pr('message '.$message);
        // pr($workflowAfterTransitionEntity);
        // die;

        // insert to the alertLog
        $this->insertAlertLog($method, $feature, $recipient, $subject, $message);

        // trigger the send email shell
        $this->triggerSendingAlertShell('SendingAlert');
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

        $AlertRules = TableRegistry::get('Alert.AlertRules');

        $alertTypeDetails = $AlertRules->getAlertTypeDetailsByFeature($feature);
        $availablePlaceholder = $alertTypeDetails[$feature]['placeholder'];

        foreach ($strArray as $key => $str) {
            $pos = strpos($str, '}');

            if ($pos !== false) {
                $placeholder = substr($str, 0, $pos);
                $replace = sprintf($format, $placeholder);

                if (array_key_exists('${' . $placeholder . '}', $availablePlaceholder)) {
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

        // element control
        $featureOptions = $this->getFeatureOptions();

        if (!empty($featureOptions)) {
            array_unshift($featureOptions, "All Features");
        }

        $selectedFeatureId = $this->queryString('feature', $featureOptions);
        $selectedFeatureName = $featureOptions[$selectedFeatureId];

        $extra['selectedFeature'] = $selectedFeatureId;
        $extra['selectedFeatureName'] = $selectedFeatureName;

        $extra['elements']['control'] = [
            'name' => 'Alert/controls',
            'data' => [
                'featureOptions'=>$featureOptions,
                'selectedFeature'=>$selectedFeatureId,
            ],
            'options' => [],
            'order' => 3
        ];
        // end element control
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $selectedFeatureId = $extra['selectedFeature'];
        $selectedFeatureName = $extra['selectedFeatureName'];

        if ($selectedFeatureId != 0) {
            $query->where(['feature' => $selectedFeatureName]);
        }
    }

    public function getFeatureOptions()
    {
        $featureResults = $this->find()
            ->distinct(['feature'])
            ->all();

        $featureOptions = [];
        foreach ($featureResults as $key => $obj) {
            $featureOptions[] = __($obj['feature']);
        }

        return $featureOptions;
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
