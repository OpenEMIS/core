<?php
declare(strict_types=1);

namespace Alert\Model\Table;

//POCOR-9509: Add AlertsQueueTable for viewing async alert queue
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class AlertsQueueTable extends ControllerActionTable
{
    private array $statusTypes = [
        0 => 'Pending',
        1 => 'Processing',
        2 => 'Sent',
        -1 => 'Failed'
    ];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('alerts_queue');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        // Disable add/edit/delete - queue entries are managed by the system
        $this->toggle('add', false);
        $this->toggle('edit', false);
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
            'name' => 'Alert/queue_controls',
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
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra): void
    {
        $status = $this->request->getQuery('status');
        $channel = $this->request->getQuery('channel');
        $alertType = $this->request->getQuery('alert_type');

        if (!empty($status) && $status !== 'all') {
            $query->where(['status' => $status]);
        }
        if (!empty($channel) && $channel !== 'all') {
            $query->where(['channel' => $channel]);
        }
        if (!empty($alertType) && $alertType !== 'all') {
            $query->where(['alert_type' => $alertType]);
        }
    }

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
        return [
            'all' => __('All Statuses'),
            $this->statusTypes
        ];
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
}
