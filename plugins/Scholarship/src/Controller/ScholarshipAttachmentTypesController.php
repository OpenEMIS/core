<?php
namespace Scholarship\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;
use App\Model\Traits\OptionsTrait;

class ScholarshipAttachmentTypesController extends PageController
{
    use OptionsTrait;

    private $mandatoryOptions = [];

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Scholarship.Scholarships');
        $this->loadModel('Scholarship.AttachmentTypes');
        $this->Page->loadElementsFromTable($this->AttachmentTypes);
        $this->mandatoryOptions = $this->getSelectOptions('general.yesno');
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderIsMandatory'] = 'onRenderIsMandatory';
        return $event;
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        parent::beforeFilter($event);

        $queryString = $this->request->query('queryString');
        $scholarshipId = $this->paramsDecode($queryString)['id'];
        $scholarshipName = $this->Scholarships->get($scholarshipId)->name;

        $page->setQueryString('scholarship_id', $scholarshipId);

        $page->setHeader($scholarshipName . ' - ' . __('Attachments'));

        $page->addCrumb(__('Scholarships'), ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Scholarships', 'index']);
        $page->addCrumb($scholarshipName);
        $page->addCrumb(__('Attachments'));

        $page->get('scholarship_id')
            ->setControlType('hidden')
            ->setValue($scholarshipId);

        $page->get('is_mandatory')
            ->setLabel('Mandatory');

        $page->get('name')
            ->setLabel('Type');

        $this->setupTabElements();
    }

    public function index()
    {
        parent::index();
        $page = $this->Page;

        $page->exclude(['scholarship_id']);

        $page->move('name')->after('is_mandatory');
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
        $this->Page->get('is_mandatory')
            ->setControlType('select')
            ->setOptions($this->mandatoryOptions);
    }

    public function setupTabElements()
    {   
        $page = $this->Page;
        $plugin = $this->plugin;
    
        $queryString = $this->request->query['queryString'];
        $scholarshipTabElements = [
            'Scholarships' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Scholarships', 'view', $queryString, 'queryString' => $queryString],
                'text' => __('Overview')
            ],
            'Attachments' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipAttachmentTypes', 'action' => 'index', 'queryString' => $queryString],
                'text' => __('Attachments')
            ]
        ];


        foreach ($scholarshipTabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }

        $page->getTab('Attachments')->setActive('true');      
    }

    public function onRenderIsMandatory(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view'])) {
            return $entity->is_mandatory ? "<i class='fa fa-check'></i>" : "<i class='fa fa-close'></i>";
        } elseif ($page->is(['delete'])) {
            return $this->mandatoryOptions[$entity->is_mandatory];
        }
    }
}
