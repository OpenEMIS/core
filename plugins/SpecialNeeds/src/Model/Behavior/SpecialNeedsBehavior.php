<?php
namespace SpecialNeeds\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Event\Event;

class SpecialNeedsBehavior extends Behavior
{
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

        $tabFeatures = [
            'Referrals' => 'SpecialNeedsReferrals',
            'Assessments' => 'SpecialNeedsAssessments',
            'Services' => 'SpecialNeedsServices',
            'Devices' => 'SpecialNeedsDevices',
            'Plans' => 'SpecialNeedsPlans'
        ];
        
        $tabElements = [];
        foreach ($tabFeatures as $featureName => $feature) {
            if ($controller->AccessControl->check([$controllerName, $feature, 'index'])) {
                $featureUrl = array_merge($urlBase, ['action' => $feature]);
                $tabElements[$feature] = [
                    'url' => $featureUrl,
                    'text' => $featureName
                ];
            }
        }

        return $tabElements;
    }
}
