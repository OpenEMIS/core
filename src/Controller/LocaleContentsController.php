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
            $locales[$value['iso']] = $value['name'];
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
        if(array_key_exists('locale_id', $queryString)) {
            foreach($localeOptions as $key => $value) {
                if($key == $queryString['locale_id']) {
                    $page->addNew($value)->setDisplayFrom('_matchingData.LocaleContentTranslations.translation');
                }
            }
        } else {
            $firstLocale = key($localeOptions);
            $page->setQueryString('locale_id', $firstLocale);
            $page->addNew($localeOptions[$firstLocale])->setDisplayFrom('_matchingData.LocaleContentTranslations.translation');
        }

        
        // $page->debug(true);
        parent::index();
    }

    public function edit($id)
    {
        $page = $this->Page;
        $request = $this->request;
        $extra = new ArrayObject();
        $localeNames = $this->getLocaleList();

        $model = $this->LocaleContents;

        $primaryKeyValue = json_decode($page->hexToStr($id), true); // locale content id
        $localContentEntity = $model->get($primaryKeyValue);

        if ($request->is(['post', 'put'])) {
            try {
                //retrieving data
                $data = $request->data;
                $data['id'] = $primaryKeyValue;
                $entity = $model->patchEntity($localContentEntity, $data, []);
                //saving data
                $extra['result'] = $model->save($entity);
            } catch (Exception $ex) {
                Log::write('error', $ex->getMessage());
            }
            $event = $this->dispatchEvent('Controller.Page.editAfterSave', [$entity, $extra], $this);
            if ($event->result instanceof Response) {
                return $event->result;
            }
            $page->attachPrimaryKey($model, $entity);
            $this->set('data', $entity);
            $this->render('Page.Page/edit');
        } else {
            $allTranslatedLocales = $this->LocaleContents->find('allTranslatedLocale')->toArray()[0];

            foreach ($allTranslatedLocales as $key => $value) {
                if ($key != 'id' && $key != 'en' && !strpos($key, '_')) {
                    $localContentEntity[$key] = $value;
                }
            }
            $page->addNew('en')->setDisplayFrom('en')->setLabel('English');
            foreach ($localeNames as $key => $value) {
                $page->addNew($key)->setDisplayFrom($key)->setLabel($value);
            }
            $page->attachPrimaryKey($model, $entity);
            $page->get('en')->setDisabled(true);
            $this->set('data', $localContentEntity);
            $this->render('Page.Page/edit');
        }
    }

    public function view($id)
    {
        $page = $this->Page;
        $page->loadElementsFromTable($this->LocaleContents);
        $page->setAutoRender(false);

        $page->get('en')->setLabel('English');

        $localeNames = $this->getLocaleList();
        foreach ($localeNames as $key => $value) {
            $page->addNew($key)->setDisplayFrom($key)->setLabel($value);
        }

        parent::view($id);

        $data = $page->getData();

        $translations = $data->locale_content_translations;
        foreach ($translations as $translation) {
            $lang = $translation->locale->iso;
            $data->$lang = $translation->translation;
        }

        $this->set('data', $data);
        $this->render('Page.Page/view');
    }
}
