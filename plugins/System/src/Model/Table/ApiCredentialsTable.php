<?php

namespace System\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

class ApiCredentialsTable extends ControllerActionTable
{
    public function initialize(array $config): void
    {
          parent::initialize($config);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name', ['required' => true]);  //  POCOR-7312 V4
        $this->field('message', ['visible' => false]);
        $this->field('public_key', ['visible' => false]);
        $this->field('client_id', ['visible' => false]);  //  POCOR-7312 V4
        $this->field('created', ['after' => 'modified', 'visible' => ['add' => false, 'view' => true, 'edit' => false]]);
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', $header);
    }
    //  POCOR-7312 V4 start
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('name')
            ->notEmptyString('name', 'Name is required');

        return $validator;
    }

    /*public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('message', ['visible' => false]);
        $this->field('public_key', ['visible' => false]);
    }*/
    //  POCOR-7312 V4 end

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
        switch ($field) {
            case 'client_id':
                return __('Client Id');
            default:
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        //$this->setfieldOrder($this->fieldsOrder); //POCOR-7312 V4
    }
}
