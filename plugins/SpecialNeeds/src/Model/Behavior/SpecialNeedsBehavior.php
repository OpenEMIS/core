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
        $plugin = $controller->plugin;
        $name = $controller->name;

        $tabElements = $this->getSpecialNeedsTab();
        $tabElements = $controller->TabPermission->checkTabPermission($tabElements);
        $controller->set('tabElements', $tabElements);
        $controller->set('selectedAction', $model->alias());
    }

    public function getSpecialNeedsTab()
    {
        $controller = $this->_table->controller;
        $plugin = $controller->plugin;
        $name = $controller->name;

        $urlBase = [
            'plugin' => $plugin,
            'controller' => $name
        ];

        $tabFeatures = [
            'Referrals' => 'SpecialNeedsReferrals',
            'Assessments' => 'SpecialNeedsAssessments',
            'Services' => 'SpecialNeedsServices',
        ];
        
        $tabElements = [];
        foreach ($tabFeatures as $action => $feature) {
            if ($controller->AccessControl->check([$name, $feature, 'index'])) {
                $featureUrl = array_merge($urlBase, ['action' => $feature]);
                $tabElements[$feature] = [
                    'url' => $featureUrl,
                    'text' => $action
                ];
            }
        }

        return $tabElements;
    }
}
