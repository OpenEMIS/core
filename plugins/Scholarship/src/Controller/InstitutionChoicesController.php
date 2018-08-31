<?php
namespace Scholarship\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;
use App\Model\Traits\OptionsTrait;

class InstitutionChoicesController extends PageController
{
    use OptionsTrait;

    private $locationTypeOptions = [];

    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Security.Users');
        $this->loadModel('FieldOption.Countries');
        $this->loadModel('Area.AreaAdministratives');
        $this->loadModel('Education.EducationFieldOfStudies');
        $this->loadModel('Scholarship.ApplicationInstitutionChoices');
        $this->loadModel('Scholarship.InstitutionChoiceTypes');

        $this->loadComponent('Scholarship.ScholarshipTabs');

        $this->Page->loadElementsFromTable($this->ApplicationInstitutionChoices);

        $this->locationTypeOptions = $this->getSelectOptions('InstitutionChoices.location_type');
        $this->institutionChoiceOptions = $this->InstitutionChoiceTypes->getList()->toArray();
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderLocationType'] = 'onRenderLocationType';
        $event['Controller.Page.onRenderScholarshipInstitutionChoiceTypeId'] = 'onRenderScholarshipInstitutionChoiceTypeId';
        return $event;
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        parent::beforeFilter($event);

        $page->get('scholarship_institution_choice_status_id')
            ->setLabel('Status');

        $page->get('scholarship_institution_choice_type_id')
            ->setLabel('Institution');

        $page->get('education_field_of_study_id')
            ->setLabel('Field Of Study');

        $page->get('is_selected')
            ->setLabel('Selection');

        $page->get('start_date')
            ->setLabel('Commencement Date');

        $page->get('end_date')
            ->setLabel('Completion Date');
        $page->exclude(['order']);
    }

    public function index()
    {
        $page = $this->Page;

        // default ordering
        $page->setQueryOption('order', [$this->ApplicationInstitutionChoices->aliasField('order') => 'ASC']);

        parent::index();
        
        $page->exclude(['estimated_cost', 'start_date', 'end_date', 'applicant_id', 'scholarship_id']);
        
        $this->reorderFields();
    }

    public function view($id)
    {
        parent::view($id);
        
        $page = $this->Page;
        $page->get('scholarship_institution_choice_type_id')
                ->setLabel('Institution');;
        $this->reorderFields();
    }

    public function add()
    {
        parent::add();
        $this->addEdit();
    }

    public function edit($id)
    {
        parent::edit($id);
        $this->addEdit($id);
    }

    private function addEdit($id=0)
    {
        $page = $this->Page;

        $scholarshipId = $page->getQueryString('scholarship_id');

        $page->get('location_type')
            ->setId('location_type')
            ->setControlType('select')
            ->setOptions($this->locationTypeOptions);

        $page->get('country_id')
            ->setControlType('select')
            ->setParams('Countries');
        
        $page->get('scholarship_institution_choice_type_id')
                ->setControlType('select')
                ->setOptions($this->institutionChoiceOptions)
                ->setLabel('Institution');

        $page->get('qualification_level_id')
            ->setControlType('select');

        $page->get('scholarship_institution_choice_status_id')
            ->setControlType('select');

        // Education Field of Studies
        $educationFieldOfStudies = $this->EducationFieldOfStudies
            ->find('availableFieldOfStudyOptionList', [
                'defaultOption' => false,
                'scholarship_id' => $scholarshipId
            ])
            ->toArray();

        $page->get('education_field_of_study_id')
            ->setControlType('select')
            ->setOptions($educationFieldOfStudies);

        $this->reorderFields();
    }

    public function setBreadCrumb($options)
    {
        $page = $this->Page;
        $plugin = $this->plugin;
        $name = $this->name;

        $userName = array_key_exists('userName', $options) ? $options['userName'] : '';
        $userId = array_key_exists('userId', $options) ? $options['userId'] : '';

        if ($plugin == 'Scholarship') {
            $page->addCrumb('Scholarships', [
                'plugin' => 'Scholarship',
                'controller' => 'Scholarships',
                'action' => 'Scholarships',
                'index'
            ]);

            if ($name == 'ScholarshipApplicationInstitutionChoices') {
                $page->addCrumb('Applicants', [
                    'plugin' => 'Scholarship',
                    'controller' => 'Scholarships',
                    'action' => 'Applications',
                    'index'
                ]);

            } else if ($name == 'ScholarshipRecipientInstitutionChoices') {
                $page->addCrumb('Recipients', [
                    'plugin' => 'Scholarship',
                    'controller' => 'ScholarshipRecipients',
                    'action' => 'index'
                ]);
            }

            $page->addCrumb($userName);
            $page->addCrumb('Instititution Choices');

        } else if ($plugin == 'Profile') {
            $page->addCrumb('Profile', [
                'plugin' => 'Profile',
                'controller' => 'Profiles',
                'action' => 'Profiles',
                'view',
                $this->paramsEncode(['id' => $userId])
            ]);
            $page->addCrumb($userName);
            $page->addCrumb('Scholarship Applications');
            $page->addCrumb('Instititution Choices');
        }
    }

    public function setupTabElements()
    {
        $page = $this->Page;
        $name = $this->name;

        $tabElements = [];
        if ($name == 'ScholarshipApplicationInstitutionChoices') {
            $tabElements = $this->ScholarshipTabs->getScholarshipApplicationTabs();
        } elseif ($name == 'ScholarshipRecipientInstitutionChoices') {
            $tabElements = $this->ScholarshipTabs->getScholarshipRecipientTabs();
        } elseif ($name == 'ProfileApplicationInstitutionChoices') {
            $tabElements = $this->ScholarshipTabs->getScholarshipProfileTabs();
        }

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }

        // set active tab
        $page->getTab('InstitutionChoices')->setActive('true');
    }

    public function onRenderLocationType(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view', 'delete'])) {
            $value = $this->locationTypeOptions[$entity->location_type];

            return $value;
        }
    }

    public function onRenderScholarshipInstitutionChoiceTypeId(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view', 'delete'])) {
            $value = $this->institutionChoiceOptions[$entity->scholarship_institution_choice_type_id];

            return $value;
        }
    }

    private function reorderFields()
    {
        $page = $this->Page;

        $page->get('education_field_of_study_id')
             ->setLabel('Area of Study');

        $page->move('country_id')->after('location_type');
        $page->move('scholarship_institution_choice_status_id')->after('scholarship_institution_choice_type_id');
        $page->move('education_field_of_study_id')->after('estimated_cost');
        $page->move('qualification_level_id')->after('course_name');
    }
}
