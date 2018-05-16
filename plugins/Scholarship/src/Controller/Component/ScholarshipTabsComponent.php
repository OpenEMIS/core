<?php
namespace Scholarship\Controller\Component;

use Cake\Controller\Component;

class ScholarshipTabsComponent extends Component
{
    public $components = ['TabPermission', 'Page.Page'];

    public function initialize(array $config)
    {
        $this->controller = $this->_registry->getController();
    }

    public function getScholarshipApplicationTabs($options = [])
    {
    }

    public function getScholarshipRecipientTabs($options = [])
    {
    }

    public function getScholarshipProfileTabs($options = [])
    {
        $queryString = $this->request->query('queryString');

        $tabElements = [
            'ScholarshipApplications' => [
                'url' => ['plugin' => 'Profile', 'controller' => 'Profiles', 'action' => 'ScholarshipApplications', 'view', $queryString, 'queryString' => $queryString],
                'text' => __('Overview')
            ],
            'ProfileApplicationInstitutionChoices' => [
                'url' => ['plugin' => 'Profile', 'controller' => 'ProfileApplicationInstitutionChoices', 'action' => 'index', 'queryString' => $queryString],
                'text' => __('Institution Choices')
            ],
            'ProfileApplicationAttachments' => [
                'url' => ['plugin' => 'Profile', 'controller' => 'ProfileApplicationAttachments', 'action' => 'index', 'queryString' => $queryString],
                'text' => __('Attachments')
            ]
        ];

        $tabElements = $this->TabPermission->checkTabPermission($tabElements);
        return $this->renderTabs($tabElements);
    }

    public function renderTabs($tabElements)
    {
        if ($this->controller instanceof \Page\Controller\PageController) {
            // page
            $page = $this->Page;

            foreach ($tabElements as $tab => $tabAttr) {
                $page->addTab($tab)
                    ->setTitle($tabAttr['text'])
                    ->setUrl($tabAttr['url']);
            }

            // set active tab
            $page->getTab($this->controller->name)->setActive('true');
        } else {
            return $tabElements;
            // CAv4
            // $this->controller->set('tabElements', $tabElements);
            // pr($this->controller);
            // $this->controller->set('selectedAction', $this->alias());
        }
    }
}
