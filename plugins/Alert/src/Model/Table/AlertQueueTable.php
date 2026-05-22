<?php
declare(strict_types=1);

namespace Alert\Model\Table;

//POCOR-9509: Add AlertQueueTable for viewing async alert queue
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\Log\Log;
use Cake\ORM\Query;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;
use DateTime;
use DateTimeInterface;

class AlertQueueTable extends ControllerActionTable
{
    //POCOR-9509: Numeric status constants — language-agnostic, used by both CakePHP and Laravel
    // alert_queue.status uses: PENDING, PROCESSING, SENT, FAILED
    // alert_logs.status uses:  PENDING, SENT, FAILED
    const STATUS_PENDING = 0;
    const STATUS_PROCESSING = 1;
    const STATUS_SENT = 2;
    const STATUS_FAILED = -1;
    const STATUS_DEDUPED = 4; //POCOR-9509: queue row was a same-(feature,method,destination,checksum) duplicate — hidden from listing

    private array $statusTypes = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_PROCESSING => 'Processing',
        self::STATUS_SENT => 'Sent',
        self::STATUS_FAILED => 'Failed',
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('alert_queue');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        // Disable add/edit/delete - queue entries are managed by the system
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('view', true);
        $this->toggle('delete', false);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra): void
    {
        // Configure which fields to show and their order
        $this->field('alert_type', ['after' => 'id']);
        $this->field('channel', ['after' => 'alert_type']);
        $this->field('recipient', ['after' => 'channel']);
        $this->field('subject', ['after' => 'recipient', 'truncate' => 50]);
        $this->field('status', ['after' => 'subject']);
        $this->field('retry_count', ['after' => 'status']);
        $this->field('available_at', ['after' => 'retry_count']);
        $this->field('sent_at', ['after' => 'available_at']);
        $this->field('created', ['after' => 'sent_at']);
        $this->field('modified', ['visible' => false]);
        $this->field('payload', ['visible' => false]);
        $this->field('message_body', ['visible' => false]);
        $this->field('last_error', ['visible' => false]);

        // Add filter controls
        $statusOptions = $this->getStatusOptions();
        $channelOptions = $this->getChannelOptions();
        $alertTypeOptions = $this->getAlertTypeOptions();

        $selectedStatus = $this->queryString('status', $statusOptions);
        $selectedChannel = $this->queryString('channel', $channelOptions);
        $selectedAlertType = $this->queryString('alert_type', $alertTypeOptions);

        $extra['selectedStatus'] = $selectedStatus;
        $extra['selectedChannel'] = $selectedChannel;
        $extra['selectedAlertType'] = $selectedAlertType;

        $extra['elements']['queueControl'] = [
            'name' => 'Alert/controls',
            'data' => [
                'statusOptions' => $statusOptions,
                'selectedStatus' => $selectedStatus,
                'channelOptions' => $channelOptions,
                'selectedChannel' => $selectedChannel,
                'alertTypeOptions' => $alertTypeOptions,
                'selectedAlertType' => $selectedAlertType,
            ],
            'options' => [],
            'order' => 3,
        ];

        //POCOR-9509: Trigger Alert Check — checks frequency rules and fills alert_queue
        $checkButton = [
            'type' => 'button',
            'label' => '<i class="fa fa-refresh"></i>',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Trigger Alert Check')
            ],
            'url' => [
                'plugin' => 'Alert', 'controller' => 'Alerts',
                'action' => 'processLogs',
            ]
        ];
        //POCOR-9509: Trigger Alert Send — sends all pending items from alert_queue
        $sendButton = [
            'type' => 'button',
            'label' => '<i class="fa fa-paper-plane"></i>',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Trigger Alert Send')
            ],
            'url' => [
                'plugin' => 'Alert', 'controller' => 'Alerts',
                'action' => 'processQueue',
            ]
        ];
        //POCOR-9694: cross-link to Async Services → Queue Backlog dashboard.
        $checkBacklog = [
            'type' => 'button',
            'label' => '<i class="fa fa-tasks"></i>',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Check backlog'),
            ],
            'url' => [
                'plugin' => 'System', 'controller' => 'Systems',
                'action' => 'QueueBacklog',
            ],
        ];

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarButtonsArray['alertCheck'] = $checkButton;
        $toolbarButtonsArray['alertSend'] = $sendButton;
        $toolbarButtonsArray['checkBacklog'] = $checkBacklog;

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        $this->controller->set('toolbarButtons', $extra['toolbarButtons']);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): void
    {
        $status = $this->request->getQuery('status');
        $channel = $this->request->getQuery('channel');
        $alertType = $this->request->getQuery('alert_type');

        //POCOR-9509: Hide DEDUPED rows from the listing — they are kept in the table
        //for audit but are not part of the active queue.
        $query->where([$this->aliasField('status') . ' !=' => self::STATUS_DEDUPED]);

        //POCOR-9509: Convert status to integer for proper comparison with SMALLINT column
        if ($status !== null && $status !== '' && $status !== 'all') {
            $query->where([$this->aliasField('status') => (int)$status]);
        }
        if ($channel !== null && $channel !== '' && $channel !== 'all') {
            $query->where([$this->aliasField('channel') => $channel]);
        }
        if ($alertType !== null && $alertType !== '' && $alertType !== 'all') {
            $query->where([$this->aliasField('alert_type') => $alertType]);
        }
    }

    //POCOR-9509: No onGetSelect needed — type:'element' renders select_checkbox.php via HtmlField->element()

    public function onGetStatus(EventInterface $event, $entity): string
    {
        return $this->statusTypes[$entity->status] ?? (string)$entity->status;
    }

    public function onGetChannel(EventInterface $event, $entity): string
    {
        return ucfirst((string)$entity->channel);
    }

    public function onGetAlertType(EventInterface $event, $entity): string
    {
        return $entity->alert_type;
    }

    public function onGetRecipient(EventInterface $event, $entity): string
    {
        $recipient = $entity->recipient;
        if (strlen($recipient) > 50) {
            return substr($recipient, 0, 47) . '...';
        }
        return $recipient;
    }

    public function onGetSubject(EventInterface $event, $entity): string
    {
        $subject = $entity->subject;
        if (empty($subject)) {
            return __('(No Subject)');
        }
        if (strlen($subject) > 50) {
            return substr($subject, 0, 47) . '...';
        }
        return $subject;
    }

    public function onGetAvailableAt(EventInterface $event, $entity): ?string
    {
        if (!empty($entity->available_at)) {
            /** @var \Cake\I18n\FrozenTime $available_at */
            $available_at = $entity->available_at;
            return $available_at->i18nFormat('yyyy-MM-dd HH:mm');
        }
        return null;
    }

    public function onGetSentAt(EventInterface $event, $entity): ?string
    {
        if (!empty($entity->sent_at)) {
            /** @var \Cake\I18n\FrozenTime $sent_at */
            $sent_at = $entity->sent_at;
            return $sent_at->i18nFormat('yyyy-MM-dd HH:mm');
        }
        return null;
    }

    public function onGetCreated(EventInterface $event, $entity): ?string
    {
        if (!empty($entity->created)) {
            /** @var \Cake\I18n\FrozenTime $created */
            $created = $entity->created;
            return $created->i18nFormat('yyyy-MM-dd HH:mm');
        }
        return null;
    }

    public function getStatusOptions(): array
    {
        return array_merge(['all' => __('All Statuses')], $this->statusTypes);
    }

    public function getChannelOptions(): array
    {
        return [
            'all' => __('All Channels'),
            'email' => __('Email'),
            'sms' => __('SMS')
        ];
    }

    public function getAlertTypeOptions(): array
    {
        // Get distinct alert types from the database
        $query = $this->find()
            ->select(['alert_type'])
            ->distinct('alert_type')
            ->order(['alert_type' => 'ASC']);

        $options = ['all' => __('All Types')];
        foreach ($query->all() as $row) {
            $options[$row->alert_type] = $row->alert_type;
        }

        return $options;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true): string
    {
        switch ($field) {
            case 'alert_type':
                return __('Alert Type');
            case 'channel':
                return __('Channel');
            case 'recipient':
                return __('Recipient');
            case 'subject':
                return __('Subject');
            case 'status':
                return __('Status');
            case 'retry_count':
                return __('Retries');
            case 'available_at':
                return __('Available At');
            case 'sent_at':
                return __('Sent At');
            case 'created':
                return __('Created');
            case 'message_body':
                return __('Message');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    // -------------------------------------------------------------------------
    // POCOR-9509: Business logic methods (merged from App\Model\Table\AlertQueueTable)
    // -------------------------------------------------------------------------

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->nonNegativeInteger('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->scalar('alert_type')
            ->maxLength('alert_type', 100)
            ->requirePresence('alert_type', 'create')
            ->notEmptyString('alert_type');

        $validator
            ->scalar('channel')
            ->maxLength('channel', 20)
            ->requirePresence('channel', 'create')
            ->notEmptyString('channel')
            ->inList('channel', ['email', 'sms'], 'Channel must be either email or sms');

        $validator
            ->scalar('recipient')
            ->maxLength('recipient', 255)
            ->requirePresence('recipient', 'create')
            ->notEmptyString('recipient');

        $validator
            ->scalar('subject')
            ->maxLength('subject', 255)
            ->allowEmptyString('subject');

        $validator
            ->scalar('message_body')
            ->requirePresence('message_body', 'create')
            ->notEmptyString('message_body');

        $validator
            ->integer('status')
            ->notEmptyString('status');

        $validator
            ->nonNegativeInteger('retry_count')
            ->notEmptyString('retry_count');

        return $validator;
    }

    //POCOR-9509: Queue an alert for async processing
    public function queueAlert(
        string $alertType,
        string $channel,
        string $recipient,
        string $messageBody,
        ?string $subject = null,
        ?array $payload = null,
        ?DateTimeInterface $availableAt = null
    ): bool {
        $entity = $this->newEntity([
            'alert_type' => $alertType,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'message_body' => $messageBody,
            'payload' => $payload ? json_encode($payload) : null,
            'status' => self::STATUS_PENDING, //POCOR-9509: use constant
            'retry_count' => 0,
            'available_at' => $availableAt ?? new DateTime(),
        ]);

        if ($this->save($entity)) {
            return true;
        }

        if ($entity->hasErrors()) {
            Log::error('Failed to queue alert', [
                'alert_type' => $alertType,
                'channel' => $channel,
                'errors' => $entity->getErrors(),
            ]);
        }

        return false;
    }

    //POCOR-9509: Queue an email alert
    public function queueEmail(
        string $recipient,
        string $subject,
        string $message,
        string $alertType = 'general',
        ?array $payload = null
    ): bool {
        return $this->queueAlert($alertType, 'email', $recipient, $message, $subject, $payload);
    }

    //POCOR-9509: Queue an SMS alert
    public function queueSms(
        string $recipient,
        string $message,
        string $alertType = 'general',
        ?array $payload = null
    ): bool {
        return $this->queueAlert($alertType, 'sms', $recipient, $message, null, $payload);
    }

    //POCOR-9509: Get queue statistics
    public function getQueueStats(): array
    {
        $connection = $this->getConnection();

        $pending = $connection->execute(
            'SELECT COUNT(*) as count FROM alert_queue WHERE status = ' . self::STATUS_PENDING
        )->fetch('assoc')['count'] ?? 0;

        $processing = $connection->execute(
            'SELECT COUNT(*) as count FROM alert_queue WHERE status = ' . self::STATUS_PROCESSING
        )->fetch('assoc')['count'] ?? 0;

        $failed = $connection->execute(
            'SELECT COUNT(*) as count FROM alert_queue WHERE status = ' . self::STATUS_FAILED
        )->fetch('assoc')['count'] ?? 0;

        return [
            'pending' => (int) $pending,
            'processing' => (int) $processing,
            'failed' => (int) $failed,
        ];
    }
}
