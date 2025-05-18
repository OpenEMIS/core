<?php
namespace Scholarship\Controller\Component;

use Cake\Controller\Component;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;
use Cake\Log\Log;

class ScholarshipTabsComponent extends Component
{
    public $components = ['TabPermission', 'Page.Page'];
    private $queryString;

    public function initialize(array $config) : void
    {
        $this->controller = $this->_registry->getController();
        $this->queryString = $this->getController()->getRequest()->getQuery('queryString');

        $this->controller->loadModel('Scholarship.Scholarships');
        $this->controller->loadModel('Scholarship.FinancialAssistanceTypes');
    }

    public function getScholarshipApplicationTabs($options = [])
    {
        $urlRequest = $this->queryString;
        if (empty($urlRequest)) {
            $urlRequest = $this->getController()->getRequest()->getParam('pass')[1];
        }
        if(!empty($urlRequest)){
            $session = $this->getController()->getRequest()->getSession();
            $session->write('urlRequest', $urlRequest);
        }
        if (empty($urlRequest)) {
            $session = $this->getController()->getRequest()->getSession();
            $urlRequest = $session->read('urlRequest');
        }
        $tabElements = [
            'Applications' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Applications', 0 =>'view', 1=> $urlRequest, 'queryString' => $this->queryString],
                'text' => __('Overview')
            ],
            'Identities' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Identities', 0 => 'index', 1 => $urlRequest],
                'text' => __('Identities')
            ],
            'UserNationalities' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Nationalities', 0 => 'index', 1 => $urlRequest],
                'text' => __('Nationalities')
            ],
            'Contacts' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Contacts', 0 => 'index', 1 => $urlRequest],
                'text' => __('Contacts')
            ],
            'Guardians' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Guardians', 0 => 'index', 1 => $urlRequest],
                'text' => __('Guardians')
            ],
            'Histories' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'Histories',  'index',  1 => $urlRequest],
                'text' => __('Scholarship History')
            ],
            'InstitutionChoices' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'ScholarshipApplicationInstitutionChoices', 0 => 'index', 1=> $urlRequest, 'queryString' => $this->queryString],
                'text' => __('Institution Choices')
            ],
            'InstitutionAttachment' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'ScholarshipApplicationAttachments', 0 => 'index',  1=> $urlRequest, 'queryString' => $this->queryString],
                'text' => __('Attachments')
            ]

        ];
        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getScholarshipRecipientTabs($options = [])
    {
        $ids = $this->controller->paramsDecode($this->queryString);
        $encodedIds = $this->Page->encode($ids);

        $tabElements = [
            'Recipients' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipRecipients', 'action' => 'view', $this->queryString, 'queryString' => $this->queryString],
                'text' => __('Overview')
            ],
            'InstitutionChoices' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipRecipientInstitutionChoices', 'action' => 'index', 'queryString' => $this->queryString],
                'text' => __('Institution Choices')
            ],
            'PaymentStructures' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'RecipientPaymentStructures', 'index','queryString' => $this->queryString],
                'text' => __('Payment Structures')
            ],
            'Disbursements' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'Scholarships', 'action' => 'RecipientPayments', 'index', 'queryString' => $this->queryString],
                'text' => __('Disbursements')
            ],
            'Collections' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipRecipientCollections', 'action' => 'index', 'queryString' => $this->queryString],
                'text' => __('Collections')
            ],
            'AcademicStandings' => [
                'url' => ['plugin' => 'Scholarship', 'controller' => 'ScholarshipRecipientAcademicStandings', 'action' => 'index', 'queryString' => $this->queryString],
                'text' => __('Academic Standings')
            ]
        ];

        $isLoan = false;
        if (isset($ids['scholarship_id']) && !empty($ids['scholarship_id'])) {
            $scholarshipEntity = $this->controller->Scholarships->get($ids['scholarship_id']);
            // $isLoan = $this->controller->FinancialAssistanceTypes->is($scholarshipEntity->scholarship_financial_assistance_type_id, 'LOAN');
            // Start POCOR-7570
            $FinancialAssistanceTypesTable = TableRegistry::get('Scholarship.FinancialAssistanceTypes');
            $rec = $FinancialAssistanceTypesTable->get($scholarshipEntity->scholarship_financial_assistance_type_id);
            $isLoan = false;
            if(!empty($rec)){
                if($rec->code == 'LOAN'){
                    $isLoan = true;
                }
            }
            // END POCOR-7570
        }
        if (!$isLoan) {
            unset($tabElements['Collections']);
        }

        return $this->TabPermission->checkTabPermission($tabElements);
    }

    public function getScholarshipProfileTabs($options = [])
    {
        $urlRequest = $this->queryString;
        if (empty($urlRequest)) {
            $urlRequest = $this->getController()->getRequest()->getParam('pass')[1];
        }
        if(!empty($urlRequest)){
            $session = $this->getController()->getRequest()->getSession();
            $session->write('urlRequest', $urlRequest);
        }
        if (empty($urlRequest)) {
            $session = $this->getController()->getRequest()->getSession();
            $urlRequest = $session->read('urlRequest');
        }
        if(empty($urlRequest)){
            $urlRequest = $this->getController()->getRequest()->getParam('pass')[1];
        }
        $tabElements = [
            'ScholarshipApplications' => [
                'url' => ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'ScholarshipApplications', 0 => 'view', 1 => $urlRequest, 'queryString' => $urlRequest],
                'text' => __('Overview')
            ],
            'InstitutionChoices' => [
               'url' => ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'ScholarshipApplicationInstitutionChoices', 0=> 'index', 1 => $urlRequest],
                'text' => __('Institution Choices')
            ],
            'Attachments' => [
                'url' => ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'ScholarshipApplicationAttachments', 0=> 'index', 1 => $urlRequest],
                'text' => __('Attachments')
            ]
        ];

       return $this->TabPermission->checkTabPermission($tabElements);
       // return $tabElements;
    }
}
