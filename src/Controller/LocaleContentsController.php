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
        foreach ($localeNames as $key => $value) {
            $page->addNew($key)->setAliasField("$modelAlias.locales.$counter._joinData.translation")->setLabel($value['name']);
            $page->addNew($key.'_id')->setAliasField("$modelAlias.locales.$counter.id")->setControlType('hidden')->setValue($value['id']);
            $counter++;
        }

        parent::edit($id);
    }

    public function view($id)
    {
        $page = $this->Page;
        $model = $this->LocaleContents;

        $localeNames = $this->Locales->find('allLocale');
        $modelAlias = $model->alias();
        $counter = 0;

        foreach ($localeNames as $key => $value) {
            $page->addNew($key)->setDisplayFrom("locales.$counter._joinData.translation")->setLabel($value['name']);

            $counter++;
        }

        parent::view($id);
    }
}
