<?php
namespace Institution\Controller;

use Cake\Event\Event;

use App\Controller\PageController;

class InstitutionHistoriesController extends PageController
{
	public function initialize()
    {
        parent::initialize();

        $this->loadModel('Institution.Institutions');
        $this->Page->loadElementsFromTable($this->InstitutionHistories);
        $this->Page->disable(['add', 'edit', 'view', 'delete']);
    }

    public function beforeFilter(Event $event)
    {
        $institutionId = $this->paramsDecode($this->request->params['pass'][1])['id'];
        $institutionName = $this->Institutions->get($institutionId)->name;

        parent::beforeFilter($event);

        $page = $this->Page;

        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

        // set breadcrumb
        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
        $page->addCrumb(__('History'));

        // set header
        $header = $page->getHeader();
        $page->setHeader($institutionName . ' - ' . __('History'));

        // set queryString
        $page->setQueryString('institution_id', $institutionId);

        // set field
        $page->exclude(['model_reference', 'field_type', 'operation', 'institution_id']);

        // set field order
        $page->move('model')->first();
        $page->move('field')->after('model');
        $page->move('old_value')->after('field');
        $page->move('new_value')->after('old_value');
    }

    public function index()
    {
        $page = $this->Page;

        // modified_by
        $page->addNew('modified_by');
        $page->get('modified_by')->setDisplayFrom('created_user.name');

        // modified_on
        $page->addNew('modified_on');
        $page->get('modified_on')->setDisplayFrom('created');

        // addtotoolbar addtoolbar


        parent::index();

    }
}
