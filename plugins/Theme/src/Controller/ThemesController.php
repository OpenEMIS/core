<?php
namespace Theme\Controller;

use App\Controller\PageController;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Core\Configure;

class ThemesController extends PageController
{
    const APPNAME = 1;
    const LOGINBGIMAGE = 2;
    const LOGO = 3;
    const FAVICON = 4;
    const COLOUR = 5;
    const COPYRIGHTNOTICE = 6;

    public function initialize()
    {
        parent::initialize();
        $this->Page->loadElementsFromTable($this->Themes);
        $this->Page->disable(['add', 'delete']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.Page.onRenderValue'] = 'onRenderValue';
        $events['Controller.Page.onRenderDefaultValue'] = 'onRenderDefaultValue';
        $events['Controller.Page.onRenderName'] = 'onRenderName';
        return $events;
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Page->addCrumb(__('System Configurations'), ['plugin' => 'Configuration', 'controller' => 'Configurations', 'action' => 'index']);
    }

    private function darkenColour($rgb, $darker = 2)
    {
        $hash = (strpos($rgb, '#') !== false) ? '#' : '';
        $rgb = (strlen($rgb) == 7) ? str_replace('#', '', $rgb) : ((strlen($rgb) == 6) ? $rgb : false);
        if (strlen($rgb) != 6) {
            return $hash.'000000';
        }
        $darker = ($darker > 1) ? $darker : 1;

        list($R16,$G16,$B16) = str_split($rgb, 2);

        $R = sprintf("%02X", floor(hexdec($R16)/$darker));
        $G = sprintf("%02X", floor(hexdec($G16)/$darker));
        $B = sprintf("%02X", floor(hexdec($B16)/$darker));

        return $hash.$R.$G.$B;
    }

    public function onRenderValue(Event $event, Entity $entity, $key)
    {
        if ($this->request->param('action') != 'edit') {
            $id = $entity->id;
            if (!$entity->value) {
                return '';
            }
            switch ($id) {
                case self::COLOUR:
                    return
                        '<div style="float: left; width: 150px; height: 20px; margin: 5px; border: 1px solid rgba(0, 0, 0, .2); background-color: #'.$entity->value.';"></div>';
                    break;
                default:
                    return $entity->value;
                    break;
            }
        }
    }

    public function onRenderDefaultValue(Event $event, Entity $entity, $key)
    {
        if ($this->request->param('action') != 'edit') {
            switch ($entity->id) {
                case self::COLOUR:
                    return
                        '<div style="float: left; width: 150px; height: 20px; margin: 5px; border: 1px solid rgba(0, 0, 0, .2); background-color: #'.$entity->default_value.';"></div>';
                    break;
                default:
                    return $entity->default_value;
                    break;
            }
        }
    }

    public function onRenderName(Event $event, Entity $entity, $key)
    {
        if ($this->request->param('action') != 'edit') {
            return __($entity->name);
        }
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['content', 'default_content']);
        $page->addFilter('type')
            ->setOptions($this->systemConfigFilterOptions('Themes'));
        $textKeys = array_column($this->systemConfigFilterOptions(), 'value');
        $key = array_search(__('Themes'), array_column($this->systemConfigFilterOptions(), 'text'));
        if ($textKeys[$key] != $page->getQueryString('type')) {
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
        $page->move('default_content')->after('default_value');
        $entityId = $page->decode($id)['id'];
        switch ($entityId) {
            case self::APPNAME:
            case self::COPYRIGHTNOTICE:
                $page->get('name')->setControlType('string');
                $page->get('default_value')->setControlType('string');
                $page->exclude(['content', 'default_content']);
                break;
            case self::COLOUR:
                $page->exclude(['content', 'default_content']);
                break;
            case self::LOGINBGIMAGE:
                $width = '1620px';
                $height = '1080px';
                $photoMessage = __('Advisable photo dimension %width by %height');
                $photoMessage = str_replace('%width', $width, $photoMessage);
                $photoMessage = str_replace('%height', $height, $photoMessage);
                $page->get('content')
                    ->setLabel('Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('imageMessage', '* '.$photoMessage)
                    ->setAttributes('fileSizeLimit', 2)
                    ->setAttributes('supportedFileFormat', ['jpeg', 'jpg', 'gif', 'png'])
                    ->setAttributes('fileNameField', 'value');

                $page->get('default_content')
                    ->setLabel('Default Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'default_value')
                    ->setDisabled(true);
                $page->exclude(['value', 'default_value']);
                break;
            case self::FAVICON:
                $page->get('content')
                    ->setLabel('Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'value')
                    ->setAttributes('supportedFileFormat', ['ico']);

                $page->get('default_content')
                    ->setLabel('Default Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'default_value')
                    ->setDisabled(true);
                $page->exclude(['value', 'default_value']);
                break;
            case self::LOGO:
                $backgroundColour = $this->Themes->get(self::COLOUR);
                $backgroundColour = $backgroundColour->value ? $backgroundColour->value : $backgroundColour->default_value;
                $page->get('content')
                    ->setLabel('Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'value')
                    ->setAttributes('fileSizeLimit', 2)
                    ->setAttributes('supportedFileFormat', ['jpeg', 'jpg', 'gif', 'png'])
                    ->setAttributes('backgroundColor', '#'.$backgroundColour);

                $page->get('default_content')
                    ->setLabel('Default Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'default_value')
                    ->setAttributes('backgroundColor', '#'.$backgroundColour)
                    ->setDisabled(true);
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
            case self::COLOUR:
                $page->exclude(['content', 'default_content']);
                break;
            case self::LOGO:
                $backgroundColour = $this->Themes->get(self::COLOUR);
                $backgroundColour = $backgroundColour->value ? $backgroundColour->value : $backgroundColour->default_value;
                $width = '1620px';
                $height = '1080px';
                $photoMessage = __('Advisable photo dimension %width by %height');
                $photoMessage = str_replace('%width', $width, $photoMessage);
                $photoMessage = str_replace('%height', $height, $photoMessage);
                $page->get('content')
                    ->setLabel('Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('imageMessage', '* ' . $photoMessage)
                    ->setAttributes('fileNameField', 'value')
                    ->setAttributes('supportedFileFormat', ['svg'])
                    ->setAttributes('backgroundColor', '#'.$backgroundColour);

                $page->get('default_content')
                    ->setLabel('Default Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'default_value')
                    ->setAttributes('backgroundColor', '#'.$backgroundColour)
                    ->setDisabled(true);
                $page->exclude(['value', 'default_value']);
                break;
            case self::LOGINBGIMAGE:
                $page->get('content')
                    ->setLabel('Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'value');

                $page->get('default_content')
                    ->setLabel('Default Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'default_value')
                    ->setDisabled(true);
                $page->exclude(['value', 'default_value']);
                break;
            case self::FAVICON:
                $page->get('content')
                    ->setLabel('Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'value')
                    ->setAttributes('supportedFileFormat', ['ico']);

                $page->get('default_content')
                    ->setLabel('Default Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('fileNameField', 'default_value')
                    ->setDisabled(true);
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
            ->where([$configItems->aliasField('visible') => 1])
            ->toArray();
        $excludedPlugins = (array) Configure::read('School.excludedPlugins');
        foreach ($options as $key => &$opt) {
            $text = $opt['text'] != 'Authentication' ? $opt['text'] : 'Sso';
            if (in_array($text, $excludedPlugins)) {
                unset($options[$key]);
            } else {
                $opt['value'] = $key;
                if ($opt['text'] == __($selectedModule)) {
                    $opt['selected'] = true;
                }
            }
        }
        return $options;
    }
}
