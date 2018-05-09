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
        $this->loadModel('Education.EducationFieldOfStudies');
        $this->loadModel('Security.Users');
        $this->loadModel('Scholarship.ApplicationInstitutionChoices');
        $this->Page->loadElementsFromTable($this->ApplicationInstitutionChoices);

        $this->locationTypeOptions = $this->getSelectOptions('InstitutionChoices.location_type');
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderLocationTypeId'] = 'onRenderLocationTypeId';
        return $event;
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        parent::beforeFilter($event);

        $page->get('scholarship_institution_choice_status_id')
            ->setLabel('Status');

        $page->get('institution_name')
            ->setLabel('Institution');

        $page->get('education_field_of_study_id')
            ->setLabel('Field Of Study');

        $page->get('is_selected')
            ->setLabel('Selection');
    }

    public function index()
    {
        $page = $this->Page;
        parent::index();

        $page->exclude(['institution_id', 'estimated_cost', 'start_date', 'end_date', 'applicant_id', 'scholarship_id', 'is_selected', 'requested_amount']);

        $page->move('scholarship_institution_choice_status_id')->first();
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

        $scholarshipId = $page->getQueryString('scholarship_id');

        $page->get('location_type')
            ->setControlType('select')
            ->setOptions($this->locationTypeOptions);

        $page->get('country_id')
            ->setControlType('select');

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
                'action' => 'index'
            ]);

            if ($name == 'ApplicationInstitutionChoices') {
                $page->addCrumb('Applicants', [
                    'plugin' => 'Scholarship',
                    'controller' => 'ScholarshipApplications',
                    'action' => 'index'
                ]);

                $page->addCrumb($userName);

            } else if ($name == 'RecipientInstitutionChoices') {
                $page->addCrumb('Applicants', [
                    'plugin' => 'Scholarship',
                    'controller' => 'ScholarshipApplications',
                    'action' => 'index'
                ]);
            }

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
            $page->addCrumb('Instititution Choices');
        }
    }

   public function setupTabElements()
    {  
        $page = $this->Page;

        if (array_key_exists('queryString', $this->request->query)) {
            $queryString = $this->request->query('queryString');
        }

        $tabElements = [
            'Applications' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Applications', 'view', $queryString, 'queryString' => $queryString],
                'text' => __('Overview')
            ],
            'Identities' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Identities', 'index', 'queryString' => $queryString],
                'text' => __('Identities')
            ],
            'UserNationalities' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Nationalities', 'index', 'queryString' => $queryString],
                'text' => __('Nationalities')
            ],
            'Contacts' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Contacts', 'index', $queryString, 'queryString' => $queryString],
                'text' => __('Contacts')
            ],
            'Guardians' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Guardians', 'index', $queryString, 'queryString' => $queryString],
                'text' => __('Guardians')
            ],
            'Histories' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipHistories', 'action' => 'index', 'queryString' => $queryString],
                'text' => __('Scholarship History')
            ],
            'InstitutionChoices' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ApplicationInstitutionChoices', 'action' => 'index', 'queryString' => $queryString],
                'text' => __('Institution Choices')
            ],
            'ApplicationAttachments' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipApplicationAttachments', 'action' => 'index', 'queryString' => $queryString],
                'text' => __('Attachments')
            ],
     
        ];

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }
        // set active tab
        $page->getTab('InstitutionChoices')->setActive('true');
    }

    public function onRenderLocationTypeId(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view', 'delete'])) {
            $value = $this->locationTypeOptions[$entity->location_type_id];

            return $value;
        }
    }

    private function reorderFields()
    {
        $page = $this->Page;

        $page->move('country_id')->after('location_type');
        $page->move('scholarship_institution_choice_status_id')->after('institution_name');
        $page->move('education_field_of_study_id')->after('estimated_cost');
        $page->move('qualification_level_id')->after('course_name');
    }
}
