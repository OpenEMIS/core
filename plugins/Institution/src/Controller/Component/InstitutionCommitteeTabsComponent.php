<?php
namespace Institution\Controller\Component;

use Cake\Controller\Component;

class InstitutionCommitteeTabsComponent extends Component
{
    public $components = ['TabPermission', 'Page.Page'];
    private $querystring;
    private $institutionId;
    private $institutionCommitteeId;
    private $pass;

    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
        $this->querystring = $this->request->query('querystring');
        $this->institutionId = $this->request->params['institutionId'];
    }

    public function getInstitutionCommitteeTabs($options = [])
    {
        $tabElements = [
            'InstitutionCommittees' => [
                'url' => ['plugin' => 'Institution', 'institutionId' => $this->institutionId, 'controller' => 'InstitutionCommittees', 'action' => 'view', $this->querystring, 'querystring' => $this->querystring],
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

