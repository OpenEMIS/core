<?php
namespace Alert\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\Utility\Security;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\FrozenTime; // POCOR-8286
use Cake\I18n\FrozenDate; // POCOR-8286

class AlertLogsTable extends ControllerActionTable
{
    use OptionsTrait;

    private $statusTypes = [
        0 => 'Pending',
        1 => 'Success',
        -1 => 'Failed'
    ];

    private $featureGrouping = [];

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->featureGrouping = $this->getSelectOptions($this->aliasField('feature_grouping'));
        $this->AlertRules = TableRegistry::getTableLocator()->get('Alert.AlertRules');
        $this->toggle('add', false);
        $this->toggle('edit', false);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Workflow.afterSave'] = 'alertAssigneeAfterSave';
        return $events;
    }

    public function alertAssigneeAfterSave(EventInterface $mainEvent, Entity $recordEntity)
    {
        $WorkflowTransitions = TableRegistry::getTableLocator()->get('Workflow.WorkflowTransitions');
        $WorkflowSteps = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
        $WorkflowModels = TableRegistry::getTableLocator()->get('Workflow.WorkflowModels');
        $Users = TableRegistry::getTableLocator()->get('User.Users');

        if ($recordEntity->has('status_id') && $recordEntity->status_id > 0) {
            // used to get correct workflow model for StaffTransferIn and StaffTransferOut
            $stepEntity = $WorkflowSteps->find()
                ->matching('Workflows.WorkflowModels')
                ->where([$WorkflowSteps->aliasField('id') => $recordEntity->status_id])
                ->first();

            if (!empty($stepEntity)) {
                $modelName = $stepEntity->_matchingData['WorkflowModels']->model;
            }
        }

        $workflowModel = isset($modelName) ? $modelName : $recordEntity->getSource();
        $model = TableRegistry::getTableLocator()->get($workflowModel);
        $modelAlias = $model->getAlias();
        $modelRegistryAlias = $model->getRegistryAlias();
        $feature = __(Inflector::humanize(Inflector::underscore($modelAlias))); // feature for control filter

        $method = 'Email'; // method will be predefined

        if ($recordEntity->has('assignee_id') && $recordEntity->assignee_id > 0) {
            // to get the comment inputted on the workflow popup
            $workflowModelEntity = $WorkflowModels->find()
                ->where([$WorkflowModels->aliasField('model') => $modelRegistryAlias])
                ->first();
            $workflowModelId = $workflowModelEntity->id ?? null;

            $records = $WorkflowTransitions->find()
                ->where([
                    $WorkflowTransitions->aliasField('model_reference') => $recordEntity->id,
                    $WorkflowTransitions->aliasField('workflow_model_id') => $workflowModelId
                ])
                ->last();

            // get the query for the $vars on replace message function, auto contain the belongs to associations
            $query = $model->find()->where([$model->aliasField('id') => $recordEntity->id]);

            $extra = new ArrayObject([]);
            if (isset($model->CAVersion) && $model->CAVersion == '4.0') {
                $contain = $model->getContains('belongsTo', $extra);
            } else {
                $contain = $model->ControllerAction->getContains($model, 'belongsTo');
            }

            if (!empty($contain)) {
                $query->contain($contain);
            }

            if ($records) {
                $lastExecutorId = $records->created_user_id;
                if($lastExecutorId){ //POCOR-7964
                    $lastExecutorName = $lastExecutorId;
                    $lastExecutor = $Users->get($lastExecutorId);
                    if ($lastExecutor) {
                        $lastExecutorName = $Users->get($lastExecutorId)->name;
                    }
                }

                $vars = $query->disableHydration()->first();
                $vars['feature'] = $feature;
                $vars['last_executor_id'] = $lastExecutorId;
                $vars['last_executor_name'] = $lastExecutorName ?? $lastExecutorId;
                $vars['workflow_comment'] = $records->comment;
                $assigneeData = $vars['assignee'];
                $recipient = null;

                if (
                    is_array($assigneeData ?? null) &&
                    !empty($assigneeData['id']) &&
                    is_numeric($assigneeData['id']) &&
                    !empty($assigneeData['email'])
                ) {
                    // Use `find()->first()` to avoid exception if user not found
                    $user = $Users->find()
                        ->where(['id' => $assigneeData['id']])
                        ->first();

                    if ($user) {
                        $recipient = $this->formatRecipientNameEmail($user);
                    }
                }

                if (!empty($recipient)) {
                    $defaultSubject = __('[${feature}] (${status.name}) ${created_user.first_name} ${created_user.last_name}');
                    $subject = $this->replaceMessage($modelAlias, $defaultSubject, $vars, true);

                    $defaultMessage = __('Your action is required for [${feature} Workflow].') . "\n";
                    $defaultMessage .= "\n" . __('Status') . ': ' . "\t\t" . '${status.name}' . "\n";
                    $defaultMessage .= "\n" . __('Sent By') . ': ' . "\t\t" . '${last_executor_name}' . "\n";
                    $defaultMessage .= "\n" . __('Comments') . ': ' . "\t" . '${workflow_comment}';

                    $customMessage = $this->getWorkflowEmailMessage($recordEntity);
                    $finalMessage = $this->replaceMessage($modelAlias, $customMessage ?? $defaultMessage, $vars, true);

                    $this->insertAlertLog($method, $modelAlias, $recipient, $subject, $finalMessage);
                }
            }
        }// end if have assignee id in the recordEntity
    }

    // POCOR-8286-start
    public function insertAlertLog(string $method, string $feature, string $recipient, ?string $subject = null, ?string $message = null): void
    {
        $alertFeatures = $this->AlertRules->getFeatureOptions();
        $checksum = $this->generateChecksum($subject . $recipient . $feature . $method, $message); // POCOR-9213
        $alertFeatures['Messaging'] = __('Messaging');

        if (!array_key_exists($feature, $alertFeatures)) {
            Log::debug("❌ Unknown feature '{$feature}' passed to insertAlertLog.");
            return;
        }

        // Find any logs (sent, sending, or unsent) with the same checksum
        $existingLogs = $this->find()
            ->where([
                'checksum' => $checksum
            ])
            ->all();

        $alreadyProcessed = [];

        foreach ($existingLogs as $log) {
            $alreadyProcessed[] = $log->destination;

            // Only trigger resend if status is still 0 (unsent)
            if ($log->status === 0) {
                $this->triggerSendingAlertCommand('sending_alert', $feature, $log->id, __FUNCTION__, __LINE__);
                sleep(10);
            }
        }

        // If the recipient has not been processed yet (sent/sending/unsent), send it
        if (!in_array($recipient, $alreadyProcessed, true)) {
            $this->createAndSendAlertLog($method, $feature, [$recipient], $subject, $message, $checksum);
        }
    }

//    public function insertStudentAdmissionAlertLog(string $method, string $feature, string $recipient, ?string $subject = null, ?string $message = null): void
//    {
//        $checksum = $this->generateChecksum($subject, $message);
//        $this->createAndSendAlertLog($method, $feature, [$recipient], $subject, $message, $checksum);
//    }

    private function generateChecksum(?string $subject, ?string $message): string
    {
        return Security::hash("{$subject},{$message}", 'sha256');
    }

    private function createAndSendAlertLog(
        string $method,
        string $feature,
        array $recipients,
        ?string $subject,
        ?string $message,
        string $checksum
    ): void {
        $savedIds = [];

        foreach ($recipients as $recipient) {
            $entity = $this->newEntity([
                'feature' => $feature,
                'method' => $method,
                'destination' => $recipient,
                'status' => 0,
                'subject' => $subject,
                'message' => $message,
                'checksum' => $checksum
            ]);

            $saved = $this->save($entity);

            if ($saved) {
                $savedIds[] = $saved->id;
            }
        }

        foreach ($savedIds as $id) {
            $this->triggerSendingAlertCommand('sending_alert', $feature, $id, __FUNCTION__, __LINE__);
        }
    }
    // POCOR-8286 end

    public function replaceMessage($feature, $message, $vars, $workflow = false)
    {

        $alertFeatures =  $this->AlertRules->getFeatureOptions();

        $format = '${%s}';
        $strArray = explode('${', $message);
        array_shift($strArray); // first element will not contain the placeholder

        $availablePlaceholder = [];
        if (array_key_exists($feature, $alertFeatures)) {
            // for feature from alert Rule to get the availablePlaceholder
            $alertTypeDetails =  $this->AlertRules->getAlertTypeDetailsByFeature($feature);
            $availablePlaceholder = $alertTypeDetails[$feature]['placeholder'];
        }

        foreach ($strArray as $key => $str) {
            $pos = strpos($str, '}');

            if ($pos !== false) {
                $placeholder = substr($str, 0, $pos);
                $replace = sprintf($format, $placeholder);

                if (empty($availablePlaceholder) || $workflow) {
                    // for workflow alert
                    $value = Hash::get($vars, $placeholder);
                    if ($value instanceof FrozenDate || $value instanceof \Cake\I18n\Date) {
                        $value = $this->formatDate($value);
                    }
                    $message = str_replace($replace, $value, $message);
                } else if (array_key_exists('${' . $placeholder . '}', $availablePlaceholder)) {
                    // for attendance alert (alert rules)
                    $value = Hash::get($vars, $placeholder);
                    if ($value instanceof FrozenDate || $value instanceof \Cake\I18n\Date) { // POCOR-8286
                        $value = $this->formatDate($value);
                    }
                    $message = str_replace($replace, $value, $message);
                }
            }
        }

        return $message;
    }

    public function onGetFeature(EventInterface $event, Entity $entity)
    {
        return Inflector::humanize(Inflector::underscore($entity->feature));
    }

    public function onGetStatus(EventInterface $event, Entity $entity)
    {
        return $this->statusTypes[$entity->status];
    }

    //6023 starts
    public function onGetProcessedDate(EventInterface $event, Entity $entity)
    {
        if(!empty($entity->processed_date)){
            return date('Y-m-d', strtotime($entity->processed_date));
        }
    }//6023 ends

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('status', ['after' => 'message']);
        $this->field('checksum', ['visible' => false]);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('message', ['visible' => false]);
        $this->field('method', ['after' => 'feature', 'sort' => true]);
        $this->field('destination', ['after' => 'method', 'visible' => true]);

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

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Logs','Communications');
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        //$selectedFeature = $extra['selectedFeature'];
        $featureOptions = $this->getFeatureOptions();
        $selectedFeature = $this->request->getQuery('feature');
        if ($selectedFeature != -1 && !empty($selectedFeature)) {
            $query->where(['feature' => $selectedFeature]);
        }
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        // trigger the send email shell
        //$this->triggerSendingAlertShell('SendingAlert', $entity->feature, $entity->id);
        //comment this shell because of POCOR-6023 ticket. Not receiving data from entity->id
    }

    public function getFeatureOptions()
    {
        // feature from alert to be classified under general

        $alertFeatures =  $this->AlertRules->getFeatureOptions();
        ksort($alertFeatures); // sort alphabetical

        // feature from workflow to be classified under workflow
        $WorkflowModels = TableRegistry::getTableLocator()->get('Workflow.WorkflowModels');
        $workflowFeatures = $WorkflowModels->getFeatureOptions();
        ksort($workflowFeatures); // sort alphabetical

        $features = array_merge($alertFeatures, $workflowFeatures); // combine the alert and workflow feature
        $alertFeatures['Messaging'] = __('Messaging');
        $featureOptions['AllFeatures'] = __('All Features'); // to show all the records
        foreach ($features as $key => $value) {
            if (array_key_exists($key, $alertFeatures)) {
                $featureOptions[$this->featureGrouping['general']][$key] = $value;
            } else if (array_key_exists($key, $workflowFeatures)) {
                $featureOptions[$this->featureGrouping['workflow']][$key] = $value;
            }
        }

        return $featureOptions;
    }

    public function getWorkflowEmailMessage($recordEntity): ?string
    {
        $message = null;

        if ($recordEntity->has('status_id') && !empty($recordEntity->status_id)) {
            $WorkflowModels = TableRegistry::getTableLocator()->get('Workflow.WorkflowModels');
            $Workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');
            $WorkflowSteps = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');

            $workflowStepEntity = $WorkflowSteps
                ->find()
                ->contain(['Workflows'])
                ->where([$WorkflowSteps->aliasField('id') => $recordEntity->status_id])
                ->first();

            if (!empty($workflowStepEntity)) {
                $message = $workflowStepEntity->workflow->message;
            }
        }

        return $message;
    }

    public function triggerSendingAlertCommand(string $commandName, ?string $feature = null, int $alertLogId = 0, $function = '', $line = 0): void
    {
        $args = '';
        if (!is_null($feature)) {
            $args .= ' ' . escapeshellarg($feature);
        }
        if ($alertLogId > 0) {
            $args .= ' ' . escapeshellarg((string)$alertLogId);
        }

        $cmdPath = ROOT . DS . 'bin' . DS . 'cake ' . $commandName . $args;
        $logPath = ROOT . DS . 'logs' . DS . $commandName . '.log';
        $shellCmd = $cmdPath . ' >> ' . $logPath . ' & echo $!';

        exec($shellCmd);
        Log::write('debug', "Executing command from $function $line: " . $shellCmd);
    }

    /** @deprecated Use triggerSendingAlertCommand() instead */
    public function triggerSendingAlertShell($shellName, $feature = null, $alertLogId = 0)
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'feature':
                return __('Feature');
            case 'subject':
                return __('Subject');
            case 'status':
                return __('Status');
            case 'processed_date':
                return __('Process Date');
            case 'method':
                return __('Method');
            case 'destination':
                return __('Destination');
            case 'message':
                return __('Message');
            case 'created':
                return __('Created By');
            case 'created_user_id':
                return __('Created On');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    private function formatRecipientNameEmail($user): string
    {
        return $user->name . ' <' . $user->email . '>';
    }
}
