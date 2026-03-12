<?php
namespace Alert\Model\Table;

use App\Controller\DashboardController;
use ArrayObject;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Routing\Router;
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

    // POCOR-9509: Updated to trigger Laravel artisan commands instead of CakePHP shells
    public static function triggerAlertCommand(string $processName, int $userId, int $ruleId, int $processId, array $extraOptions = []): void
    {
        // POCOR-9509: Map CakePHP process names to Laravel artisan commands
        // Log::debug("Triggering alert command for process: {$processName} with extra options: " . json_encode($extraOptions));
        $commandMap = [
            'AlertStudentAbsence' => 'alerts:student-absence',
            'AlertRetirementWarning' => 'alerts:retirement-warning',
            'AlertStaffEmployment' => 'alerts:staff-employment',
            'AlertStaffLeave' => 'alerts:staff-leave',
            'AlertStudentAdmission' => 'alerts:student-admission',
            'AlertStudentEnrolment' => 'alerts:student-enrolment',
            'AlertSystemUpdates' => 'alerts:system-updates',
            'StudentStatus' => 'alerts:student-status-change', // POCOR-9509
            'AlertStudentStatus' => 'alerts:student-status-change', // POCOR-9509
        ];

        $commandName = $commandMap[$processName] ?? null;

        if (!$commandName) {
            // Fallback to old CakePHP shell command for unmapped processes
            $command = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $processName));
            $argsArray = [
                '--user_id=' . $userId,
                '--rule_id=' . $ruleId,
                '--process_id=' . $processId
            ];

            foreach ($extraOptions as $key => $value) {
                $argsArray[] = '--' . $key . '=' . escapeshellarg($value);
            }

            $args = implode(' ', $argsArray);
            $cmd = ROOT . DS . 'bin' . DS . 'cake ' . $command . ' ' . $args;
            $logPath = ROOT . DS . 'logs' . DS . $command . '.log & echo $!';
            $shellCmd = $cmd . ' >> ' . $logPath;

            exec($shellCmd);
            Log::write('debug', '[AlertCommand] CakePHP Shell: ' . $shellCmd);
            return;
        }

        // POCOR-9509: Build Laravel artisan command
        $argsArray = [
            '--user_id=' . $userId,
            '--rule_id=' . $ruleId,
            '--process_id=' . $processId
        ];

        foreach ($extraOptions as $key => $value) {
            $argsArray[] = '--' . $key . '=' . escapeshellarg($value);
        }

        $args = implode(' ', $argsArray);

        // POCOR-9509: Execute Laravel artisan command in background
        $artisanPath = ROOT . DS . 'api' . DS . 'artisan';
        $cmd = 'php ' . $artisanPath . ' ' . $commandName . ' ' . $args;
        $logPath = ROOT . DS . 'logs' . DS . 'alert_' . str_replace(':', '_', $commandName) . '.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logPath;

        exec($shellCmd);
        Log::write('debug', '[POCOR-9509] Laravel Artisan: ' . $shellCmd);
    }

    /**
     * POCOR-9509: Trigger alert system process with enhanced params structure
     *
     * Enhanced params structure (Phase 2):
     * {
     *   "rule_id": 5,
     *   "entity_id": 123,
     *   "entity_type": "Student",
     *   "trigger_type": "status_change",
     *   "context": {
     *     "old_value": "Transferred",
     *     "new_value": "Withdrawn",
     *     "old_value_id": 6,
     *     "new_value_id": 7,
     *     "change_date": "2026-02-12 14:30:00",
     *     "field_name": "student_status_id"
     *   },
     *   "checksum": "sha256_hash",
     *   "triggered_at": "2026-02-12 14:30:00"
     * }
     *
     * Deduplication strategy:
     * - Uses checksum (hash of context) for efficient duplicate detection
     * - Same entity + same context = duplicate (skip)
     * - Same entity + different context = new alert (allow)
     *
     * @param mixed $systemProcessesTable SystemProcesses table instance
     * @param array $rule Alert rule data
     * @param string $processName Process name (e.g., 'AlertStudentAdmission')
     * @param int $userId User ID triggering the alert
     * @param array $extraOptions Additional options with optional 'context' key
     */
    public static function triggerAlertSystemProcess($systemProcessesTable, $rule, string $processName, int $userId, array $extraOptions = []): void
    {
        $now = FrozenTime::now();

        // POCOR-9509: Build enhanced params structure (Phase 2)
        $context = $extraOptions['context'] ?? [];
        $entityType = $extraOptions['entity_type'] ?? 'Unknown';
        $triggerType = $extraOptions['trigger_type'] ?? 'manual';

        // POCOR-9509: Generate checksum for deduplication
        // Hash the context to detect true duplicates vs. different changes to same entity
        $checksumData = [
            'entity_id' => $extraOptions['entity_id'] ?? null,
            'context' => $context,
            'trigger_type' => $triggerType,
        ];
        $checksum = hash('sha256', json_encode($checksumData));

        // POCOR-9509: Build params JSON with enhanced structure
        $params = [
            'rule_id' => $rule['id'],
            'entity_id' => $extraOptions['entity_id'] ?? null,
            'entity_type' => $entityType,
            'trigger_type' => $triggerType,
            'context' => $context,
            'checksum' => $checksum,
            'triggered_at' => $now->toDateTimeString(),
        ];

        // POCOR-9509: Add backward compatibility for old-style params
        // If no context provided, include raw extraOptions for compatibility
        if (empty($context)) {
            $params['legacy_options'] = $extraOptions;
        }

        $paramsJson = json_encode($params);
        $feature = $rule['feature'];

        // POCOR-9509: Deduplication check using checksum (Phase 2)
        // Check for existing process with same checksum (prevents true duplicates)
        $existing = $systemProcessesTable->find()
            ->where([
                'model' => $processName,
                'name' => $feature,
                'created_user_id' => $userId,
            ])
            ->where(function ($exp, $q) use ($checksum) {
                // POCOR-9509: Check if params contains this checksum
                return $exp->like('params', '%"checksum":"' . $checksum . '"%');
            })
            ->first();

        if ($existing) {
            // POCOR-9509: Skip creating duplicate process (same entity + same context)
            // Log::debug('[POCOR-9509] Duplicate alert skipped (checksum match)',
            return;
        }

        // POCOR-9509: Create system_processes record
        $processValues = [
            'name' => $feature,
            'status' => 1, // Starting
            'start_date' => $now,
            'model' => $processName,
            'created_user_id' => $userId,
            'params' => $paramsJson
        ];

        // POCOR-9509: Prepare extraOptions for Laravel command (remove context, keep IDs)
        $commandOptions = $extraOptions;
        unset($commandOptions['context']); // Context already in params, don't pass to command
        unset($commandOptions['entity_type']);
        unset($commandOptions['trigger_type']);
        unset($commandOptions['status_id']); // Remove to avoid confusion with student_status_id
        unset($commandOptions['student_status_id']); // Remove, already in context

        $process = $systemProcessesTable->newEntity($processValues);
        if ($systemProcessesTable->save($process)) {
            // Log::debug('[POCOR-9509] Alert process created',
            self::triggerAlertCommand($processName, $userId, $rule['id'], $process->id, $commandOptions);
        } else {
            Log::error('[POCOR-9509] Failed to create alert process', [
                'feature' => $feature,
                'errors' => $process->getErrors(),
            ]);
        }
    }

    /**
     * POCOR-9509: Helper to trigger Laravel-based alerts from CakePHP models.
     * This centralizes the logic for finding alert rules and dispatching to the Laravel queue.
     *
     * Enhanced in Phase 2 to support context parameter for better deduplication.
     *
     * USAGE EXAMPLES:
     *
     * Basic (backward compatible):
     *   AlertLogsTable::triggerLaravelAlertFromCakePHP('AlertStudentAdmission', $entity, $userId);
     *
     * Enhanced with context (recommended for status changes):
     *   AlertLogsTable::triggerLaravelAlertFromCakePHP(
     *       'AlertStudentStatus',
     *       $entity,
     *       $userId,
     *       [
     *           'old_value' => 'Transferred',
     *           'new_value' => 'Withdrawn',
     *           'old_value_id' => 6,
     *           'new_value_id' => 7,
     *           'field_name' => 'student_status_id'
     *       ]
     *   );
     *
     * @param string $alertProcessName The process name as defined in Alert.Alerts (e.g., 'AlertStudentAdmission').
     * @param \Cake\ORM\Entity $entity The CakePHP entity that triggered the alert (e.g., StudentAdmission entity).
     * @param int $userId The ID of the user who triggered the action.
     * @param array $context Optional context for deduplication (old_value, new_value, etc.)
     * @return void
     */
    public static function triggerLaravelAlertFromCakePHP(
        string $alertProcessName,
        Entity $entity,
        int $userId,
        array $context = []
    ): void {
        $alertsTable = TableRegistry::getTableLocator()->get('Alert.Alerts');
        $alertRulesTable = TableRegistry::getTableLocator()->get('Alert.AlertRules');
        $systemProcessesTable = TableRegistry::getTableLocator()->get('SystemProcesses');

        $alert = $alertsTable
            ->find()
            ->where([
                $alertsTable->aliasField('process_name') => $alertProcessName,
                $alertsTable->aliasField('frequency') => 'once' // Specific frequency for these types of alerts
            ])
            ->first();

        if (!$alert) {
            Log::error("[POCOR-9509] No Alerts configured for process: {$alertProcessName}");
            return;
        }

        $activeRules = $alertRulesTable->find()
            ->where([
                $alertRulesTable->aliasField('feature') => $alert->name,
                $alertRulesTable->aliasField('enabled') => 1
            ])
            ->toArray();

        if (empty($activeRules)) {
            // Log::debug("[POCOR-9509] No active alert rules found for feature: {$alert->name}");
            return;
        }

        foreach ($activeRules as $rule) {
            if (!is_array($rule)) {
                $rule = $rule->toArray();
            }

            // POCOR-9509: Build enhanced extraOptions with context support (Phase 2)
            $extraOptions = [
                'entity_type' => $alertProcessName, // e.g., 'AlertStudentStatus'
                'trigger_type' => !empty($context) ? 'status_change' : 'event',
            ];

            // POCOR-9509: Dynamically build extraOptions based on the alert type
            // Log::debug('[POCOR-9509] Building alert options', [


            switch ($alertProcessName) {
                case 'AlertStudentEnrolment':
                case 'StudentStatus':
                case 'AlertStudentStatus':
                case 'AlertStudentAdmission':
                    $extraOptions['entity_id'] = $entity->id;
                    break;
                // Add other cases for different alert types if needed
            }

            // POCOR-9509: Add context data if provided (Phase 2)
            if (!empty($context)) {
                // Enhanced context with old/new values for proper deduplication
                $extraOptions['context'] = array_merge($context, [
                    'change_date' => $entity->modified ?? $entity->created ?? date('Y-m-d H:i:s'),
                    'entity_id' => $entity->id,
                ]);
            } else {
                // POCOR-9509: Fallback context - build from entity current state
                // This provides basic deduplication but won't distinguish between
                // different status changes on the same entity
                $contextData = [];

                switch ($alertProcessName) {
                    case 'StudentStatus':
                    case 'AlertStudentStatus':
                        if (isset($entity->student_status_id)) {
                            $contextData['field_name'] = 'student_status_id';
                            $contextData['new_value_id'] = $entity->student_status_id;
                            // Old value not available without explicit context
                        }
                        break;

                    case 'AlertStudentEnrolment':
                    case 'AlertStudentAdmission':
                        if (isset($entity->status_id)) {
                            $contextData['field_name'] = 'status_id';
                            $contextData['new_value_id'] = $entity->status_id;
                        }
                        break;
                }

                if (!empty($contextData)) {
                    $contextData['change_date'] = $entity->modified ?? $entity->created ?? date('Y-m-d H:i:s');
                    $contextData['entity_id'] = $entity->id;
                    $extraOptions['context'] = $contextData;
                }
            }

            self::triggerAlertSystemProcess($systemProcessesTable, $rule, $alertProcessName, $userId, $extraOptions);
        }
    }

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->featureGrouping = $this->getSelectOptions($this->aliasField('feature_grouping'));
        $this->AlertRules = TableRegistry::getTableLocator()->get('Alert.AlertRules');
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('view', true);
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
            // Log::debug("❌ Unknown feature '{$feature}' passed to insertAlertLog.");
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

            // POCOR-9509: Queue unsent alerts for async processing
            if ($log->status === 0) {
                $this->queueAlertForAsyncSending(
                    $log,
                    $log->method,
                    $log->feature,
                    $log->destination,
                    $log->subject,
                    $log->message,
                    $log->checksum
                );
                // Legacy trigger commented out (async queue handles it now)
                // $this->triggerSendingAlertCommand('sending_alert', $feature, $log->id, __FUNCTION__, __LINE__);
                // sleep(10); // No longer needed with async queue
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

    // POCOR-9509: Updated to queue alerts asynchronously
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

                // POCOR-9509: Queue alert for async sending
                $this->queueAlertForAsyncSending($saved, $method, $feature, $recipient, $subject, $message, $checksum);
            }
        }

        // POCOR-9509: Keep legacy trigger for backward compatibility (can be removed after full migration)
        foreach ($savedIds as $id) {
            // $this->triggerSendingAlertCommand('sending_alert', $feature, $id, __FUNCTION__, __LINE__);
            // Commented out: Async queue handles sending now
        }
    }

    // POCOR-9509: Queue alert to alerts_queue table for async worker processing
    private function queueAlertForAsyncSending(
        Entity $alertLogEntity,
        string $method,
        string $feature,
        string $recipient,
        ?string $subject,
        ?string $message,
        string $checksum
    ): void {
        try {
            $AlertsQueue = TableRegistry::getTableLocator()->get('AlertsQueue');

            // Map method to channel (Email/SMS → email/sms)
            $channel = strtolower($method);

            $queued = $AlertsQueue->queueAlert(
                $feature,              // alert_type (e.g., 'StudentAttendance', 'StaffLeave')
                $channel,              // channel ('email' or 'sms')
                $recipient,            // recipient (email address or phone number)
                $message ?? '',        // message_body (placeholders already replaced)
                $subject,              // subject (nullable, for email)
                [                      // payload (metadata for tracking)
                    'alert_log_id' => $alertLogEntity->id,
                    'checksum' => $checksum,
                    'feature' => $feature
                ]
            );

            if ($queued) {
                // Log::debug("✅ [POCOR-9509] Alert queued for async sending", [

            } else {
                Log::error("❌ [POCOR-9509] Failed to queue alert", [
                    'alert_log_id' => $alertLogEntity->id,
                    'feature' => $feature,
                    'method' => $method,
                    'recipient' => $recipient,
                    'reason' => 'queueAlert returned false'
                ]);
            }
        } catch (\Exception $e) {
            // POCOR-9509: Don't fail the whole process if queueing fails
            // Alert is still logged in alert_logs for manual retry
            Log::error("❌ [POCOR-9509] Exception while queueing alert", [
                'alert_log_id' => $alertLogEntity->id,
                'feature' => $feature,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
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
        // POCOR-9509: Add checkbox column for multiselect
        $this->field('_select', [
            'type' => 'element',
            'element' => 'Alert/select_checkbox',
            'tableHeaderClass' => 'checkbox-column',
            'tableColumnClass' => 'checkbox-column',
            'label' => ''
        ]);

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

        // POCOR-9509: Add status and channel filter options
        $statusOptions = $this->getStatusOptions();
        $channelOptions = $this->getChannelOptions();
        $selectedStatus = $this->queryString('status', $statusOptions);
        $selectedChannel = $this->queryString('channel', $channelOptions);

        $extra['elements']['control']['data'] = array_merge(
            $extra['elements']['control']['data'],
            [
                'statusOptions' => $statusOptions,
                'selectedStatus' => $selectedStatus,
                'channelOptions' => $channelOptions,
                'selectedChannel' => $selectedChannel
            ]
        );

        // POCOR-9509: Add bulk delete toolbar button and JS
        $extra['toolbarButtons']['deleteSelected'] = [
            'type' => 'element',
            'element' => 'Alert/delete_selected_button',
            'data' => [
                'id' => 'delete-selected-btn',
                'classes' => 'btn btn-xs btn-default icon-big',
                'disabled' => true
            ],
            'options' => []
        ];


        $extra['elements']['bulkActionsJs'] = [
            'name' => 'Alert/bulk_actions_js',
            'data' => [
                'deleteUrl' => Router::Url(['plugin' => 'Alert', 'controller' => 'Alerts', 'action' => 'logsDeleteSelected', true])
            ],
            'options' => [],
            'order' => 4
        ];

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
        if ($selectedFeature !== null && $selectedFeature !== '' && $selectedFeature !== 'all') {
            $query->where(['feature' => $selectedFeature]);
        }

        // POCOR-9509: Apply status and channel filters
        $status = $this->request->getQuery('status');
        $channel = $this->request->getQuery('channel');

        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where(['status' => $status]);
        }
        if ($channel !== null && $channel !== '' && $channel !== 'all') {
            $query->where(['method' => $channel]);
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
        $featureOptions['all'] = __('All Features'); // to show all the records
        foreach ($features as $key => $value) {
            if (array_key_exists($key, $alertFeatures)) {
                $featureOptions[$this->featureGrouping['general']][$key] = $value;
            } else if (array_key_exists($key, $workflowFeatures)) {
                $featureOptions[$this->featureGrouping['workflow']][$key] = $value;
            }
        }

        return $featureOptions;
    }

    /**
     * POCOR-9509: Get status options for filter dropdown
     */
    public function getStatusOptions(): array
    {
        return [
            'all' => __('All Statuses'),
            0 => __('Pending'),
            1 => __('Success'),
            -1 => __('Failed')
        ];
    }

    /**
     * POCOR-9509: Get channel options for filter dropdown
     */
    public function getChannelOptions(): array
    {
        return [
            'all' => __('All Channels'),
            'Email' => __('Email'),
            'SMS' => __('SMS')
        ];
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
            case '_select':
                return '';
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    private function formatRecipientNameEmail($user): string
    {
        return $user->name . ' <' . $user->email . '>';
    }
}
