<?php
namespace App\Controller;

use ArrayObject;
use Cake\Event\Event;

use Page\Controller\PageController;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;

class LocaleContentsController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        // $this->Page->loadElementsFromTable($this->LocaleContents);
        $this->Page->disable(['add', 'delete']);
        $this->Page->setHeader('Translations');

        $this->loadModel('Locales');
        $this->loadModel('LocaleContents');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Navigation->addCrumb('Localization', ['plugin' => false, 'controller' => 'LocaleContents', 'action' => 'index']);
    }

    private function getLocaleList()
    {
        $locales = [];
        $result = $this->Locales->find('allLocale')->toArray();
        foreach ($result as $key => $value) {
            //extracting locale name
            //e.g. [zh] = Chinese

            $locales[$value['iso']] = [
                'name' => $value['name'],
                'id' => $value['id']
            ];
        }
        return $locales;
    }

    public function index()
    {
        $this->Page->loadElementsFromTable($this->LocaleContents);
        $page = $this->Page;

        $page->get('en')->setLabel('English');

        $localeOptions = $this->Locales->getList()->toArray();
        $page->addFilter('locale_id')
            ->setOptions($localeOptions);
        ;

        $queryString = $page->getQueryString();
        if (array_key_exists('locale_id', $queryString)) {
            foreach ($localeOptions as $key => $value) {
                if ($key == $queryString['locale_id']) {
                    $page->addNew($value)->setDisplayFrom('_matchingData.LocaleContentTranslations.translation');
                }
            }
        } else {
            $firstLocale = key($localeOptions);
            $page->setQueryString('locale_id', $firstLocale);
            $page->addNew($localeOptions[$firstLocale])->setDisplayFrom('_matchingData.LocaleContentTranslations.translation');
        }

        parent::index();
    }

    public function edit($id)
    {
        $page = $this->Page;
        $page->loadElementsFromTable($this->LocaleContents);
        $request = $this->request;
        $model = $this->LocaleContents;

        $page->get('en')->setLabel('English')->setDisabled(true);
        $modelAlias = $model->alias();
        $localeNames = $this->getLocaleList();
        $counter = 0;
        foreach ($localeNames as $key => $value) {
            $page->addNew("$modelAlias.locales.$counter._joinData.translation")->setLabel($value['name']);
            $page->addNew("$modelAlias.locales.$counter.id")->setControlType('hidden')->setValue($value['id']);
            $counter++;
        }

        parent::edit($id);
    }

    public function view($id)
    {
        $page = $this->Page;
        $page->loadElementsFromTable($this->LocaleContents);
        $model = $this->LocaleContents;

        $page->get('en')->setLabel('English');

        $localeNames = $this->getLocaleList();
        $modelAlias = $model->alias();
        $counter = 0;

        foreach ($localeNames as $key => $value) {
            $page->addNew("locales.$counter._joinData.translation")->setLabel($value['name']);

            $counter++;
        }

        parent::view($id);
    }
}
