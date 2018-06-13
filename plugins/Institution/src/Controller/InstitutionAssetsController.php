<?php
namespace Institution\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Model\Traits\OptionsTrait;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;

class InstitutionAssetsController extends PageController
{
    use OptionsTrait;

    private $academicPeriodOptions = [];
    private $accessibilityOptions = [];

    public function initialize()
    {
        parent::initialize();

        $this->loadModel('Institution.InstitutionAssets');
        $this->loadModel('Institution.AssetTypes');
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
        $page->addCrumb('Assets');

        $page->setHeader($institutionName . ' - ' . __('Assets'));

        $page->setQueryString('institution_id', $institutionId);

        $page->get('asset_status_id')->setLabel('Status');
        $page->get('asset_type_id')->setLabel('Type');
        $page->get('asset_purpose_id')->setLabel('Purpose');
        $page->get('asset_condition_id')->setLabel('Condition');

        $page->move('accessibility')->after('asset_condition_id');
        $page->move('asset_status_id')->after('accessibility');

        $page->exclude(['id']);

        // hide institution_id
        $page->get('institution_id')
            ->setControlType('hidden')
            ->setValue($institutionId);

        // get options
        $this->academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $this->accessibilityOptions = $this->getSelectOptions($this->InstitutionAssets->aliasField('accessibility'));
    }

    public function index()
    {
        $page = $this->Page;

        // academic_period_id filter
        $page->addFilter('academic_period_id')->setOptions($this->academicPeriodOptions);

        $academicPeriodId = !is_null($page->getQueryString('academic_period_id')) ? $page->getQueryString('academic_period_id') : $this->AcademicPeriods->getCurrent();
        $page->setQueryString('academic_period_id', $academicPeriodId);

        // asset_type_id filter
        $assetTypes = $this->AssetTypes
            ->find('optionList', ['defaultOption' => false])
            ->find('visible')
            ->find('order')
            ->toArray();
        $assetTypeOptions = ['' => '-- ' . __('Select Type') . ' --'] + $assetTypes;
        $page->addFilter('asset_type_id')->setOptions($assetTypeOptions);

        // accessibility filter
        $accessibilityOptions = ['' => '-- ' . __('Select Accessibility') . ' --'] + $this->accessibilityOptions;
        $page->addFilter('accessibility')->setOptions($accessibilityOptions);

        parent::index();

        $page->exclude(['institution_id']);

        // sorting
        $page->get('asset_purpose_id')->setSortable(true);
        $page->get('asset_condition_id')->setSortable(true);
        $page->get('asset_status_id')->setSortable(true);
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

        $page->get('asset_status_id')->setControlType('select');
        $page->get('asset_type_id')->setControlType('select');
        $page->get('asset_purpose_id')->setControlType('select');
        $page->get('asset_condition_id')->setControlType('select');

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

        if ($page->is(['index', 'view', 'delete'])) {
            return $this->accessibilityOptions[$entity->accessibility];
        }
    }
}
