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
        'SpecialNeedsPlans' => 'Plans'
    ];

    private $_sessionReadKeys = [
        'Students' => 'Student.Students.name',
        'Staff' => 'Staff.Staff.name',
        'Profiles' => 'Auth.User.name',
        'Directories' => 'Directory.Directories.name'
    ];

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 100];
        return $events;
    }

    public function beforeAction(Event $event)
    {
        $model = $this->_table;
        $controller = $this->_table->controller;
        $controllerName = $controller->name;

        // Breadcrumbs
        $navigation = $model->Navigation;
        $oldTitle = $model->getHeader($model->alias());
        $newTitle = $this->_tabFeatures[$model->alias()];
        $newTitle = $model->getHeader($newTitle);
        $navigation->substituteCrumb($oldTitle, $newTitle);

        // Header
        $session = $model->request->session();
        $sessionKey = $this->_sessionReadKeys[$controllerName];
        $username = $session->read($sessionKey);
        $postfix = $newTitle;
        $header = $username . ' - ' . $postfix;
        $controller->set('contentHeader', $header);

        // Tab elements
        $tabElements = $this->getSpecialNeedsTab();
        $tabElements = $controller->TabPermission->checkTabPermission($tabElements);
        $controller->set('tabElements', $tabElements);
        $controller->set('selectedAction', $model->alias());
    }

    public function getSpecialNeedsTab()
    {
        $controller = $this->_table->controller;
        $plugin = $controller->plugin;
        $controllerName = $controller->name;

        $urlBase = [
            'plugin' => $plugin,
            'controller' => $controllerName
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
