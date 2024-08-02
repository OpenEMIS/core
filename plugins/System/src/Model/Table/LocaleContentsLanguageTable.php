<?php

namespace System\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\Utility\Inflector;

class LocaleContentsLanguageTable extends ControllerActionTable
{
    private $fieldsOrder = ['created', 'message'];
    public function initialize(array $config): void
    {
        $this->setTable('locale_contents');
       parent::initialize($config);
       $this->toggle('view', true);
       $this->toggle('edit', true);
       $this->toggle('delete', false);
       $this->toggle('remove', false);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', 'Translations');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // By default English has to be there
        $defaultLocale = 'en';

        // Get the localization option from localization component
        $localeOptions = $this->Localization->getOptions();

        if(array_key_exists($defaultLocale, $localeOptions)){
            unset($localeOptions[$defaultLocale]);
        }
        $this->controller->set(compact('localeOptions'));

        $selectedOption = $this->queryString('translations_id', $localeOptions);
        $this->controller->set('selectedOption', $selectedOption);

        $toolbarElements = [
            ['name' => 'System.controls', 'data' => [], 'options' => []]
        ];
        $this->controller->set('toolbarElements', $toolbarElements);

        $selected = 'ar';
        if(array_key_exists($selectedOption, $localeOptions)){
            $selected = $selectedOption;
        }

        $this->ControllerAction->setFieldOrder([
             $defaultLocale, $selected
        ]);
        $this->ControllerAction->setFieldVisible(['index'], [
            $defaultLocale, $selected
        ]);

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
}
