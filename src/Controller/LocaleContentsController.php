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


        // $page->debug(true);
        parent::index();
    }

    public function edit($id)
    {
        $page = $this->Page;
        $page->loadElementsFromTable($this->LocaleContents);
        // $page->setAutoRender(false);
        $request = $this->request;
        $model = $this->LocaleContents;
        // $extra = new ArrayObject();

        $page->get('en')->setLabel('English')->setDisabled(true);
        $modelAlias = $model->alias();
        $localeNames = $this->getLocaleList();
        $counter = 0;
        foreach ($localeNames as $key => $value) {
            // pr($key);
            $page->addNew("$modelAlias.locales.$counter._joinData.translation")->setLabel($value['name']);
            $page->addNew("$modelAlias.locales.$counter.id")->setControlType('hidden')->setValue($value['id']);
            $counter++;
        }

        parent::edit($id);

        // $data = $page->getData();
        // pr($data);die;
        // $page->debug(true);
        // if ($request->is(['post', 'put'])) {
            // pr($request);die;
            // try {
            //     // $locales = $data->locales;
            //     // pr($locales);die;
            //     $data = $model->patchEntity($data, $locales, []);
            //     $extra['result'] = $model->save($entity);
            // } catch (Exception $ex) {
            //     Log::write('error', $ex->getMessage());
            // }
        // } 
        // else {
        //     // pr($data);die;
        //     $locales = $data->locales;
        //     foreach ($locales as $locale) {
        //         //for setDisplayFrom to work
        //         $lang = $locale->iso;
        //         $data->$lang = $locale->_joinData->translation;

        //         // updating existing data entity
        //         foreach ($localeNames as $key => $value) {
        //             if ($key == $lang) {
        //                 $locale->_joinData->translation = $data->$lang;
        //             }
        //         }
        //     }
        // }
        // pr($data);die;
        // $this->set('data', $data);
        // $this->render('Page.Page/edit');


        
        // $model = $this->LocaleContents;

        // $primaryKeyValue = json_decode($page->hexToStr($id), true); // locale content id
        // $localContentEntity = $model->get($primaryKeyValue);

        // if ($request->is(['post', 'put'])) {
        //     try {
        //         //retrieving data
        //         $data = $request->data;
        //         $data['id'] = $primaryKeyValue;
        //         $entity = $model->patchEntity($localContentEntity, $data, []);
        //         //saving data
        //         $extra['result'] = $model->save($entity);
        //     } catch (Exception $ex) {
        //         Log::write('error', $ex->getMessage());
        //     }
        //     $event = $this->dispatchEvent('Controller.Page.editAfterSave', [$entity, $extra], $this);
        //     if ($event->result instanceof Response) {
        //         return $event->result;
        //     }
        //     $page->attachPrimaryKey($model, $entity);
        //     $this->set('data', $entity);
        //     $this->render('Page.Page/edit');
        // } else {
        //     $allTranslatedLocales = $this->LocaleContents->find('allTranslatedLocale')->toArray()[0];

        //     foreach ($allTranslatedLocales as $key => $value) {
        //         if ($key != 'id' && $key != 'en' && !strpos($key, '_')) {
        //             $localContentEntity[$key] = $value;
        //         }
        //     }
        //     $page->addNew('en')->setDisplayFrom('en')->setLabel('English');
        //     foreach ($localeNames as $key => $value) {
        //         $page->addNew($key)->setDisplayFrom($key)->setLabel($value);
        //     }
        //     $page->attachPrimaryKey($model, $entity);
        //     $page->get('en')->setDisabled(true);
        //     $this->set('data', $localContentEntity);
        //     $this->render('Page.Page/edit');
        // }
    }

    public function view($id)
    {
        $page = $this->Page;
        $page->loadElementsFromTable($this->LocaleContents);
        // $page->setAutoRender(false);
        $model = $this->LocaleContents;

        $page->get('en')->setLabel('English');

        $localeNames = $this->getLocaleList();
        $modelAlias = $model->alias();
        $counter = 0;
        foreach ($localeNames as $key => $value) {
            pr($key);
            // $page->addNew($key)->setDisplayFrom($key)->setLabel($value);
            $page->addNew("data.locales.$counter.translation")->setLabel($value['name']);
            $page->addNew("data.locales.$counter.id")->setControlType('hidden')->setValue($value['id']);
            $counter++;
        }

        parent::view($id);
// 
        // $data = $page->getData();
        // // pr($data);

        // $locales = $data->locales;
        // foreach ($locales as $locale) {
        //     $lang = $locale->iso;
        //     $data->$lang = $locale->_joinData->translation;
        // }
        // pr($data);
        // die;
        // $this->set('data', $data);
        // $this->render('Page.Page/view');
    }
}
