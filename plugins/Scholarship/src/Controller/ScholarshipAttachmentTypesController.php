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
        $page->addCrumb('Scholarships', ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'index']);
        $page->addCrumb('Attachment');

        $scholarshipId = $page->getQueryString('scholarship_id');
        
        $this->setupTabElements(['scholarshipId' => $scholarshipId]);
        
        $page->get('scholarship_id')
            ->setControlType('hidden')
            ->setValue($scholarshipId);

        $page->get('is_mandatory')
            ->setLabel('Mandatory');
    }
    
    public function index()
    {       
        parent::index();
        $page = $this->Page;

        $page->exclude(['scholarship_id']);

        $page->move('type')->after('is_mandatory');
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

    public function setupTabElements($options)
    {   
        $page = $this->Page;
        $plugin = $this->plugin;
    
        $encodedScholarshipId = $page->encode(['id' => $options['scholarshipId']]);
        $queryString = $this->request->query['querystring'];

        $tabElements = [
            'Scholarships' => [
                'url' => ['plugin' => $this->plugin, 'controller' => 'Scholarships', 'action' => 'view', $encodedScholarshipId],
                'text' => __('Scholarships')
            ],
            'ScholarshipAttachmentTypes' => [
                'url' => ['plugin' => $this->plugin, 'controller' => 'ScholarshipAttachmentTypes', 'action' => 'index', 'querystring' => $queryString],
                'text' => __('Attachments')
            ],
        ];

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }

        $page->getTab('ScholarshipAttachmentTypes')->setActive('true');      
    }

    public function onRenderIsMandatory(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view', 'delete'])) {
            if($entity->is_mandatory == 1) {
                return "<i class='fa fa-check'></i>";
            } else {
                return "<i class='fa fa-close'></i>";
            }
        }
    }
    
}
