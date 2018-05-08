<?php
namespace Scholarship\Controller;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Page\Model\Entity\PageElement;
use App\Controller\PageController;

class ApplicationAttachmentsController extends PageController
{
    public function initialize()
    {
        parent::initialize();
        $this->loadModel('Security.Users');
        $this->loadModel('Scholarship.ApplicationAttachments');
        $this->loadModel('Scholarship.ScholarshipAttachmentTypes');

        $this->Page->loadElementsFromTable($this->ApplicationAttachments);
    }

    public function implementedEvents()
    {
        $event = parent::implementedEvents();
        $event['Controller.Page.onRenderMandatory'] = 'onRenderMandatory';
        return $event;
    }

    public function beforeFilter(Event $event)
    {
        $page = $this->Page;

        parent::beforeFilter($event);

        $page->get('scholarship_attachment_type_id')
            ->setLabel('Type');
    }

    public function index()
    {
        $page = $this->Page;
        $page->setAutoContain(false);

        parent::index();

        $page->exclude(['applicant_id', 'scholarship_id', 'file_name', 'file_content']);

        $page->addNew('mandatory');
        $page->addNew('uploaded_by')->setDisplayFrom('created_user.name');
        $page->addNew('uploaded_on')->setDisplayFrom('created');

        $page->move('mandatory')->first();
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

        $page->exclude(['file_name']);
        $scholarshipId = $page->getQueryString('scholarship_id');

        $attachmentTypesOption = $this->ScholarshipAttachmentTypes
            ->find('attachmentTypeOptionList', [
                'defaultOption' => false,
                'scholarship_id' => $scholarshipId
            ])
            ->toArray();

        $page->get('scholarship_attachment_type_id')
            ->setControlType('select')
            ->setOptions($attachmentTypesOption);
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

            $page->addCrumb('Applicants', [
                    'plugin' => 'Scholarship',
                    'controller' => 'ScholarshipApplications',
                    'action' => 'index'
            ]);

            $page->addCrumb($userName);
            $page->addCrumb(__('Attachments'));

        } else if ($plugin == 'Profile') {
            $page->addCrumb('Profile', [
                'plugin' => 'Profile',
                'controller' => 'Profiles',
                'action' => 'Profiles',
                'view',
                $this->paramsEncode(['id' => $userId])
            ]);
            $page->addCrumb($userName);
            $page->addCrumb(__('Attachments'));
        }
    }

     public function setupTabElements($options)
     {
        $page = $this->Page;
        $plugin = $this->plugin;
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
                'ApplicationInstitutionChoices' => ['text' => __('Institution Choice')], //page
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
        // $page->getTab('ScholarshipApplicationAttachments')->setActive('true');
    }

    public function onRenderMandatory(Event $event, Entity $entity, PageElement $element)
    {
        $page = $this->Page;

        if ($page->is(['index'])) {
            if($entity->scholarship_attachment_type->is_mandatory == 1) {
                return "<i class='fa fa-check'></i>";
            } else {
                return "<i class='fa fa-close'></i>";
            }
        }
    }
}
