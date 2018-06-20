<?php
namespace Institution\Controller\Component;

use Cake\Controller\Component;

class InstitutionCommitteeTabsComponent extends Component
{
    public $components = ['TabPermission', 'Page.Page'];
    private $querystring;
    private $institutionId;
    private $institutionCommitteeId;

    public function initialize(array $config)
    {   
        $this->querystring = $this->request->query('querystring');
        $this->institutionId = $this->request->params['institutionId'];
        $decodeQueryString = $this->Page->decode($this->querystring);

        $institutionCommitteeId = [];
        if ($decodeQueryString && array_key_exists('institution_committee_id', $decodeQueryString)) {
            $institutionCommitteeId['id'] = $decodeQueryString['institution_committee_id'];
        }
        $this->institutionCommitteeId = $this->Page->encode($institutionCommitteeId);
    }

    public function getInstitutionCommitteeTabs($options = [])
    {
        $tabElements = [
            'InstitutionCommittees' => [
                'url' => ['plugin' => 'Institution', 'institutionId' => $this->institutionId, 'controller' => 'InstitutionCommittees', 'action' => 'view', $this->institutionCommitteeId, 'querystring' => $this->querystring],
                'text' => __('Overview')
            ],
            'Attachments' => [
                'url' => ['plugin' => 'Institution', 'institutionId' => $this->institutionId, 'controller' => 'InstitutionCommitteeAttachments', 'action' => 'index', 'querystring' => $this->querystring],
                'text' => __('Attachments')
            ]
        ];

        return $this->TabPermission->checkTabPermission($tabElements);
    }
}

