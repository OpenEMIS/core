<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;

class InstitutionContactPersonsController extends PageController
{

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.InstitutionContactPersons');
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderPreferred'] = 'onRenderPreferred';

        return $event;
    }

    public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');

        parent::beforeFilter($event);

        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

        $page = $this->Page;

        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
        $page->addCrumb('Contact Persons');

        // set header
        $page->setHeader(__('Contact Persons'));

        $page->setQueryString('institution_id', $institutionId);

        // set institution_id
        $page->get('institution_id')
            ->setControlType('hidden') 
            ->setValue($institutionId);
    }
     
    public function index()
    {
        parent::index();
        $page = $this->Page;
        $page->exclude(['institution_id']);
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
        $this->Page->get('preferred')
            ->setControlType('select')
            ->setOptions([
                null => '-- Select --', '1' => __('Yes'), '0' => __('No')
        ]);
    }

    public function onRenderPreferred(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;
        if ($page->is(['index', 'view', 'delete'])) {
            ($entity->preferred) ? $return = __('Yes') : $return = __('No');
            return $return;
        }
    }
}