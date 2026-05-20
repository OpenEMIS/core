<?php

namespace Health\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;

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
        //POCOR-9718: force encoded pass[1] on every Health post-save redirect — Procrustean exit.
        //Without this, ControllerAction's default redirect drops pass[1] and the index page
        //bare-redirects, ending in 404 (Student/Directory contexts).
        $events['ControllerAction.Model.add.afterSave']  = ['callable' => 'forcePassOnRedirect', 'priority' => 100];
        $events['ControllerAction.Model.edit.afterSave'] = ['callable' => 'forcePassOnRedirect', 'priority' => 100];
        return $events;
    }

    //POCOR-9718: stop the default redirect and emit one carrying pass[1].
    //Payload mirrors getHealthTabElements() so the index page sees the same encoded context
    //the user arrived with.
    public function forcePassOnRedirect(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        $payload = $this->buildContextPayload();
        if (empty($payload)) {
            return;
        }

        $action = $this->_table->url('index');
        $action[1] = $this->_table->paramsEncode($payload);

        $event->stopPropagation();
        return $this->_table->controller->redirect($action);
    }

    private function buildContextPayload(): array
    {
        $userId        = $this->positiveInt($this->_table->getUserID());
        $institutionId = $this->positiveInt($this->_table->getInstitutionID());
        $isProfile     = $this->_table->controller->getPlugin() === 'Profile';

        $payload = [];
        if ($userId !== null) {
            $payload['user_id']    = $userId;
            $payload['student_id'] = $userId;
            $payload['staff_id']   = $userId;
        }
        if ($institutionId !== null && !$isProfile) {
            $payload['institution_id'] = $institutionId;
        }
        return $payload;
    }

    private function positiveInt($value): ?int
    {
        return is_numeric($value) && (int)$value > 0 ? (int)$value : null;
    }

    public function beforeAction(EventInterface $event)
    {
        // POCOR-8074-6 Unified Tabs
        $controller = $this->_table->controller;
        $model = $this->_table;
        $pluginName = $controller->getPlugin();
        $controllerName = $controller->getName();
        $institutionId = $this->getInstitutionID();
        $userId = $this->getUserID();
        if(!$userId){
            //die('No!');
        }
        $otherTabElements = $this->getHealthTabElements(
            $pluginName,
            $controllerName,
            $userId,
            $institutionId
        );
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
        if (!$userID) {
            $userID = $model->getQueryString('user_id');
        }
        //POCOR-8653 start
        if (!$userID) {
            $userID = $model->getQueryString('id');
        }
        // POCOR-8653 end
        if(!$userID){
            $userID = $model->getQueryString();
            //die('userID<pre>' . print_r($userID, true) . '</pre>');
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
    // POCOR-8074-6 Unified Health Tabs
    private function getHealthTabElements(string $pluginName, string $controllerName, $userId = null, $institutionId = null): array
    {
        $tabElements = [
            'Healths' => ['text' => __('Overview')],
            'HealthAllergies' => ['text' => __('Allergies')],
            'HealthConsultations' => ['text' => __('Consultations')],
            'HealthFamilies' => ['text' => __('Families')],
            'HealthHistories' => ['text' => __('Histories')],
            'HealthImmunizations' => ['text' => __('Vaccinations')],
            'HealthMedications' => ['text' => __('Medications')],
            'HealthTests' => ['text' => __('Tests')],
            'HealthBodyMasses' => ['text' => __('Body Mass')],
            'HealthInsurances' => ['text' => __('Insurances')]
        ];
        $params = ['user_id' => $userId, 'student_id' => $userId];
        if ($institutionId != null) {
            $params['institution_id'] = $institutionId;
        }
        $params['staff_id'] =  $userId;
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
                //todo Links With Institution ID
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
                    1 => $queryString
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

    // POCOR-8074-6 End
}
