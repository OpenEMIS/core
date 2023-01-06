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
        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');

        parent::beforeFilter($event);

        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

        $page = $this->Page;

        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
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
}
