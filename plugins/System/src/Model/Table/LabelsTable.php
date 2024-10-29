<?php

namespace System\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\Utility\Inflector;
use Cake\ORM\Entity;
use Cake\Log\Log;
use Cake\Cache\Cache;

class LabelsTable extends ControllerActionTable
{
    private $fieldsOrder = ['created', 'message'];
    private $defaultConfig = 'labels';
    public function initialize(array $config): void
    {
       parent::initialize($config);
       $this->toggle('view', true);
       $this->toggle('edit', true);
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

    //POCOR-8679 start
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $keyFetch = $entity->module.'.'.$entity->field;
        $keyValue = self::concatenateLabel($entity);
        Log::debug(print_r([1, $keyFetch, $keyValue],true));
        Cache::write($keyFetch, $keyValue, $this->defaultConfig);
    }

    public function concatenateLabel($entity)
    {
        $keyFetch = $entity->module.'.'.$entity->field;
        $keyValue = (!is_null($entity->name) && ($entity->name != "")) ? $entity->name : $entity->field_name;

        if (!is_null($entity->code) && ($entity->code != "")) {
            $keyValue = ucfirst($entity->code).' '.ucfirst($keyValue); // POCOR-4095 Remove the bracket on the label code
        }

        return $keyValue;
    }

    public function getDefaultConfig()
    {
        return $this->defaultConfig;
    }
    //POCOR-8679 end

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setfieldOrder($this->fieldsOrder);
    }

}
