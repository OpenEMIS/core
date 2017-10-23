<?php
namespace Institution\Controller;

use Cake\Event\Event;
use App\Controller\PageController;

class InstitutionTransportProvidersController extends PageController
{
    public function initialize()
    {
        parent::initialize();

        $this->Page->loadElementsFromTable($this->InstitutionTransportProviders);
    }

	public function beforeFilter(Event $event)
    {
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');

    	parent::beforeFilter($event);

        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

		$page = $this->Page;

        // set Breadcrumb
        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
        $page->addCrumb('Providers');

        // set header
        $page->setHeader($institutionName . ' - ' . __('Providers'));

        // to filter by institution_id
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
        $page->exclude(['comment', 'institution_id']);
    }
}
