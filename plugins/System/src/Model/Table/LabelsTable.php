<?php

namespace System\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\ORM\Entity;

class LabelsTable extends ControllerActionTable
{
    private $fieldsOrder = ['created', 'message'];
    public function initialize(array $config): void
    {
       parent::initialize($config);
       $this->toggle('view', true);
       $this->toggle('edit', true);
       $this->toggle('add', false);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', $header);
        $this->field('visible', ['visible' => false]);
        $this->field('message', ['visible' => false]);
        $this->field('module', ['visible' => false]);
        $this->field('created', ['visible' => false]);
        $this->field('field', ['visible' => false]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('created', ['visible' => false, 'sort' => true]);
        $this->field('message', ['sort' => true]);

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryParams = $this->request->getQuery();
        if (!isset($queryParams['sort'])) {
            $query->order(
                [$this->aliasField('created') => 'DESC',
                    $this->aliasField('modified') => 'DESC']);
        }

    }

    public function onGetFormButtons(Event $event, ArrayObject $buttons)
    {

    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setfieldOrder($this->fieldsOrder);
    }

    //POCOR-8146 Start
    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->allowEmpty('code')
            ->add('code', [
                    'ruleUnique' => [
                        'rule' => 'validateUnique',
                        'provider' => 'table',
                    ]
                ])
            ->requirePresence('module_name')
            ->requirePresence('field_name');
        return $validator;
    }
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //do not save empty strings
        if ($entity->code == "") {
            $entity->code = null;
        }

        if ($entity->name == "") {
            $entity->name = null;
        }
    }

    public function editBeforeAction(Event $event)
    {
        $this->field('module_name', ['type' => 'readonly']);
        $this->field('field_name', ['type' => 'readonly']);
    }
    //POCOR-8146 End
}
