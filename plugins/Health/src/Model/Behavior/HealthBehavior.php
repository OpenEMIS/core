<?php

namespace Health\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

class HealthBehavior extends Behavior
{
    //POCOR-9718: set true when the current add/edit form depends on an empty
    //Health lookup; consumed by onGetFormButtons to strip the footer Save button.
    private bool $blockedByEmptyLookup = false;

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
        //POCOR-9718: guard empty lookup tables — block save with a user-facing alert so
        //the user does not waste time filling an unsavable form.
        $events['ControllerAction.Model.add.afterAction']  = ['callable' => 'guardEmptyLookups', 'priority' => 90];
        $events['ControllerAction.Model.edit.afterAction'] = ['callable' => 'guardEmptyLookups', 'priority' => 90];
        $events['ControllerAction.Model.onGetFormButtons'] = ['callable' => 'stripSaveOnBlocked', 'priority' => 90];
        //POCOR-9718: require a value in every non-nullable Health lookup select, so submitting
        //the form without choosing shows a clear inline "select a value" message instead of a 404.
        $events['Model.buildValidator'] = ['callable' => 'buildValidator', 'priority' => 90];
        return $events;
    }

    //POCOR-9718: a non-nullable belongsTo lookup left on "--Select--" used to submit an empty FK
    //and fall through to a 404; now it fails validation with a user-facing message and the field
    //renders the required asterisk. Scoped to Health.* lookups whose column is NOT NULL, so genuinely
    //optional fields are untouched. Centralised here instead of repeating in each Health table.
    public function buildValidator(EventInterface $event, Validator $validator, string $name)
    {
        $schema = $this->_table->getSchema();
        foreach ($this->_table->associations() as $assoc) {
            if ($assoc->type() !== 'manyToOne') {
                continue;
            }
            if (strpos((string)$assoc->getClassName(), 'Health.') !== 0) {
                continue;
            }
            $foreignKey = $assoc->getForeignKey();
            if (!$schema->hasColumn($foreignKey) || !empty($schema->getColumn($foreignKey)['null'])) {
                continue;
            }
            $validator->notEmptyString($foreignKey, __('Please select a value to save.'));
        }
    }

    //POCOR-9718: stop the default redirect and emit one carrying pass[1].
    //Payload mirrors getHealthTabElements() so the index page sees the same encoded context
    //the user arrived with.
    public function forcePassOnRedirect(EventInterface $event, Entity $entity, ArrayObject $data)
    {
        //POCOR-9718: only force the redirect on a genuine successful save. add.afterSave also
        //fires when the save was rejected (e.g. a required lookup left on "--Select--"); without
        //this guard we would redirect to index and hide the inline field error, leaving the user
        //with only a generic "record not added" message instead of "Please select a value to save".
        if ($entity->hasErrors() || $entity->isNew()) {
            return;
        }

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

    //POCOR-9718: on form open, if any Health lookup table the form depends on
    //is empty, raise an i18n alert AND disable Save — user gets the warning before
    //typing anything, instead of a generic FK violation at submit time.
    public function guardEmptyLookups(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $missing = $this->collectMissingLookups();
        if (empty($missing)) {
            return;
        }

        $this->blockedByEmptyLookup = true;

        $controller = $this->_table->controller;
        if (isset($controller->Alert)) {
            $controller->Alert->error(
                __('Cannot save: the following option(s) are not configured: {0}. Please ask your system administrator to add them.',
                    [implode(', ', $missing)]),
                ['type' => 'string', 'reset' => true]
            );
        }

        if (isset($extra['toolbarButtons']['save'])) {
            unset($extra['toolbarButtons']['save']);
        }
    }

    //POCOR-9718: drop the footer Save button when guardEmptyLookups flagged the form.
    //Fires from ControllerActionHelper::getFormButtons during render.
    public function stripSaveOnBlocked(EventInterface $event, ArrayObject $buttons)
    {
        if (!$this->blockedByEmptyLookup) {
            return;
        }
        foreach ($buttons as $i => $btn) {
            if (isset($btn['attr']['value']) && $btn['attr']['value'] === 'save') {
                $buttons->offsetUnset($i);
            }
        }
    }

    //POCOR-9718: collect humanized labels of empty Health-plugin lookup tables
    //that this form's belongsTo associations depend on. Non-Health and user-ish
    //associations are skipped.
    private function collectMissingLookups(): array
    {
        $missing = [];
        foreach ($this->_table->associations() as $assoc) {
            if ($assoc->type() !== 'manyToOne') {
                continue;
            }
            $className = (string)$assoc->getClassName();
            if (strpos($className, 'Health.') !== 0) {
                continue;
            }
            if (!$assoc->getTarget()->exists([])) {
                $missing[] = __(Inflector::humanize(Inflector::underscore($assoc->getAlias())));
            }
        }
        return $missing;
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
