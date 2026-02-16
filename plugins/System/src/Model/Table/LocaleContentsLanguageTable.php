<?php

namespace System\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\Utility\Inflector;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet; // POCOR-9503 start

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
       $this->belongsToMany('System.Locales', [
            'through' => 'LocaleContentTranslations',
            'foreignKey' => 'locale_content_id',
            'targetForeignKey' => 'locale_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->belongsToMany('LocaleContentTranslations', [
            'targetForeignKey' => 'locale_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', 'Translations');
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
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
        $extra['elements']['controls'] = ['name' => 'System.controls', 'data' => [], 'options' => [], 'order' => 1]; //POCOR-8479
        $selected = 'ar';
        if(array_key_exists($selectedOption, $localeOptions)){
            $selected = $selectedOption;
        }
        //POCOR-8479
        $this->field('en', ['visible' => true, 'sort' => true]);
        $this->field($selected, ['visible' => true]);
        $this->setFieldOrder(['en', $selected]);
    }

    //POCOR-8479 Start
    // Add new Laugauge so need to create this function for display in Listing
    public function onGetAr(EventInterface $event,$entity)
    {
        $localesId = $this->localesData('ar');
		return $this->translation($entity, $localesId);
	}

    public function onGetZh(EventInterface $event,$entity)
    {
        $localesId = $this->localesData('zh');
		return $this->translation($entity, $localesId);
	}

    public function onGetFr(EventInterface $event,$entity)
    {
        $localesId = $this->localesData('fr');
		return $this->translation($entity, $localesId);
	}

    public function onGetRu(EventInterface $event,$entity)
    {
        $localesId = $this->localesData('ru');
		return $this->translation($entity, $localesId);
	}

    public function onGetEs(EventInterface $event,$entity)
    {
        $localesId = $this->localesData('es');
		return $this->translation($entity, $localesId);
	}
    //POCOR-8479 End

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        $localeOptions = $this->Localization->getOptions(); //POCOR-8479
        $isoLocaleOption = array_keys($localeOptions);
        if(in_array($field, $isoLocaleOption)) {
            $Locale = TableRegistry::getTableLocator()->get('System.Locales')
                ->find()
                ->select(['name'])
                ->where(['iso' => $field])
                ->first();

            return $Locale ? $Locale->name : '';
        }
        return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
    }

    public function translation($entity, $LocalesId) //POCOR-8479
    {
        $localeContentTranslations = TableRegistry::getTableLocator()->get('LocaleContentTranslations');
        $result = $localeContentTranslations
            ->find()
            ->select(['translation'])
            ->where(['locale_content_id' => $entity->id])
            ->where(['locale_id' => $LocalesId])
            ->first();
		return $result ? $result->translation : '';
    }

    public function onGetFormButtons(EventInterface $event, ArrayObject $buttons)
    {

    }

    // // POCOR-9503 start
    public function indexAfterAction(EventInterface $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $localeOptions = $this->Localization->getOptions();
        $selectedOption = $this->queryString('translations_id', $localeOptions);
        $locale = TableRegistry::getTableLocator()->get('System.Locales')
            ->find()
            ->select(['iso', 'name','id'])
            ->where(['iso' => $selectedOption])
            ->first();

        foreach ($data as $entity) {

            $localeContentId = $entity->id;
            if ($locale->iso != 'en') {
                $localeContentTranslationsTable = TableRegistry::getTableLocator()->get('LocaleContentTranslations');
                $translationsData = $localeContentTranslationsTable->find()
                    ->where(['locale_content_id' => $localeContentId, 'locale_id' => $locale->id])
                    ->first();
                $entity->{$selectedOption} = $translationsData->translation; // POCOR-9503 end

            }

        }

    }
    //POCOR-8479 Start
    public function afterAction(EventInterface $event, ArrayObject $extra)
    {

        if($this->action == 'edit'
            || $this->action == 'view') {
            $locales = TableRegistry::getTableLocator()->get('System.Locales')
            ->find()
            ->select(['iso', 'name','id'])
            ->toArray();
            $localeContentId = $this->paramsDecode($this->request->getParam('pass')[1])['id'];
            foreach($locales as $locale) {
                $fieldName = $locale->iso;
                if($locale->iso != 'en') {
                    $localeContentTranslationsTable = TableRegistry::getTableLocator()->get('LocaleContentTranslations');
                    $translationsData = $localeContentTranslationsTable->find()
                    ->where(['locale_content_id' => $localeContentId, 'locale_id' => $locale->id])
                    ->first();
                    $this->field($fieldName, ['visible' => true, // POCOR-9503 start
                        'attr' => ['value'=> $translationsData->translation],
                        'value' => $translationsData->translation]); // POCOR-9503 end
                    $this->setFieldOrder(['en', $fieldName]);
                } else if($locale->iso == 'en') {
                    $this->field($fieldName, ['type' => 'readOnly']);
                }
            }
        }

    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if (!$entity->isNew()) {
            $localeOptions = $this->Localization->getOptions();
            $isoLocaleOption = array_keys($localeOptions);
            foreach ($isoLocaleOption as $iso) {
                $this->saveLocaleContentTranslationsTable($entity, $iso);
            }
        }
    }

    public function localesData($iso)
    {
        $Locales = TableRegistry::getTableLocator()->get('System.Locales')
                    ->find()
                    ->select(['id'])
                    ->where(['iso' => $iso])
                    ->first();
        $LocalesId = $Locales->id;
		return $LocalesId;
	}

    public function saveLocaleContentTranslationsTable($entity, $iso)
    {
        $localesId = $this->localesData($iso);
        $localeContentTranslationsTable = TableRegistry::getTableLocator()->get('LocaleContentTranslations');
        $translationsData = $localeContentTranslationsTable->find()
            ->where(['locale_content_id' => $entity->id, 'locale_id' => $localesId])
            ->first();
            if ($translationsData === null) {
                $translationsData = $localeContentTranslationsTable->newEmptyEntity();
                $translationsData->locale_content_id = $entity->id;
                $translationsData->locale_id = $localesId;
                $translationsData->translation = $entity->$iso;
            } else {
                $translationsData->translation = $entity->$iso;
            }

        $localeContentTranslationsTable->save($translationsData);
    }
    //POCOR-8479 End
}
