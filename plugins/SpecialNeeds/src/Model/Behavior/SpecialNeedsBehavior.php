<?php
namespace SpecialNeeds\Model\Behavior;

use Cake\Event\Event;
use Cake\ORM\Behavior;
use Cake\Utility\Inflector;

class SpecialNeedsBehavior extends Behavior
{
    private $_tabFeatures = [
        'SpecialNeedsReferrals' => 'Referrals',
        'SpecialNeedsAssessments' => 'Assessments',
        'SpecialNeedsServices' => 'Services',
        'SpecialNeedsDevices' => 'Devices',
        'SpecialNeedsPlans' => 'Plans',
        'SpecialNeedsDiagnostics' => 'Diagnostics' //POCOR-6873
    ];

    private $_sessionReadKeys = [
        'Students' => 'Student.Students.name',
        'Staff' => 'Staff.Staff.name',
        'Profiles' => 'Auth.User.name',
        'Directories' => 'Directory.Directories.name'
    ];

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 100];
        return $events;
    }

    public function beforeAction(Event $event)
    {
        $model = $this->_table;
        $controller = $this->_table->controller;
        $controllerName = $controller->getName();

        // Breadcrumbs
        $navigation = $model->Navigation;
        $oldTitle = $model->getHeader($model->getAlias());
        $newTitle = $this->_tabFeatures[$model->getAlias()];
        $newTitle = $model->getHeader($newTitle);
        $navigation->substituteCrumb($oldTitle, $newTitle);

        // Header
        $session = $model->request->getSession();
        $sessionKey = $this->_sessionReadKeys[$controllerName];
        $username = $session->read($sessionKey);
        $postfix = $newTitle;
        $header = $username . ' - ' . $postfix;
        $controller->set('contentHeader', $header);

        // Tab elements
        $tabElements = $this->getSpecialNeedsTab();
        $tabElements = $controller->TabPermission->checkTabPermission($tabElements);
        $controller->set('tabElements', $tabElements);
        $controller->set('selectedAction', $model->getAlias());
    }

    public function getSpecialNeedsTab()
    {
        $model = $this->_table;
        $controller = $model->controller;
        $plugin = $controller->getPlugin();
        $controllerName = $controller->getName();
        /*$userID = $model->getQueryString();
        $param = ['user_id' => $userID];
        $queryString = $model->paramsEncode($param);*/

        $queryString = $model->getQueryString();
        $encodedQueryString = $model->paramsEncode($queryString);
        $urlBase = [
            'plugin' => $plugin,
            'controller' => $controllerName,
            '0' => 'index',
            '1' => $encodedQueryString
        ];

        $tabElements = [];
        foreach ($this->_tabFeatures as $feature => $featureName) {
            if ($controller->AccessControl->check([$controllerName, $feature, 'index'])) {
                $featureUrl = array_merge($urlBase, ['action' => $feature]);
                $tabElements[$feature] = [
                    'url' => $featureUrl,
                    'text' => __($featureName)
                ];
            }
        }

        return $tabElements;
    }
}
