<?php
namespace App\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

// this file is used solely for Preferences/Preferences
class PreferencesTable extends ControllerActionTable
{
    private $loginLanguages = [];
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);

        $Locales = TableRegistry::get('Locales');
        $this->loginLanguages = $Locales->find('list', [
                'keyField' => 'iso',
                'valueField' => 'name'
            ])
            ->toArray();

        $this->toggle('remove', false);
        $this->toggle('add', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
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

    public function onGetPreferredLanguage(Event $event, Entity $entity)
    {
        if (isset($this->loginLanguages[$entity->preferred_language])) {
            return $this->loginLanguages[$entity->preferred_language];
        } else {
            return $entity->preferred_language;
        }
    }

    public function onUpdateFieldPreferredLanguage(Event $event, array $attr, $action, Request $request)
    {
        $session = $this->request->session();
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

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // Remove the back toolbarButton
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        $session = $this->request->session();
        if (!$session->read('System.language_menu')) {
            if (array_key_exists('edit', $toolbarButtonsArray)) {
                unset($toolbarButtonsArray['edit']);
            }
        }

        if (array_key_exists('back', $toolbarButtonsArray)) {
            unset($toolbarButtonsArray['back']);
        }

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // remove the list toolbarButton
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        if (array_key_exists('list', $toolbarButtonsArray)) {
            unset($toolbarButtonsArray['list']);
        }

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        // To change the language of the UI
        $url = $this->url('view');
        $url['lang'] = $entity->preferred_language;
        return $this->controller->redirect($url);
    }
}
