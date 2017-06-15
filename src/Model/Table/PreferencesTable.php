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

        $ConfigItemOptionsTable = TableRegistry::get('Configuration.ConfigItemOptions');
        $this->loginLanguages = $ConfigItemOptionsTable->find('list', [
                'keyField' => 'value',
                'valueField' => 'option'
            ])
            ->where([$ConfigItemOptionsTable->aliasField('option_type') => 'language'])
            ->toArray();

        $this->toggle('remove', false);
        $this->toggle('add', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('preferred_language');

        // Disabled all the field except preferred language
        $this->field('id', ['visible' => false]);
        $this->field('username', ['visible' => false]);
        $this->field('password', ['visible' => false]);
        $this->field('openemis_no', ['visible' => false]);
        $this->field('first_name', ['visible' => false]);
        $this->field('middle_name', ['visible' => false]);
        $this->field('third_name', ['visible' => false]);
        $this->field('last_name', ['visible' => false]);
        $this->field('preferred_name', ['visible' => false]);
        $this->field('email', ['visible' => false]);
        $this->field('address', ['visible' => false]);
        $this->field('postal_code', ['visible' => false]);
        $this->field('address_area_id', ['visible' => false]);
        $this->field('birthplace_area_id', ['visible' => false]);
        $this->field('gender_id', ['visible' => false]);
        $this->field('date_of_birth', ['visible' => false]);
        $this->field('date_of_death', ['visible' => false]);
        $this->field('nationality_id', ['visible' => false]);
        $this->field('identity_type_id', ['visible' => false]);
        $this->field('identity_number', ['visible' => false]);
        $this->field('external_reference', ['visible' => false]);
        $this->field('super_admin', ['visible' => false]);
        $this->field('status', ['visible' => false]);
        $this->field('last_login', ['visible' => false]);
        $this->field('photo_name', ['visible' => false]);
        $this->field('photo_content', ['visible' => false]);
        $this->field('is_student', ['visible' => false]);
        $this->field('is_staff', ['visible' => false]);
        $this->field('is_guardian', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);
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
            $attr['type'] = 'disabled';
            $entity = $attr['entity'];
            $attr['attr']['value'] = $this->loginLanguages[$entity->preferred_language];
        }
        $attr['select'] = false;

        return $attr;
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        // Remove the back toolbarButton
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

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
