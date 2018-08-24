<?php
namespace Profile\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Utility\Inflector;
use Cake\Core\Configure;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;
use App\Model\Traits\OptionsTrait;

class ScholarshipsDirectoryController extends PageController
{
    use OptionsTrait;

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Profile.ScholarshipsDirectory');
        $this->loadModel('Education.EducationFieldOfStudies');
        $this->loadModel('Configuration.ConfigItems');
        $this->Page->loadElementsFromTable($this->ScholarshipsDirectory);

        $this->Page->disable(['add', 'edit', 'delete']);
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderFieldOfStudies'] = 'onRenderFieldOfStudies';
        $event['Controller.Page.onRenderBond'] = 'onRenderBond';
        $event['Controller.Page.onRenderDuration'] = 'onRenderDuration';
        return $event;
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;
        parent::beforeFilter($event);

        $applicantId = $this->Auth->user('id');
        $applicantName = $this->Auth->user('name');
        $encodedApplicantId = $this->paramsEncode(['id' => $applicantId]);
        $currency = $this->ConfigItems->value('currency');

        // set queryString
        $page->setQueryString('applicant_id', $applicantId);

        // set header
        $page->setHeader($applicantName . ' - ' . __('Scholarships Directory'));

        // set breadcrumbs
        $page->addCrumb('Profile', ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'Profiles', 'view', $encodedApplicantId]);
        $page->addCrumb($applicantName);
        $page->addCrumb('Scholarships Directory');

        // set labels
        $page->get('scholarship_financial_assistance_type_id')->setLabel('Financial Assistance Type');
        $page->get('scholarship_funding_source_id')->setLabel('Funding Source');
        $page->get('maximum_award_amount')->setLabel(__('Annual Award Amount') . ' (' . $currency . ')');
        $page->get('total_amount')->setLabel(__('Total Award Amount') . ' (' . $currency . ')');
    }

    public function index()
    {
        $page = $this->Page;
        parent::index();

        $page->exclude(['description', 'scholarship_financial_assistance_type_id', 'scholarship_funding_source_id', 'academic_period_id', 'total_amount', 'requirements', 'instructions']);

        $page->get('name')
            ->setLabel('Scholarship Name');
        // back button
        $page->addToolbar('back', [
            'type' => 'element',
            'element' => 'Page.button',
            'data' => [
                'title' => __('Back'),
                'url' => [
                    'plugin' => 'Profile',
                    'controller' => 'Profiles',
                    'action' => 'ScholarshipApplications',
                    'index'
                ],
                'iconClass' => 'fa kd-back',
                'linkOptions' => ['title' => __('Back'), 'id' => 'btn-back']
            ],
            'options' => []
        ]);
    }

    public function view($id)
    {
        $page = $this->Page;
        $page->setAutoContain(false);

        parent::view($id);

        $page->addNew('field_of_studies')
            ->setControlType('select')
            ->setAttributes('multiple', true);
        $page->get('name')
            ->setLabel('Scholarship Name');

        $page->move('scholarship_financial_assistance_type_id')->after('description');
        $page->move('scholarship_funding_source_id')->after('scholarship_financial_assistance_type_id');
        $page->move('academic_period_id')->after('scholarship_funding_source_id');
        $page->move('field_of_studies')->after('academic_period_id');

        // extra fields
        $entity = $page->getVar('data');
        if ($entity->has('financial_assistance_type')) {
            switch ($entity->financial_assistance_type->code) {
                case 'SCHOLARSHIP':
                    // No implementation
                    break;
                case 'LOAN':
                    if ($entity->has('loan')) {
                        $interestRateOptions = $this->getSelectOptions('Scholarships.interest_rate');

                        $page->addNew('interest_rate')
                            ->setLabel(__('Interest Rate').' (%)')
                            ->setValue($entity->loan->interest_rate);

                        $page->addNew('interest_rate_type')
                            ->setValue($interestRateOptions[$entity->loan->interest_rate_type]);

                        $page->addNew('payment_frequency')
                            ->setValue($entity->loan->payment_frequency->name);

                        $page->addNew('loan_term')
                            ->setValue($entity->loan->loan_term . ' ' . __('Years'));

                        $page->move('interest_rate')->after('bond');
                        $page->move('interest_rate_type')->after('interest_rate');
                        $page->move('payment_frequency')->after('interest_rate_type');
                        $page->move('loan_term')->after('payment_frequency');
                    }
                    break;
            }
        }

        // add button
        $scholarshipId = $page->decode($id)['id'];
        $addUrl = $this->setQueryString([
            'plugin' => 'Profile',
            'controller' => 'Profiles',
            'action' => 'ScholarshipApplications',
            'add'
        ], ['scholarship_id' => $scholarshipId]);

        $page->addToolbar('back', []); // to fix the order of the buttons
        $page->addToolbar('add', [
            'type' => 'element',
            'element' => 'Page.button',
            'data' => [
                'title' => __('Apply'),
                'url' => $addUrl,
                'iconClass' => 'fa kd-add',
                'linkOptions' => ['title' => __('Apply')]
            ],
            'options' => []
        ]);
    }

    public function onRenderFieldOfStudies(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['view'])) {
            $list = [];

            if ($this->ScholarshipsDirectory->checkIsSelectAll($entity)) {
                $list = $this->EducationFieldOfStudies
                    ->find('order')
                    ->find('visible')
                    ->extract('name')
                    ->toArray();

            } else if ($entity->has('field_of_studies')) {
                foreach($entity->field_of_studies as $fieldOfStudy) {
                    $list[] = $fieldOfStudy->name;
                }
            }

            $value = implode(", ", $list);
            return $value;
        }
    }

    public function onRenderBond(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view'])) {
            return $entity->bond . ' ' . __('Years');
        }
    }

    public function onRenderDuration(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view'])) {
            return $entity->duration . ' ' . __('Years');
        }
    }
}
