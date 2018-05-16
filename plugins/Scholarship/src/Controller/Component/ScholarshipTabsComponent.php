<?php
namespace Scholarship\Controller\Component;

use Cake\Controller\Component;

class ScholarshipTabsComponent extends Component
{
    public $components = ['TabPermission', 'Page.Page'];
    private $queryString;

    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
        $this->queryString = $this->request->query('queryString');
    }

    public function getScholarshipTabs($options = [])
    {
        $tabElements = [
            'Scholarships' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Scholarships', 'view', $this->queryString, 'queryString' => $this->queryString],
                'text' => __('Overview')
            ],
            'Attachments' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipAttachmentTypes', 'action' => 'index', 'queryString' => $this->queryString],
                'text' => __('Attachments')
            ]
        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getScholarshipApplicationTabs($options = [])
    {
        $tabElements = [
            'Applications' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Applications', 'view', $this->queryString, 'queryString' => $this->queryString],
                'text' => __('Overview')
            ],
            'Identities' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Identities', 'index', 'queryString' => $this->queryString],
                'text' => __('Identities')
            ],
            'UserNationalities' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Nationalities', 'index', 'queryString' => $this->queryString],
                'text' => __('Nationalities')
            ],
            'Contacts' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Contacts', 'index', 'queryString' => $this->queryString],
                'text' => __('Contacts')
            ],
            'Guardians' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Guardians', 'index', 'queryString' => $this->queryString],
                'text' => __('Guardians')
            ],
            'Histories' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Histories',  'index', 'queryString' => $this->queryString],
                'text' => __('Scholarship History')
            ],
            'InstitutionChoices' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipApplicationInstitutionChoices', 'action' => 'index', 'queryString' => $this->queryString],
                'text' => __('Institution Choices')
            ],
            'Attachments' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipApplicationAttachments', 'action' => 'index', 'queryString' => $this->queryString],
                'text' => __('Attachments')
            ]
        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getScholarshipRecipientTabs($options = [])
    {
    }

    public function getScholarshipProfileTabs($options = [])
    {
        $tabElements = [
            'ScholarshipApplications' => [
                'url' => ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'ScholarshipApplications', 'view', $this->queryString, 'queryString' => $this->queryString],
                'text' => __('Overview')
            ],
            'InstitutionChoices' => [
                'url' => ['plugin' => 'Profile', 'controller' => 'ProfileApplicationInstitutionChoices', 'action' => 'index', 'queryString' => $this->queryString],
                'text' => __('Institution Choices')
            ],
            'Attachments' => [
                'url' => ['plugin' => 'Profile', 'controller' => 'ProfileApplicationAttachments', 'action' => 'index', 'queryString' => $this->queryString],
                'text' => __('Attachments')
            ]
        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }
}
