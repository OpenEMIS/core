<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;

use App\Controller\PageController;
use App\Model\Traits\OptionsTrait;

class InstitutionContactPersonsController extends PageController
{
    use OptionsTrait;

    private $preferredOptions = [];

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Institution.InstitutionContactPersons');

        $this->preferredOptions = $this->getSelectOptions('general.yesno');
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
        $institutionId = $this->getInstitutionID();
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);
        $institutionName = $session->read('Institution.Institutions.name');

        parent::beforeFilter($event);

        $page = $this->Page;

        // set Breadcrumb
        $page->addCrumb('Institutions', [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Institutions',
            'index']);
        $page->addCrumb($institutionName, [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'dashboard',
            'institutionId' => $encodedInstitutionId,
            $encodedInstitutionId]);
        $page->addCrumb('Contacts (People)');

        // set header
        $page->setHeader($institutionName . ' - ' . __('Contacts (People)'));

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
        $page->exclude(['mobile_number', 'fax', 'email', 'institution_id']);
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
            ->setOptions($this->preferredOptions);
    }

    public function onRenderPreferred(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;
        if ($page->is(['index', 'view', 'delete'])) {
            $value = $this->preferredOptions[$entity->preferred];

            return $value;
        }
    }

    private function getInstitutionID()
    {
        $session = $this->request->session();
        $insitutionIDFromSession = $session->read('Institution.Institutions.id');
        $encodedInstitutionIDFromSession = $this->paramsEncode(['id' => $insitutionIDFromSession]);
        $encodedInstitutionID = isset($this->request->params['institutionId']) ?
            $this->request->params['institutionId'] :
            $encodedInstitutionIDFromSession;
        try {
            $institutionID = $this->paramsDecode($encodedInstitutionID)['id'];
        } catch (\Exception $exception) {
            $institutionID = $insitutionIDFromSession;
        }
        return $institutionID;
    }
}
