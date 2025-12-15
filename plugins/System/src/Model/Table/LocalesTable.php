<?php

namespace System\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\Utility\Inflector;
use Cake\Http\ServerRequest; // POCOR-9504
use App\Model\Traits\OptionsTrait; // POCOR-9504

class LocalesTable extends ControllerActionTable
{
    use OptionsTrait; // POCOR-9504
    private $fieldsOrder = ['iso','name','editable','created']; // POCOR-9504
    public function initialize(array $config): void
    {

       parent::initialize($config);
       $this->toggle('view', true);
       $this->toggle('edit', true);
       $this->toggle('delete', false);
       $this->toggle('remove', false);
       $this->belongsToMany('System.LocaleContentsLanguage', [
            'through' => 'LocaleContentTranslations',
            'foreignKey' => 'locale_id',
            'targetForeignKey' => 'locale_content_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

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
        $this->field('editable', ['type' => 'select']); // POCOR-9504
    }

    // POCOR-9504
    public function onUpdateFieldEditable(Event $event, array $attr, $action, ServerRequest $request)
    {
        $attr['options'] = $this->getSelectOptions('general.yesno');
        return $attr;
    }
    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('created', ['visible' => true, 'sort' => true]);
        $this->field('message', ['visible' => false, 'type' =>'hidden']); // POCOR-9504
        $this->field('direction', ['visible' => false, 'type' =>'hidden']); // POCOR-9504
        $this->field('editable', ['type' => 'select']); // POCOR-9504

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
        $this->field('direction', ['visible' => false, 'type' =>'hidden']); // POCOR-9504
        $this->setfieldOrder($this->fieldsOrder);
    }
}
