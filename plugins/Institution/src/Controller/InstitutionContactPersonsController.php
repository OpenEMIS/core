<?php
namespace Institution\Controller;

use Cake\Event\Event;
use App\Controller\PageController;

class InstitutionContactPersonsController extends PageController
{

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.InstitutionContactPersons');
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
        $page->addCrumb('Contacts');

        // set header
        $page->setHeader(__('Contacts'));

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
}