<?php
namespace Import\Model\Behavior;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Behavior;
use Cake\ORM\ResultSet;
use Cake\I18n\Time;
use Cake\Utility\Inflector;
use ControllerAction\Model\Traits\EventTrait;
use Cake\I18n\I18n;

class ImportLinkBehavior extends Behavior
{
    protected $_defaultConfig = [
    ];

    public function initialize(array $config)
    {
        $importModel = $this->config('import_model');
        if (empty($importModel)) {
            $this->config('import_model', 'Import'.$this->_table->alias());
        };
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 1];

        if ($this->isCAv4()) {
            $events['ControllerAction.Model.index.afterAction'] = ['callable' => 'indexAfterActionImportv4'];
            $events['ControllerAction.Model.view.afterAction'] = ['callable' => 'viewAfterActionImportv4'];
        }

        return $events;
    }

    //using after action for ordering of toolbar buttons (because export also using afteraction)
    public function indexAfterActionImportv4(Event $event, Query $query, $data, ArrayObject $extra)
    {
        if ($this->_table->request->action != 'Surveys') { 
            $attr = $this->_table->getButtonAttr();
            $customButton = [];
            $customButton['url'] = $this->_table->url('index');
            $customButton['url']['action'] = $this->config('import_model');
            $customButton['url'][0] = 'add';
            $this->generateImportButton($extra['toolbarButtons'], $attr, $customButton);
        }
    }

    public function viewAfterActionImportv4(Event $event, Entity $entity, ArrayObject $extra)
    {
        if ($this->_table->request->action == 'Surveys') {
            $attr = $this->_table->getButtonAttr();
            $customButton = [];
            $customButton['url'] = $this->_table->url('view');
            $customButton['url']['action'] = 'Import'.$this->_table->alias();
            $this->generateImportButton($extra['toolbarButtons'], $attr, $customButton);
        }
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $customButton = [];
        switch ($action) {
            case 'index':
                if ($buttons['index']['url']['action']=='Surveys') {
                    break;
                }
                $customButton['url'] = $this->_table->ControllerAction->url('add');
                $customButton['url']['action'] = $this->config('import_model');

                $this->generateImportButton($toolbarButtons, $attr, $customButton);
                break;

            case 'view':
                if ($buttons['view']['url']['action']!='Surveys') {
                    break;
                }
                $customButton['url'] = $buttons['view']['url'];
                $customButton['url']['action'] = 'Import'.$this->_table->alias();

                $this->generateImportButton($toolbarButtons, $attr, $customButton);
                break;
        }
    }

    private function generateImportButton(ArrayObject $toolbarButtons, array $attr, array $customButton)
    {
        if (array_key_exists('_ext', $customButton['url'])) {
            unset($customButton['url']['_ext']);
        }
        if (array_key_exists('pass', $customButton['url'])) {
            unset($customButton['url']['pass']);
        }
        if (array_key_exists('paging', $customButton['url'])) {
            unset($customButton['url']['paging']);
        }
        if (array_key_exists('filter', $customButton['url'])) {
            unset($customButton['url']['filter']);
        }
        $customButton['url'][0] = 'add';

        $AccessControl = $this->_table->controller->AccessControl;
        $permission = $AccessControl->check($customButton['url']);
        if ($permission) {
            $customButton['type'] = 'button';
            $customButton['label'] = '<i class="fa kd-import"></i>';
            $customButton['attr'] = $attr;
            $customButton['attr']['title'] = __('Import');

            $toolbarButtons['import'] = $customButton;
        }
    }

    private function isCAv4()
    {
        return isset($this->_table->CAVersion) && $this->_table->CAVersion=='4.0';
    }
}
