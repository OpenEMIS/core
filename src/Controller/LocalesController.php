<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;

use App\Controller\PageController;

class LocalesController extends PageController
{
    private $officialLanguages = ['ar', 'zh', 'en', 'fr', 'ru', 'es'];

    public function initialize()
    {
        parent::initialize();
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Controller.Page.getEntityDisabledActions'] = 'getEntityDisabledActions';

        return $events;
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);
        $this->Page->addCrumb('Localization', ['plugin' => false, 'controller' => 'Locales', 'action' => 'index']);

        $this->Page->get('direction')
            ->setControlType('select')
            ->setOptions([
                '' => '-- Select --', 'ltr' => __('Left to Right'), 'rtl' => __('Right to Left')
            ]);

        $this->Page->exclude(['editable']);
    }

    public function getEntityDisabledActions(Event $event, Entity $entity)
    {
        if (in_array($entity->iso, $this->officialLanguages)) {
            return ['delete'];
        }
    }

    public function index()
    {
        $page = $this->Page;
        $page->exclude(['iso', 'full_iso']);
        parent::index();
    }

    public function add()
    {
        parent::add();
        $this->addEdit();
    }

    public function edit($id)
    {
        parent::edit($id);
        $this->addEdit();
    }

    private function addEdit()
    {
        $page = $this->Page;

        $page->get('iso')
            ->setLabel('ISO');
    }

    public function view($id)
    {
        parent::view($id);
        $page = $this->Page;
        $entity = $page->getData();

        if (in_array($entity->iso, $this->officialLanguages)) {
            $page->disable(['delete']);
        }
        $page->get('iso')
            ->setLabel('ISO');
    }
}
