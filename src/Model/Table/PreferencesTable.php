<?php
namespace App\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;

// this file is used solely for Preferences/Preferences
class PreferencesTable extends ControllerActionTable
{
    private $loginLanguages = [];
    public function initialize(array $config): void               
    {
        $this->setTable('security_users');
        parent::initialize($config);

        $Locales = TableRegistry::getTableLocator()->get('Locales');
        $this->loginLanguages = $Locales->find('list', [
                'keyField' => 'iso',
                'valueField' => 'name'
            ])
            ->toArray();

        $this->toggle('remove', false);
        $this->toggle('add', false);
    }

    public function beforeAction(EventInterface $event)
    {
        $shownField = ['preferred_language'];

        foreach ($this->fields as $key => $field) {
            if (in_array($key, $shownField)) {
                $this->field($key);
            } else {
                $this->field($key, ['visible' => false]);
            }
        }
    }

    public function onGetPreferredLanguage(EventInterface $event, Entity $entity)
    {
        if (isset($this->loginLanguages[$entity->preferred_language])) {
            return $this->loginLanguages[$entity->preferred_language];
        } else {
            return $entity->preferred_language;
        }
    }

    public function onUpdateFieldPreferredLanguage(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $session = $this->request->getSession();
        if ($session->read('System.language_menu')) {
            $attr['options'] = $this->loginLanguages;
        } else {
            $loginId = $this->Auth->user('id');
            $entity = $this->get($loginId);
            $preferredLanguage = $this->loginLanguages[$entity->preferred_language];

            $attr['type'] = 'disabled';
            $attr['value'] = $preferredLanguage;
            $attr['attr']['value'] = $preferredLanguage;
        }
        $attr['select'] = false;

        return $attr;
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // Remove the back toolbarButton
        $toolbarButtonsArray = isset($extra['toolbarButtons']) ? $extra['toolbarButtons']->getArrayCopy() : [];
        $session = $this->request->getSession();
        if (!$session->read('System.language_menu')) {
            if (isset($toolbarButtonsArray['edit'])) {
                unset($toolbarButtonsArray['edit']);
            }
        }

        if (isset($toolbarButtonsArray['back'])) {
            unset($toolbarButtonsArray['back']);
        }

        isset($extra['toolbarButtons']) ? $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray) : [];
    }

    public function editAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        // remove the list toolbarButton
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        if (isset($toolbarButtonsArray['list'])) {
            unset($toolbarButtonsArray['list']);
        }

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        // To change the language of the UI
        $url = $this->url('view');
        $url['lang'] = $entity->preferred_language;
        return $this->controller->redirect($url);
    }
}
