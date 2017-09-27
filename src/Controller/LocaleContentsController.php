<?php
namespace App\Controller;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;

use App\Controller\PageController;

class LocaleContentsController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->Page->disable(['add', 'delete']);
        $this->Page->setHeader('Translations');

        $this->loadModel('Locales');
        $this->loadModel('LocaleContents');
        $this->loadModel('LocaleContentTranslations');

        $this->Page->loadElementsFromTable($this->LocaleContents);
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $this->Page->get('en')->setLabel('English');
        $this->Page->addCrumb('Localization', ['plugin' => false, 'controller' => 'LocaleContents', 'action' => 'index']);
    }

    public function index()
    {
        $page = $this->Page;

        $localeOptions = $this->Locales->getList()->toArray();
        $page->addFilter('locale_id')
            ->setOptions($localeOptions)
        ;

        $queryString = $page->getQueryString();
        if (array_key_exists('locale_id', $queryString)) {
                $localeId = $queryString['locale_id'];
                $page->addNew($localeOptions[$localeId])->setDisplayFrom('locales.0._joinData.translation');
        } else {
            $firstLocale = key($localeOptions);
            $page->setQueryString('locale_id', $firstLocale);
            $page->addNew($localeOptions[$firstLocale])->setDisplayFrom('locales.0._joinData.translation');
        }

        parent::index();
    }

    public function edit($id)
    {
        $page = $this->Page;
        $request = $this->request;
        $model = $this->LocaleContents;

        $page->get('en')->setDisabled(true);
        $modelAlias = $model->alias();
        $localeNames = $this->Locales->find('allLocale');
        $counter = 0;

        parent::edit($id);

        $entity = $page->getVar('data');

        foreach ($localeNames as $key => $value) {
            $translation = '';
            if (array_key_exists($counter, $entity->locales)) {
                $translation = $entity->locales[$counter]->_joinData->translation;
            }

            $page->addNew($value['name'], [
                    'type' => 'string',
                    'length' => 250
                ])
                ->setValue($translation)
            ;

            $counter++;
        }

        if ($request->is(['post', 'put', 'patch'])) {
            $localeContentData = $request->data['LocaleContents'];
            $localeContentId = $localeContentData['id'];

            $localeData = $request->data['LocaleContents'];
            unset($localeData['id']); // remove the id

            foreach ($localeData as $key => $value) {
                $translationEntity = $this->LocaleContentTranslations->find()
                    ->contain(['Locales'])
                    ->where([
                        $this->LocaleContentTranslations->aliasField('locale_content_id') => $localeContentId,
                        'Locales.name' => $key,
                    ])
                    ->first();

                if ($translationEntity) {
                    // Update the translation
                    $translationEntity->translation = $value;
                } else {
                    // adding new translation
                    $translationEntity = $this->LocaleContentTranslations->newEntity();
                    $translationEntity['translation'] = $value;
                    $translationEntity['locale_content_id'] = $localeContentId;
                    $translationEntity['locale_id'] = $translationEntity->locale->id;
                }

                $this->LocaleContentTranslations->save($translationEntity);
            }
        }
    }

    public function view($id)
    {
        $page = $this->Page;
        $model = $this->LocaleContents;

        $localeNames = $this->Locales->find('allLocale');
        $modelAlias = $model->alias();
        $counter = 0;

        parent::view($id);

        $entity = $page->getVar('data');

        foreach ($localeNames as $key => $value) {
            $translation = '';
            if (array_key_exists($counter, $entity->locales)) {
                $translation = $entity->locales[$counter]->_joinData->translation;
            }

            $page->addNew($value['name'])
                ->setValue($translation);

            $counter++;
        }
    }
}
