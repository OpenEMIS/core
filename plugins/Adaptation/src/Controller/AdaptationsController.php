<?php
namespace Adaptation\Controller;

use App\Controller\PageController;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Entity;

class AdaptationsController extends PageController
{
    const APPNAME = 1;
    const LOGINBGIMAGE = 2;
    const LOGO = 3;
    const FAVICON = 4;
    const COLOUR = 5;
    const COPYRIGHTNOTICE = 6;
    const ABOUTCONTENT = 7;

    public function initialize()
    {
        parent::initialize();
        $this->Page->loadElementsFromTable($this->Adaptations);
        $this->Page->disable(['add', 'delete']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.Page.onRenderValue'] = 'onRenderValue';
        $events['Controller.Page.onRenderDefaultValue'] = 'onRenderDefaultValue';
        return $events;
    }

    public function onRenderValue(Event $event, Entity $entity, $key)
    {
        $id = $entity->id;
        if (!$entity->value) {
            return '';
        }
        switch ($id) {
            case self::ABOUTCONTENT:
                $str = strlen($entity->value) > 30 ? substr($entity->value, 0, 30).'...' : $entity->value;
                return trim($str);
                break;
            case self::COLOUR:
                return '<div style="float: left; width: 200px; height: 20px; margin: 5px; border: 1px solid rgba(0, 0, 0, .2); background-color: #'.$entity->value.'"></div>';
                break;
            default:
                return $entity->value;
                break;
        }
    }

    public function onRenderDefaultValue(Event $event, Entity $entity, $key)
    {
        switch ($entity->id) {
            case self::ABOUTCONTENT:
                $str = strlen($entity->default_value) > 30 ? substr($entity->default_value, 0, 30).'...' : $entity->default_value;
                return trim($str);
                break;
            case self::COLOUR:
                return '<div style="float: left; width: 200px; height: 20px; margin: 5px; border: 1px solid rgba(0, 0, 0, .2); background-color: #'.$entity->default_value.'"></div>';
                break;
            default:
                return $entity->default_value;
                break;
        }
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['content', 'default_content']);
        $page->addFilter('type')
            ->setOptions($this->systemConfigFilterOptions('Adaptations'));
        $key = array_search(__('Adaptations'), array_column($this->systemConfigFilterOptions(), 'text'));
        if ($key != $page->getQueryString('type')) {
            $this->redirect(['plugin' => 'Configuration', 'controller' => 'Configurations', 'action' => 'index', 'type' => $page->getQueryString('type')]);
        }
        $page = $this->Page;
        parent::index();
    }

    public function edit($id)
    {
        $page = $this->Page;
        $page->get('name')->setDisabled(true);
        $page->get('default_value')->setDisabled(true);
        $page->move('default_value')->after('name');
        $entityId = $page->decode($id)['id'];
        switch ($entityId) {
            case self::APPNAME:
            case self::COPYRIGHTNOTICE:
                $page->get('name')->setControlType('string');
                $page->get('default_value')->setControlType('string');
                $page->exclude(['content', 'default_content']);
                break;
            case self::ABOUTCONTENT:
            case self::COLOUR:
                $page->exclude(['content', 'default_content']);
                break;
            default:
                $page->get('content')
                    ->setLabel('Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'value');

                $page->get('default_content')
                    ->setLabel('Default Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'default_value');
                $page->exclude(['value', 'default_value']);
                break;
        }
        parent::edit($id);
    }

    public function view($id)
    {
        $page = $this->Page;
        $entityId = $page->decode($id)['id'];
        switch ($entityId) {
            case self::APPNAME:
            case self::COPYRIGHTNOTICE:
                $page->get('name')->setControlType('string');
                $page->get('default_value')->setControlType('string');
                $page->exclude(['content', 'default_content']);
                break;
            case self::ABOUTCONTENT:
            case self::COLOUR:
                $page->exclude(['content', 'default_content']);
                break;
            default:
                $page->get('content')
                    ->setLabel('Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'value');

                $page->get('default_content')
                    ->setLabel('Default Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'default_value');
                $page->exclude(['value', 'default_value']);
                break;
        }
        parent::view($id);
    }

    private function systemConfigFilterOptions($selectedModule = null)
    {
        $configItems = TableRegistry::get('Configuration.ConfigItems');
        $options = $configItems
            ->find('optionList', ['keyField' => 'id', 'valueField' => 'type', 'defaultOption' => false])
            ->group([$configItems->aliasField('type')])
            ->toArray();
        foreach ($options as $key => &$opt) {
            $opt['value'] = $key;
            if ($opt['text'] == __($selectedModule)) {
                $opt['selected'] = true;
            }
        }
        return $options;
    }
}
