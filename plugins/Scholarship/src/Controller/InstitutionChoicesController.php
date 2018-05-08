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
        $this->loadModel('Scholarship.InstitutionChoices');
        $this->Page->loadElementsFromTable($this->InstitutionChoices);

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

        $page->get('institution_choice_status_id')
            ->setLabel('Status');

        $page->get('institution_name')
            ->setLabel('Institution');

        $page->get('education_field_of_study_id')
            ->setLabel('Field Of Study');

        // $page->get('location_type_id')
        //     ->setLabel('Location Type');

        $page->get('is_selected')
            ->setLabel('Selection');
    }

    public function index()
    {
        $page = $this->Page;
        parent::index();

        $page->exclude(['institution_id', 'estimated_cost', 'start_date', 'end_date', 'applicant_id', 'scholarship_id', 'is_selected', 'requested_amount']);

        $page->move('institution_choice_status_id')->first();
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

        $page->get('level_of_study_id')
            ->setControlType('select');

        $page->get('institution_choice_status_id')
            ->setControlType('select');

        // Education Field of Studies
        $educationFieldOfStudies = $this->EducationFieldOfStudies
            ->find('scholarshipOptionList', [
                'defaultOption' => false,
                'scholarship_id' => $scholarshipId
            ])
            ->toArray();

        $page->get('education_field_of_study_id')
            ->setControlType('select')
            ->setOptions($educationFieldOfStudies);
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

    public function setupTabElements($options)
    {
        $page = $this->Page;
        $plugin = $this->plugin;
        $tabElements = [];
        $queryString = array_key_exists('queryString', $options) ? $options['queryString'] : '';

        if ($plugin == 'Scholarship') {
            $tabElements = [
                'ScholarshipApplications' => ['text' => __('Overview')],
                // 'Generals' => ['text' => __('General')],
                'Identities' => ['text' => __('Identities')],
                'UserNationalities' => ['text' => __('Nationalities')],
                'Contacts' => ['text' => __('Contacts')],
                'Guardians' => ['text' => __('Guardians')],
                // 'ExaminationResults' => ['text' => __('Examinations')],
                // 'Qualifications' => ['text' => __('Qualifications')],
                'ScholarshipHistories' => ['text' => __('Scholarship History')], //page
                'ApplicationInstitutionChoices' => ['text' => __('Institution Choices')], //page
                'ScholarshipApplicationAttachments' => ['text' => __('Attachments')], //page
            ];

            foreach ($tabElements as $action => &$obj) {
                if ($action == 'ScholarshipApplications') {
                    $url = [
                        'plugin' => $plugin,
                        'controller' => 'ScholarshipApplications',
                        'action' => $action,
                        'view',
                        $queryString,
                        'queryString' => $queryString
                    ];
                } elseif (in_array($action, ['ScholarshipHistories', 'ApplicationInstitutionChoices', 'ScholarshipApplicationAttachments'])) {
                    $url = [
                        'plugin' => $plugin,
                        'controller' => $action,
                        'action' => 'index',
                        'queryString' => $queryString
                    ];

                } else {
                    $url = [
                        'plugin' => $plugin,
                        'controller' => 'ScholarshipApplications',
                        'action' => $action,
                        'index',
                        'queryString' => $queryString
                    ];

                    if ($action == 'UserNationalities') {
                        $url['action'] = 'Nationalities';
                    }
                }
                $obj['url'] = $url;
            }
        } else if ($plugin == 'Profile') {
            $tabElements = [
                'ScholarshipApplications' => [
                    'url' => ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'ScholarshipApplications', 'view', $queryString, 'queryString' => $queryString],
                    'text' => __('Overview')
                ],
                'InstitutionChoices' => [
                    'url' => ['plugin' => 'Profile', 'controller' => 'ProfileInstitutionChoices', 'action' => 'index', 'queryString' => $queryString],
                    'text' => __('Institution Choices')
                ],
                'Attachments' => [
                    'url' => ['plugin' => 'Profile', 'controller' => 'ProfileApplicationAttachments', 'action' => 'index', 'queryString' => $queryString],
                    'text' => __('Attachments')
                ]
            ];
        }

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);

        foreach ($tabElements as $tab => $tabAttr) {
            $page->addTab($tab)
                ->setTitle($tabAttr['text'])
                ->setUrl($tabAttr['url']);
        }

        // set active tab
        // $page->getTab('InstitutionChoices')->setActive('true');
    }

    public function onRenderLocationTypeId(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index', 'view', 'delete'])) {
            $value = $this->locationTypeOptions[$entity->location_type_id];

            return $value;
        }
    }
}
