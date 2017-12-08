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

    private function checkIfTooLight($hex)
    {
        //break up the color in its RGB components
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        //do simple weighted avarage
        //
        //(This might be overly simplistic as different colors are perceived
        // differently. That is a green of 128 might be brighter than a red of 128.
        // But as long as it's just about picking a white or black text color...)
        if ($r + $g + $b > 350) {
            return '#000000';
            //bright color, use dark font
        } else {
            return '#FFFFFF';
            //dark color, use bright font
        }
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
                        '<div style="float: left; width: 150px; height: 20px; margin: 5px; border: 1px solid rgba(0, 0, 0, .2); background-color: #'.$entity->value.'; text-align: center; color: '.$this->checkIfTooLight($entity->value).'">'.__('Primary Colour').'</div>'.
                        '<div style="float: left; width: 150px; height: 20px; margin: 5px; border: 1px solid rgba(0, 0, 0, .2); background-color: #'. $this->darkenColour($entity->value) .';text-align: center; color: '.$this->checkIfTooLight($this->darkenColour($entity->value)).'">'.__('Secondary Colour').'</div>'
                    ;
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
                        '<div style="float: left; width: 150px; height: 20px; margin: 5px; border: 1px solid rgba(0, 0, 0, .2); background-color: #'.$entity->default_value.'; text-align: center; color: '.$this->checkIfTooLight($entity->default_value).'">'.__('Primary Colour').'</div>'.
                        '<div style="float: left; width: 150px; height: 20px; margin: 5px; border: 1px solid rgba(0, 0, 0, .2); background-color: #'. $this->darkenColour($entity->default_value) .';text-align: center; color: '.$this->checkIfTooLight($this->darkenColour($entity->default_value)).'">'.__('Secondary Colour').'</div>'
                        ;
                    break;
                default:
                    return $entity->default_value;
                    break;
            }
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
                $page->get('content')
                    ->setLabel('Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('imageMessage', '* Advisable photo dimension 1620px by 1080px')
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
                $backgroundColour = $this->Adaptations->get(self::COLOUR);
                $backgroundColour = $backgroundColour->value ? $backgroundColour->value : $backgroundColour->default_value;
                $page->get('content')
                    ->setLabel('Content')
                    ->setAttributes('type', 'image')
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
                $backgroundColour = $this->Adaptations->get(self::COLOUR);
                $backgroundColour = $backgroundColour->value ? $backgroundColour->value : $backgroundColour->default_value;
                $page->get('content')
                    ->setLabel('Content')
                    ->setAttributes('type', 'image')
                    ->setAttributes('imageMessage', '* Advisable photo dimension 1620px by 1080px')
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
