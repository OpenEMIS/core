<?php
namespace Configuration\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use ArrayObject;
use Cake\ORM\TableRegistry;
use App\Model\Traits\OptionsTrait;

class ConfigWebhooksTable extends ControllerActionTable
{
    use OptionsTrait;

    private $eventKeyOptions = [
        'logout' => 'Logout',
    ];

    public function initialize(array $config)
    {
        $this->table('webhooks');
        parent::initialize($config);
        $this->hasMany('WebhookEvents', ['className' => 'Webhook.WebhookEvents', 'dependent' => true, 'cascadeCallBack' => true, 'saveStrategy' => 'replace', 'foreignKey' => 'webhook_id', 'joinType' => 'INNER']);
        $this->addBehavior('Configuration.ConfigItems');

        foreach ($this->eventKeyOptions as $key => $value) {
            $this->eventKeyOptions[$key] = __($value);
        }
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ])
            ->requirePresence('url')
            ->add('triggered_event', 'ruleNotEmpty', [
                'rule' => function ($value, $context) {
                    if (empty($value)) {
                        return false;
                    } elseif (isset($value['_ids']) && empty($value['_ids'])) {
                        return false;
                    }
                    return true;
                }
            ])
            ;
        return $validator;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['WebhookEvents']);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['WebhookEvents']);
    }

    public function editOnInitialize(Event $event, Entity $entity)
    {
        $this->request->data[$this->alias()]['triggered_event']['_ids'] = [];
        foreach ($entity->webhook_events as $event) {
            $this->request->data[$this->alias()]['triggered_event']['_ids'][] = $event->event_key;
        }
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $supportedMethod = TableRegistry::get('Webhook.Webhooks')->supportedMethod;
        $this->fields['description']['visible']['index'] = false;
        $this->field('name');
        $this->field('url', ['type' => 'string']);
        $this->field('status', ['options' => $this->getSelectOptions('general.active')]);
        $this->field('method', ['options' => $supportedMethod]);
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('triggered_event', [
            'type' => 'chosenSelect',
            'options' => $this->eventKeyOptions,
            'before' => 'description',
            'attr' => ['required' => true]
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('triggered_event', [
            'before' => 'description'
        ]);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $data['webhook_events'] = [];
        if (is_array($data['triggered_event']['_ids'])) {
            foreach ($data['triggered_event']['_ids'] as $event) {
                $data['webhook_events'][] = ['event_key' => $event];
            }
        }
        $options['associated'] = [
            'WebhookEvents' => [
                'validate' => false
            ]
        ];
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('triggered_event');
        $this->setFieldOrder(['triggered_event', 'name', 'url', 'status', 'method']);
    }

    public function onGetTriggeredEvent(Event $event, Entity $entity)
    {
        $returnString = '';
        foreach ($entity->webhook_events as $event) {
            $returnString = $returnString . ', ' . __($this->eventKeyOptions[$event->event_key]);
        }
        return ltrim($returnString, ', ');
    }
}
