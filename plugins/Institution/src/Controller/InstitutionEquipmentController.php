<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Model\Traits\OptionsTrait;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;

class InstitutionEquipmentController extends PageController
{
    use OptionsTrait;

    private $academicPeriodOptions = [];
    private $accessibilityOptions = [];

    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Institution.InstitutionEquipment');
        $this->loadModel('Institution.EquipmentTypes');
        $this->loadModel('AcademicPeriod.AcademicPeriods');
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderAccessibility'] = 'onRenderAccessibility';
        return $event;
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        $session = $this->request->session();
        parent::beforeFilter($event);

        $institutionId = $session->read('Institution.Institutions.id');
        $institutionName = $session->read('Institution.Institutions.name');
        $encodedInstitutionId = $this->paramsEncode(['id' => $institutionId]);

        $page->addCrumb('Institutions', ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Institutions', 'index']);
        $page->addCrumb($institutionName, ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'dashboard', 'institutionId' => $encodedInstitutionId, $encodedInstitutionId]);
        $page->addCrumb('Equipment');

        $page->setHeader($institutionName . ' - ' . __('Equipment'));

        $page->setQueryString('institution_id', $institutionId);

        $page->get('equipment_type_id')->setLabel('Type');
        $page->get('equipment_purpose_id')->setLabel('Purpose');
        $page->get('equipment_condition_id')->setLabel('Condition');

        // hide institution_id
        $page->get('institution_id')
            ->setControlType('hidden')
            ->setValue($institutionId);

        // get options
        $this->academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $this->accessibilityOptions = $this->getSelectOptions($this->InstitutionEquipment->aliasField('accessibility'));
    }

    public function index()
    {
        $page = $this->Page;
        parent::index();

        $page->exclude(['institution_id']);

        // filters
        $page->addFilter('academic_period_id')->setOptions($this->academicPeriodOptions);

        $equipmentTypes = $this->EquipmentTypes
            ->find('optionList', ['defaultOption' => false])
            ->find('order')
            ->toArray();
        $equipmentTypeOptions = ['' => __('All Types')] + $equipmentTypes;
        $page->addFilter('equipment_type_id')->setOptions($equipmentTypeOptions);

        $accessibilityOptions = ['' => __('All Accessibilities')] + $this->accessibilityOptions;
        $page->addFilter('accessibility')->setOptions($accessibilityOptions);
    }

    public function view($id)
    {
        $page = $this->Page;
        parent::view($id);
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

        $page->get('equipment_type_id')->setControlType('select');
        $page->get('equipment_purpose_id')->setControlType('select');
        $page->get('equipment_condition_id')->setControlType('select');

        $page->get('academic_period_id')
            ->setControlType('select')
            ->setOptions($this->academicPeriodOptions, false);

        $page->get('accessibility')
            ->setControlType('select')
            ->setOptions($this->accessibilityOptions);

        $page->move('academic_period_id')->first();
    }

    public function onRenderAccessibility(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view'])) {
            return $this->accessibilityOptions[$entity->accessibility];
        }
    }
}
