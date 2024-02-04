<?php

namespace Health\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\Event\Event;

class HealthBehavior extends Behavior
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.beforeAction'] = ['callable' => 'beforeAction', 'priority' => 100];
        return $events;
    }

    public function beforeAction(Event $event)
    {
        $controller = $this->_table->controller;
        $model = $this->_table;
        $pluginName = $controller->getPlugin();
        $controllerName = $controller->getName();
        $institutionId = $this->getInstitutionID();
        $userId = $this->getUserID();

        $otherTabElements = $this->getHealthTabElements(
            $pluginName,
            $controllerName,
            $userId,
            $institutionId
        );
        if ($controller->AccessControl->check([$controllerName, 'Healths', 'index'])) {
            $tabElements['Healths'] = [
                'url' => ['plugin' => $pluginName, 'controller' => $controllerName, 'action' => 'Healths'],
                'text' => __('Overview')
            ];
        }

        if ($controller->AccessControl->check([$controllerName, 'HealthAllergies', 'index'])) {
            $tabElements['Allergies'] = [
                'url' => ['plugin' => $pluginName, 'controller' => $controllerName, 'action' => 'HealthAllergies'],
                'text' => __('Allergies')
            ];
        }

        if ($controller->AccessControl->check([$controllerName, 'HealthConsultations', 'index'])) {
            $tabElements['Consultations'] = [
                'url' => ['plugin' => $pluginName, 'controller' => $controllerName, 'action' => 'HealthConsultations'],
                'text' => __('Consultations')
            ];
        }

        if ($controller->AccessControl->check([$controllerName, 'HealthFamilies', 'index'])) {
            $tabElements['Families'] = [
                'url' => ['plugin' => $pluginName, 'controller' => $controllerName, 'action' => 'HealthFamilies'],
                'text' => __('Families')
            ];
        }

        if ($controller->AccessControl->check([$controllerName, 'HealthHistories', 'index'])) {
            $tabElements['Histories'] = [
                'url' => ['plugin' => $pluginName, 'controller' => $controllerName, 'action' => 'HealthHistories'],
                'text' => __('Histories')
            ];
        }

        if ($controller->AccessControl->check([$controllerName, 'HealthImmunizations', 'index'])) {
            $tabElements['Immunizations'] = [
                'url' => ['plugin' => $pluginName, 'controller' => $controllerName, 'action' => 'HealthImmunizations'],
                //'text' => __('Immunizations')
                'text' => __('Vaccinations')
            ];
        }

        if ($controller->AccessControl->check([$controllerName, 'HealthMedications', 'index'])) {
            $tabElements['Medications'] = [
                'url' => ['plugin' => $pluginName, 'controller' => $controllerName, 'action' => 'HealthMedications'],
                'text' => __('Medications')
            ];
        }

        if ($controller->AccessControl->check([$controllerName, 'HealthTests', 'index'])) {
            $tabElements['Tests'] = [
                'url' => ['plugin' => $pluginName, 'controller' => $controllerName, 'action' => 'HealthTests'],
                'text' => __('Tests')
            ];
        }

        if ($controllerName == 'Students' && $controller->AccessControl->check([$controllerName, 'StudentBodyMasses', 'index'])) {

            $tabElements['StudentBodyMasses'] = [
                'url' => ['plugin' => 'Student',
                    'institutionId' => $encodedInstitutionID,
                    'controller' => 'Students',
                    'action' => 'StudentBodyMasses'],
                'text' => __('Body Mass')
            ];
        } elseif ($controllerName == 'Staff' && $controller->AccessControl->check([$controllerName, 'StaffBodyMasses', 'index'])) {

            $tabElements['StaffBodyMasses'] = [
                'url' => ['plugin' => 'Staff',
                    'institutionId' => $encodedInstitutionID,
                    'controller' => 'Staff',
                    'action' => 'StaffBodyMasses'],
                'text' => __('Body Mass')
            ];
        } elseif ($controllerName == 'Directories' && $controller->AccessControl->check(['DirectoryBodyMasses', 'index'])) {
            $tabElements['BodyMasses'] = [
                'url' => ['plugin' => 'Directory', 'controller' => 'DirectoryBodyMasses', 'action' => 'index'],
                'text' => __('Body Mass')
            ];
        } elseif ($controllerName == 'Profiles' && $controller->AccessControl->check(['ProfileBodyMasses', 'index'])) {
            $tabElements['BodyMasses'] = [
                'url' => ['plugin' => 'Profile', 'controller' => 'ProfileBodyMasses', 'action' => 'index'],
                'text' => __('Body Mass')
            ];
        }

        if ($controllerName == 'Students' && $controller->AccessControl->check([$controllerName, 'StudentInsurances', 'index'])) {

            $tabElements['StudentInsurances'] = [
                'url' => ['plugin' => 'Student', 'institutionId' => $encodedInstitutionID, 'controller' => 'Students', 'action' => 'StudentInsurances'],
                'text' => __('Insurances')
            ];
        } elseif ($controllerName == 'Staff' && $controller->AccessControl->check([$controllerName, 'StaffInsurances', 'index'])) {
            /*$tabElements['StaffInsurances'] = [
                'url' => ['plugin' => 'Staff', 'institutionId' => $params, 'controller' => 'Staff', 'action' => 'StaffInsurances'],
                'text' => __('Insurances'),
                'class' => 'tab-active'
            ];*/
            /*POCOR-6311 Starts*/
            $tabElements['Insurances'] = [
                'url' => ['plugin' => 'Institution', 'institutionId' => $encodedInstitutionID, 'controller' => 'StaffInsurances', 'action' => 'index'],
                'text' => __('Insurances')
            ];
            /*POCOR-6311 Ends*/
        } elseif ($controllerName == 'Directories' && $controller->AccessControl->check(['DirectoryInsurances', 'index'])) {
            $tabElements['Insurances'] = [
                'url' => ['plugin' => 'Directory', 'controller' => 'DirectoryInsurances', 'action' => 'index'],
                'text' => __('Insurances')
            ];
        } elseif ($controllerName == 'Profiles' && $controller->AccessControl->check(['ProfileInsurances', 'index'])) {
            $tabElements['Insurances'] = [
                'url' => ['plugin' => 'Profile', 'controller' => 'ProfileInsurances', 'action' => 'index'],
                'text' => __('Insurances')
            ];
        }
//        echo ('FullTabElements<pre>' . print_r($tabElements, true) . '</pre>');
//        die('FullTabElements<pre>' . print_r($otherTabElements, true) . '</pre>');
        foreach ($tabElements as &$n) {
            if (isset($n['url'])) {
                if ($encodedInstitutionID) {
                    $n['url']['institutionId'] = $encodedInstitutionID;
                }
            }
        }
        $tabElements = $otherTabElements;
        /*POCOR-6307 Starts*/
        $modelName = $model->getAlias();
//        if ($controllerName == 'Staff' && $model->getAlias() == 'UserInsurances') {
//            $modelName = 'StaffInsurances';
//        } elseif ($controllerName == 'Students' && $model->getAlias() == 'UserBodyMasses') {
//            $modelName = 'StudentBodyMasses';
//        } elseif ($controllerName == 'Students' && $model->getAlias() == 'UserInsurances') {
//            $modelName = 'StudentInsurances';
//        }
        /*POCOR-6307 Ends*/
        $tabElements = $controller->TabPermission->checkTabPermission($tabElements);
        $controller->set('tabElements', $tabElements);
        $controller->set('selectedAction', $modelName);
    }

    private function getInstitutionID()
    {
        $model = $this->_table;
        $institutionID = $model->getQueryString('institution_id');
        return $institutionID;
    }

    private function getUserID()
    {
        $model = $this->_table;
        $userID = $model->getQueryString('security_user_id');
        if ($userID == null) {
            $model->getQueryString('user_id');
        }
        return $userID;
    }

    /**
     * @param string $pluginName
     * @param string $controllerName
     * @param null $userId
     * @param null $institutionId
     * @return array
     */

    private function getHealthTabElements(string $pluginName, string $controllerName, $userId = null, $institutionId = null): array
    {
        $tabElements = [
            'Healths' => ['text' => __('Overview')],
            'HealthAllergies' => ['text' => __('Allergies')],
            'HealthConsultations' => ['text' => __('Consultations')],
            'HealthFamilies' => ['text' => __('Families')],
            'HealthHistories' => ['text' => __('Histories')],
            'HealthImmunizations' => ['text' => __('Immunizations')],
            'HealthMedications' => ['text' => __('Medications')],
            'HealthTests' => ['text' => __('Tests')],
            'HealthBodyMasses' => ['text' => __('Body Mass')],
            'HealthInsurances' => ['text' => __('Insurances')]
        ];
        $params = ['user_id' => $userId];
        if ($institutionId != null) {
            $params['institution_id'] = $institutionId;
        }
        $model = $this->_table;

        $queryString = $model->paramsEncode($params);
        $newTabElements = [];
        foreach ($tabElements as $action => &$obj) {
            $modelName = $action;
            if (strlen($action) > 7) {
                $modelName = str_replace('Health', "", $action);
            }
            $firstURL = [
                'plugin' => $pluginName,
                'controller' => $pluginName . $action,
                'action' => 'index',
                0 => $queryString
            ];
            $secondURL = [
                'plugin' => $pluginName,
                'controller' => $controllerName,
                'action' => $action,
                0 => 'index',
                1 => $queryString,
            ];
            if ($institutionId != null) {
                $firstURL = [
                    'plugin' => $pluginName,
                    'controller' => $pluginName . $action,
                    'action' => 'index',
                    0 => $queryString
                ];
                $secondURL = [
                    'plugin' => $pluginName,
                    'controller' => $controllerName,
                    'action' => $action,
                    0 => 'index',
                    0 => $queryString
                ];
            }
            if ($action == 'Insurances' || $action == 'BodyMasses') {
                $obj['url'] = $firstURL;
            } else {
                $obj['url'] = $secondURL;
            }
            $newTabElements[$modelName] = $obj;
        }
        return $newTabElements;
    }


}
