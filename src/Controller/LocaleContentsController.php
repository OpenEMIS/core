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
        $this->Page->loadElementsFromTable($this->LocaleContents);
        $this->Page->disable(['add', 'delete']);

        $this->loadModel('Locales');
        $this->loadModel('LocaleContents');
        // $this->localeTable = TableRegistry::get('Locales');
        // $this->localeList = $this->localeTable->find('allLocale')->toArray();
        // $this->localeName = [];

        //data massaging 
        // foreach($this->localeList as $localekey => $localevalue)
        // {
            //extracting locale name
            //e.g. [zh] = Chinese
            // $this->localeName[$this->localeList[$localekey]['iso']] = $this->localeList[$localekey]['name'];
        // }

        // $this->localeName = $this->getLocaleList();
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Navigation->addCrumb('Localization', ['plugin' => false, 'controller' => 'LocaleContents', 'action' => 'index']);

        $page = $this->Page;
        $page->exclude(['editable', 'code']);
        // $page->get('en')->setLabel('English');
    }

    private function getLocaleList()
    {
        $locales = [];
        $result = $this->Locales->find('allLocale')->toArray();
        foreach($result as $key => $value) {
            //extracting locale name
            //e.g. [zh] = Chinese
            $locales[$value['iso']] = $value['name'];
        }
        return $locales;
    }

    public function index()
    {
        // $this->Page->loadElementsFromTable($this->LocaleContents);
        $this->Page->get('en')->setLabel('English');
        $this->Page->exclude(['editable', 'code']);
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
                    $entity = $model->patchEntity($localContentEntity, $data);

                    //saving data
                    $extra['result'] = $model->save($entity);

                } catch (Exception $ex) {
                    Log::write('error', $ex->getMessage());
                }
                $event = $this->dispatchEvent('Controller.Page.editAfterSave', [$entity, $extra], $this);
                if ($event->result instanceof Response) {
                    return $event->result;
                }

            $this->set('data', $entity);
            $this->render('Page.Page/edit');
        }
        else{

            $allTranslatedLocales = $this->LocaleContents->find('allTranslatedLocale')->toArray()[0];

            foreach($allTranslatedLocales as $key => $value) {
                if($key != 'id' && $key != 'en' && !strpos($key, '_')) {
                    $localContentEntity[$key] = $value;
                }
            }

            foreach($localeNames as $key => $value) {
                $page->addNew($key)->setDisplayFrom($key)->setLabel($value);
            }
            $page->get('en')->setDisabled(true);
            $this->set('data', $localContentEntity);
            $this->render('Page.Page/edit');
        }
    }

    public function view($id)
    {
        $page = $this->Page;

        $allTranslatedLocales = $this->LocaleContents->find('allTranslatedLocale')->toArray()[0];

        $localeNames = $this->getLocaleList();
        foreach($localeNames as $key => $value) {
            $page->addNew($key)->setDisplayFrom($key)->setLabel($value);
        }

        // parent::view($id);
        $this->set('data', $allTranslatedLocales);
        $this->render('Page.Page/view');
    }

    public function add()
    {
        $page = $this->Page;
        $localeNames = $this->getLocaleList();
        foreach($localeNames as $key => $value)
        {
            $page->addNew($key)->setLabel($value);
        }
        parent::add($id);
    }

}
