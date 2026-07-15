<?php

namespace Alert\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Log\Log;
use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;
use Cake\Http\ServerRequest;
use Cake\Datasource\ConnectionManager;

class AlertRulesTable extends ControllerActionTable
{
    use OptionsTrait;

    const ASSIGN_TO_ASSIGNEE = -1;
    const ASSIGNEE_ROLE = 'Current Workflow Assignee';

    private $alertTypeFeatures = [];
    private $featureList = [];

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->belongsToMany('SecurityRoles', [
            'className' => 'Security.SecurityRoles',
            'joinTable' => 'alerts_roles',
            'foreignKey' => 'alert_rule_id',
            'targetForeignKey' => 'security_role_id',
            'through' => 'Alert.AlertsRoles',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->addBehavior('OpenEmis.Section');
        $this->addBehavior('Alert.AlertRuleStudentAttendance');
        //$this->addBehavior('Alert.AlertRuleAttendance');
        $this->addBehavior('Alert.AlertRuleLicenseRenewal');
        $this->addBehavior('Alert.AlertRuleLicenseValidity');
        $this->addBehavior('Alert.AlertRuleRetirementWarning');
        $this->addBehavior('Alert.AlertRuleStaffEmployment');
        $this->addBehavior('Alert.AlertRuleStaffLeave');
        $this->addBehavior('Alert.AlertRuleStaffType');
        $this->addBehavior('Alert.AlertRuleScholarshipApplication');
        $this->addBehavior('Alert.AlertRuleScholarshipDisbursement');
        $this->addBehavior('Alert.AlertRuleCaseEscalation');//POCOR-7642
        $this->addBehavior('Alert.AlertRuleSystemUpdates');//POCOR-7642
        $this->addBehavior('Alert.AlertRuleStudentAdmission');//POCOR-8869
        $this->addBehavior('Alert.AlertRuleStudentEnrolment');//POCOR-8286
        $this->addBehavior('Alert.AlertRuleStudentStatus'); //POCOR-9509: register StudentStatus alert feature
    }

    public function validationDefault(Validator $validator): Validator
    {

        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this); // POCOR-8286
        return $validator
            ->notEmptyArray('method')
            ->notEmptyArray('security_roles')
            ->notEmptyString('subject')
            ->notEmptyString('message')
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ]);
    }

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['submit']) && $data['submit'] == 'save') {

            // POCOR-8286 start
            if (isset($data['method'])
                && !empty($data['method'])
                && !empty($data['method']['_ids'])
            ) {
                $data['method'] = implode(',', array_map('trim', $data['method']['_ids']));
            }else{
                $data['method'] = null;
            }
//            dd($data['security_roles']);
            if (isset($data['security_roles'])
                && !empty($data['security_roles'])
                && !empty($data['security_roles']['_ids'])
            ) {
            }else {
//                $feature = $data['feature'];
//                dd($feature);
//                if ($feature != 'ScholarshipApplication') {
                    $data['security_roles'] = null;
//                }
            }
            $validator = $this->getValidator();
            $validator->notEmptyArray('method');
            $validator->notEmptyArray('security_roles');
            // POCOR-8286 end
            if (isset($data['feature']) && !empty($data['feature'])) {
                $alertRuleTypes = $this->getAlertRuleTypes();
                $feature = $data['feature']; // POCOR-9391 start
                if($feature == 'StudentAbsence') {
                    $feature = 'StudentAttendance';
                }
                $thresholdConfig = $alertRuleTypes[$feature]['threshold']; // POCOR-9391 end
                if (!empty($thresholdConfig)) {
                    $thresholdArray = [];
                    foreach ($thresholdConfig as $field => $attr) {
                        if (isset($attr['type']) && $attr['type'] == 'chosenSelect') {
                            $thresholdArray[$field] = $data[$field]['_ids'];
                        } else {
                            $thresholdArray[$field] = $data[$field];
                        }
                    }
                    $data['threshold'] = !empty($thresholdArray) ? json_encode($thresholdArray, JSON_UNESCAPED_UNICODE) : '';
                }
            }
        }
    }

    public function getAlertRuleTypes()
    {
        return $this->alertTypeFeatures;
    }

    public function afterSaveCommit(EventInterface $event, Entity $entity)
    {
        if ($entity->isNew()) {
//            $feature = $entity->feature;

//            if (in_array($feature, ['ScholarshipApplication'])) {
//                $AlertRoles = TableRegistry::getTableLocator()->get('Alert.AlertsRoles');
//
//                $alertRoleData = [
//                    'alert_rule_id' => $entity->id,
//                    'security_role_id' => self::ASSIGN_TO_ASSIGNEE
//                ];
//
//                $alertRoleEntity = $AlertRoles->newEntity($alertRoleData);
//                if ($AlertRoles->save($alertRoleEntity)) {
//                } else {
//                    Log::write('error', 'Error saving roles to assigee.');
//                    Log::write('error', $alertRoleEntity);
//                }
//            }
        }
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('message', ['visible' => false]);
        $this->field('enabled', ['options' => $this->getSelectOptions('general.yesno')]);
        $this->field('security_role_ids', ['after' => 'method']);
//        $this->field('security_moles', ['before' => 'subject']);

        // element control
        //POCOR-7558 start
        $logsTable = TableRegistry::getTableLocator()->get('Alert.AlertLogs');
        $featureOptions = $logsTable->getFeatureOptions();
        array_shift($featureOptions);
        //POCOR-7558 end
        if (!empty($featureOptions)) {
            $featureOptions = ['-1' => __('All Features')] + $featureOptions;
        }
        $request = $this->request;
        $selectedFeature = $this->queryString('feature', $featureOptions);
        // $extra['selectedFeature'] = $selectedFeature;
        $extra['selectedFeature'] = $featureOptions;

        $extra['elements']['control'] = [
            'name' => 'Alert/controls',
            'data' => [
                'featureOptions' => $featureOptions,
                'selectedFeature' => $selectedFeature,
            ],
            'options' => [],
            'order' => 3
        ];
        // end element control

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Administration', 'AlertRules', 'Communications');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188
    }

    public function getFeatureOptions()
    {
        // POCOR-9509: Exclude alerts without Laravel command implementations
        $nonImplemented = [
            'StaffAttendance',
        ];

        $featureOptions = [];
        foreach ($this->alertTypeFeatures as $key => $obj) {
            if (in_array($obj['feature'], $nonImplemented, true)) {
                continue;
            }
            $featureOptions[$obj['feature']] = __(Inflector::humanize(Inflector::underscore($obj['feature'])));
        }

        ksort($featureOptions);
        return $featureOptions;
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $params = $this->request->getQuery();
        if(empty($params)){
            $extra['options']['direction'] = 'asc';
            $extra['options']['limit'] = 20;
            $extra['options']['sort'] = 'feature';
        }
        $selectedFeature = $this->request->getQuery('feature');
        if ($selectedFeature != -1 && !empty($selectedFeature)) {
            $query->where(['feature' => $selectedFeature]);
        }

    }

    public function editOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->extractThresholdValuesFromEntity($entity);
    }

    public function extractThresholdValuesFromEntity(Entity $entity)
    {
        $thresholdArray = json_decode($entity->threshold, true);

        if (is_array($thresholdArray)) {
            $alertTypeDetails = $this->getAlertTypeDetailsByFeature($entity->feature);
            $thresholdConfig = $alertTypeDetails[$entity->feature]['threshold'];
            foreach ($thresholdArray as $field => $value) {
                $entity->{$field} = $value;

                if (array_key_exists($field, (array)$thresholdConfig) && isset($thresholdConfig[$field]['type'])) {
                    $fieldType = $thresholdConfig[$field]['type'];
                    // for threshold with type chosenSelect type
                    if ($fieldType == 'chosenSelect') {
                        $lookupModel = $thresholdConfig[$field]['lookupModel'];
                        if (isset($lookupModel)) {//POCOR-7462
                            $Model = TableRegistry::getTableLocator()->get($lookupModel);
                            if (is_array($value)) {
                                $entity->{$field} = [];
                                foreach ($value as $modelId) {
                                    $entity->{$field}[] = $Model->get($modelId);
                                }
                            }
                        }
                        $workflowOptions = ['Cases.workflow_steps',
                            'StudentAdmission.workflow_steps',
                            'StudentEnrolment.workflow_steps',
                            ];

                        if (in_array($thresholdConfig[$field]['options'], $workflowOptions, true)) {
                            if (is_array($value)) {
                                $Model = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
                                $entity->{$field} = [];

                                foreach ($value as $modelId) {
                                    //POCOR-9732[START]
                                    if (!empty($modelId)) {
                                        $record = $Model->find()
                                            ->where([$Model->getPrimaryKey() => $modelId])
                                            ->first();

                                        if ($record) {
                                            $entity->{$field}[] = $record;
                                        }
                                    }
                                    // $entity->{$field}[] = $Model->get($modelId);
                                    //POCOR-9732[END]
                                }
                            }
                        }

                    }
                }
            }
        }

    }

    public function getAlertTypeDetailsByFeature($feature)
    {
        $alertTypeDetails = [];
        foreach ($this->alertTypeFeatures as $key => $obj) {
            if ($obj['feature'] == $feature) {
                $alertTypeDetails[$obj['feature']] = $obj;
            }
        }

        return $alertTypeDetails;
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
    }

    public function setupFields(EventInterface $event, Entity $entity)
    {


        // Rule setting section
        $this->field('rule_setup', ['type' => 'section']);
        $this->field('feature', ['type' => 'select', 'entity' => $entity]);
        $this->field('enabled', ['type' => 'select']);
        //POCOR-8690[START]
        // $this->field('method', ['type' => 'readOnly', 'after' => 'threshold']);
        //POCOR-9509: start - fix method field required attribute — was nested incorrectly, now uses attr key
        $this->field('method', [
            'after' => 'threshold',
            'entity' => $entity,
            'attr' => ['required' => true],
        ]);
        //POCOR-9509: end
        //POCOR-8690[END]
        $this->field('security_roles', ['after' => 'method',
            'entity' => $entity,
            ['attr' => ['required' => true]]]);

        $this->field('threshold', ['after' => 'security_roles', 'entity' => $entity]);

        // Alert section
        $this->field('alert_content', ['type' => 'section', 'after' => 'threshold']);
        $this->field('alert_features', ['type' => 'custom_criterias', 'after' => 'message']);

        if ($entity->has('feature') && !empty($entity->feature)) {
            $event = $this->dispatchEvent('AlertRule.' . $entity->feature . '.SetupFields', [$entity], $this);
            if ($event->isStopped()) {
                return $event->getResult();
            }
        }
//        $this->ControllerAction->addField('busy');
//        dd($entity);
    }

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $errors = $entity->getErrors();
        if (!empty($errors)) {
            $errorsMessage = "";
            foreach ($errors as $fieldName => $error){
                $fieldName = Inflector::humanize(Inflector::underscore($fieldName));
                foreach ($error as $key=>$errorMessage){
                    $errorsMessage .= "$fieldName: $errorMessage<br />";
                }
            }
            $this->Alert->error("$errorsMessage", ['type' => 'string', 'reset' => true]);
        }
    }
    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
        $this->field('alert_features', ['visible' => false]);
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query)
    {
        $query->contain(['SecurityRoles']);
    }

    public function getAlertTypeDetailsByAlias($alias)
    {
        return $this->alertTypeFeatures[$alias];
    }

    public function getThresholdType($feature)
    {
        $type = '';
        if (!empty($feature)) {
            $alertTypeDetails = $this->getAlertTypeDetailsByFeature($feature);
            if (isset($alertTypeDetails[$feature]['threshold']['type'])) {
                $type = $alertTypeDetails[$feature]['threshold']['type'];
            }
        }

        return $type;
    }

    public function onGetFeature(EventInterface $event, Entity $entity)
    {
        $this->featureList[$entity->id] = $entity->feature;
        return Inflector::humanize(Inflector::underscore($entity->feature));
    }

    public function onGetEnabled(EventInterface $event, Entity $entity)
    {
        return $entity->enabled == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    //POCOR-8690[START]

    public function onGetThreshold(EventInterface $event, Entity $entity)
    {
        // temporary solution
        $origEntity = $this->get($entity->id);
        if ($origEntity->has('feature') && !empty($origEntity->feature)) {
            $event = $this->dispatchEvent('AlertRule.onGet.' . $origEntity->feature . '.Threshold', [$origEntity], $this);
            if ($event->isStopped()) {
                return $event->getResult();
            }
            if (!empty($event->getResult())) {
                return $event->getResult();
            }
        }
    }

    //POCOR-8690[END]

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $featureOptions = $this->getFeatureOptions();
        if ($action == 'add') {
            $attr['options'] = $featureOptions;
            $attr['onChangeReload'] = 'changeFeature';
        } else if ($action == 'edit') {
            $entity = $attr['entity'];

            $attr['type'] = 'readonly';
            $attr['value'] = $entity->feature;
            $attr['attr']['value'] = $featureOptions[$entity->feature];
        }

        return $attr;
    }

    public function onUpdateFieldMethod(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            //POCOR-9509: start - always mark method as required for add/edit
            $attr['attr']['required'] = true;
            //POCOR-9509: end
            // POCOR-8286 start
            if ($entity->feature) {
                $methods = $this->getMethod($entity->feature);

                if (!is_array($methods)) {
                    $attr['type'] = 'readonly';
                } else {
                    $attr['type'] = 'chosenSelect';
                    $attr['options'] = array_combine($methods, $methods);

                    // NEW: parse the string into array
                    if (!empty($entity->method)) {
                        $attr['value'] = explode(',', $entity->method);
                        $attr['attr']['value'] = $attr['value'];
                    }
                }
                // POCOR-8286 end
            }

        }

        return $attr;
    }

    public function getMethod($feature)
    {
        $method = '';
        if (!empty($feature)) {
            $alertTypeDetails = $this->getAlertTypeDetailsByFeature($feature);
            $method = $alertTypeDetails[$feature]['method'];
        }

        return $method;
    }

    public function addEditOnChangeFeature(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data)) {
            $feature = $data[$this->getAlias()]['feature'];
            $data[$this->getAlias()]['method'] = $this->getMethod($feature);
        }
    }

    public function onUpdateFieldEnabled(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $attr['visible'] = false;
        } else if ($action == 'edit') {
            $attr['select'] = false;
            $attr['options'] = $this->getSelectOptions('general.yesno');
        }

        return $attr;
    }

    public function onUpdateFieldSecurityRoles(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        // POCOR-8286 start
        $attr = $this->processAlertRoleAttributes($attr, $action);
        $attr['attr']['required'] = true;
        return $attr;
    }

    public function processAlertRoleAttributes(array $attr, string $action): array
    {
        $entity = $attr['entity'];
        if(!$entity){
            return [];
        }

        $feature = $entity->get('feature') ?? $this->request->getData('AlertRules.feature');

        if (!in_array($action, ['add', 'edit'], true)) {
            return $attr;
        }

//        if ($feature === 'ScholarshipApplication') {
//            return $this->assignToAssignee($attr);
//        }

        if ($feature === 'StudentAdmission') {
            return $this->assignToGuardianOnly($attr);
        }

        if ($feature === 'StudentEnrolment') {
            return $this->assignToGuardianOnly($attr);
        }

        if ($feature === 'StudentAttendance') { //POCOR-9509: restrict to class staff roles only
            return $this->assignToClassStaffOnly($attr);
        }

        return $this->assignToAllRoles($attr);
    }

//    private function assignToAssignee(array $attr): array
//    {
//        $attr['type'] = 'disabled';
//        $attr['value'] = self::ASSIGN_TO_ASSIGNEE;
//        $attr['attr']['value'] = __(self::ASSIGNEE_ROLE);
//        return $attr;
//    }

    // POCOR-8286 end

    private function assignToGuardianOnly(array $attr): array
    {
        $roleOptions = $this->getVisibleSecurityRoles();

        $filteredRoles = array_filter($roleOptions, function ($roleName) {
            return in_array($roleName, ['Guardian', 'Student'], true);
        });

// Apply translation after filtering
        $translatedRoles = array_map(function ($roleName) {
            return __($roleName);
        }, $filteredRoles);

        $attr['type'] = 'chosenSelect';
        $attr['options'] = $translatedRoles;

        return $attr;
    }

    //POCOR-9509: start - restrict StudentAttendance alert roles to class staff only
    private function assignToClassStaffOnly(array $attr): array
    {
        $roleOptions = $this->getVisibleSecurityRoles();

        $filteredRoles = array_filter($roleOptions, function ($roleName) {
            return in_array($roleName, ['Principal', 'Deputy Principal', 'Homeroom Teacher', 'Teacher'], true);
        });

        $translatedRoles = array_map(function ($roleName) {
            return __($roleName);
        }, $filteredRoles);

        $attr['type'] = 'chosenSelect';
        $attr['options'] = $translatedRoles;

        return $attr;
    }
    //POCOR-9509: end

    private function getVisibleSecurityRoles(): array
    {
        return $this->SecurityRoles
            ->find('list')
            ->select([
                $this->SecurityRoles->aliasField($this->SecurityRoles->getPrimaryKey()),
                $this->SecurityRoles->aliasField('name')
            ])
            ->find('visible')
            ->find('order')
            ->toArray();
    }

    private function assignToAllRoles(array $attr): array
    {
        $roleOptions = $this->getVisibleSecurityRoles();

        $filteredRoles = array_filter($roleOptions, function ($roleName) {
            return !in_array($roleName, ['Guardian', 'Student'], true);
        });

        $translatedRoles = array_map(function ($roleName) {
            return __($roleName);
        }, $filteredRoles);

        $attr['type'] = 'chosenSelect';
        $attr['options'] = $translatedRoles;

        return $attr;
    }

    public function onUpdateFieldThreshold(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $entity = $attr['entity'];
        if ($action == 'add') {
            $attr['type'] = 'hidden';
        } else if ($action == 'view' || $action == 'edit') {
            $attr['visible'] = false;
        }

        if ($entity->has('feature') && !empty($entity->feature)) {
            $feature = $entity->feature; // POCOR-9391 start
            if ($feature == 'StudentAbsence') {
                $feature = 'StudentAttendance';
            }
            $event = $this->dispatchEvent('AlertRule.UpdateField.' . $feature . '.Threshold', [$attr, $action, $request], $this);
            // POCOR-9391 end
            if ($event->isStopped()) {
                return $event->getResult();
            }
            if (!empty($event->getResult())) {
                $attr = $event->getResult();

            }
        }

        return $attr;
    }

    public function getSecurityRolesList(Entity $entity): string // POCOR-9391 start
    {
        if (!$entity->has('security_roles')) {
            $query = $this->find()
                ->where([$this->aliasField($this->getPrimaryKey()) => $entity->id])
                ->contain(['SecurityRoles']);

            $entity = $query->first();
        }

        $roles = [];

        if ($entity && $entity->has('security_roles')) {
            foreach ($entity->security_roles as $roleEntity) {
                $roles[] = $roleEntity->name;
            }
        }

        return !empty($roles)
            ? implode(', ', $roles)
            : __('No Role To Send Alert Selected');
    }

    public function onGetSecurityRoleIds(EventInterface $event, Entity $entity): string
    {
        return $this->getSecurityRolesList($entity);
    }

    public function onGetSecurityRoles(EventInterface $event, Entity $entity): string
    {
        return $this->getSecurityRolesList($entity);
    } // POCOR-9391 end
    public function onGetCustomCriteriasElement(EventInterface $event, $action, $entity, $attr, $options = [])
    {
        if ($action == 'add' || $action == 'edit') {
            $tableHeaders = [__('Keywords'), __('Remarks')];
            $tableCells = [];
            $fieldKey = 'alert_features';

            if (!empty($entity->feature)) {
                $featureKey = $entity->feature;
                $alertTypeDetails = $this->getAlertTypeDetailsByFeature($featureKey);
                $placeholder = $alertTypeDetails[$featureKey]['placeholder'];

                if (!empty($placeholder)) {
                    foreach ($placeholder as $placeholderKey => $placeholderObj) {
                        $rowData = [];
                        $rowData[] = __($placeholderKey);
                        $rowData[] = __($placeholderObj);

                        $tableCells[] = $rowData;
                    }
                }

                $attr['tableHeaders'] = $tableHeaders;
                $attr['tableCells'] = $tableCells;
            }

            return $event->getSubject()->renderElement('Alert/' . $fieldKey, ['attr' => $attr]);
        }
    }

    public function addAlertRuleType($newAlertRuleType, $_config)
    {
        $this->alertTypeFeatures[$newAlertRuleType] = $_config;
    }

    //POCOR-7558 start

    public function getLastRunDate()
    {
        //POCOR-8575[START]
        $connection = ConnectionManager::get('default');
        $connection->execute("DELETE FROM system_processes WHERE `status` = 3;");
        //POCOR-8575[END]
        $systemProcess = TableRegistry::getTableLocator()->get('SystemProcesses');
        $data = $systemProcess->find()->select([
            'name' => $systemProcess->aliasField('name'),
            'end_date' => $systemProcess->aliasField('end_date'),
        ])->group([$systemProcess->aliasField('name')])
            ->order([$systemProcess->aliasField('end_date') => 'DESC'])
            ->toArray();

        $result = [];
        foreach ($data as $key => $value) {
            if(!is_array($value)){
                $value = $value->toArray();
            }
            $result[$value['name']] = $value['end_date'];
        }
        return $result;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'feature':
                return __('Feature');
            case 'subject':
                return __('Subject');
            case 'status':
                return __('Status');
            case 'security_roles':
                return __('Security Roles');
            case 'security_role_ids':
                return __('Security Roles');
            case 'method':
                return __('Method');
            case 'threshold':
                return __('Threshold');
                return __('Enabled');
            case 'name':
                return __('Name');
            case 'enabled':
                return __('Enabled');
            case 'created':
                return __('Created On');
            case 'created_user_id':
                return __('Created By');
            case 'modified':
                return __('Modified On');
            case 'modified_user_id':
                return __('Modified By');
            case 'message':
                return __('Message');
            case 'condition':
                return __('Condition');
            case 'message':
                return __('Message');
            case 'category':
                return __('Category');
            case 'license_type':
                return __('License Type');
            case 'training_categories':
                return __('Training Category');
            case 'workflow_steps':
                return __('Workflow Step');
            case 'employment_type':
                return __('Employment Type');
            case 'staff_leave_type':
                return __('Staff Leave Type');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    ////POCOR-8341 start
    // public function onUpdateFieldMethod(EventInterface $event, array $attr, $action, ServerRequest $request)
    // {
    //     if ($action == 'add'||$action == 'edit') {
    //         $entity = $attr['entity'];
    //         if($entity->feature)
    //         {
    //         $attr['type'] = 'readonly';
    //         $attr['value'] = $this->getMethod($entity->feature);;
    //         $attr['attr']['value'] =$this->getMethod($entity->feature);;
    //         }

    //     }

    //     return $attr;
    // }
    //POCOR-8341 end
}
