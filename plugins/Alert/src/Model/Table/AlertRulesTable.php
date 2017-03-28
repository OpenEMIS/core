<?php
namespace Alert\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Log\Log;

use App\Model\Traits\OptionsTrait;
use App\Model\Table\ControllerActionTable;

class AlertRulesTable extends ControllerActionTable
{
    use OptionsTrait;

    private $alertTypeFeatures = [
        'InstitutionStudentAbsences' => [
            'feature' => 'Attendance',
            'name' => 'Student Absent',
            'method' => 'Email',
            'threshold' => ['type' => 'integer'],
            'placeholder' => [
                '${total_days}' => 'Total number of unexcused absence.',
                '${threshold}' => 'Threshold value.',
                '${user.openemis_no}' => 'Student OpenEMIS number.',
                '${user.first_name}' => 'Student first name.',
                '${user.middle_name}' => 'Student middle name.',
                '${user.third_name}' => 'Student third name.',
                '${user.last_name}' => 'Student last name.',
                '${user.preferred_name}' => 'Student preferred name.',
                '${user.email}' => 'Student email.',
                '${user.address}' => 'Student address.',
                '${user.postal_code}' => 'Student postal code.',
                '${user.date_of_birth}' => 'Student date of birth.',
                '${user.identity_number}' => 'Student identity number.',
                // '${user.photo_name}' => 'Student photo name.',
                // '${user.photo_content}' => 'Student photo content.',
                '${user.main_identity_type.name}' => 'Student identity type.',
                '${user.main_nationality.name}' => 'Student nationality.',
                '${user.gender.name}' => 'Student gender.',
                '${institution.name}' => 'Institution name.',
                '${institution.code}' => 'Institution code.',
                '${institution.address}' => 'Institution address.',
                '${institution.postal_code}' => 'Institution postal code.',
                '${institution.contact_person}' => 'Institution contact person.',
                '${institution.telephone}' => 'Institution telephone number.',
                '${institution.fax}' => 'Institution fax number.',
                '${institution.email}' => 'Institution email.',
                '${institution.website}' => 'Institution website.',
            ]
        ],
        'Licenses' => [
            'feature' => 'LicenseValidity',
            'name' => 'License Validity',
            'method' => 'Email',
            'threshold' => [
                'value' => [
                    'type' => 'integer',
                    'field' => 'value'
                ],
                'operand_id' => [
                    'type' => 'select',
                    'field' => 'operand',
                    'option' => 'before_after'
                ],
                'license_type_id' => [
                    'type' => 'select',
                    'field' => 'license_type',
                    'lookupModel' => 'FieldOption.LicenseTypes'
                ],
            ],
            'placeholder' => [
                '${threshold}' => 'Threshold value.',
                '${license_type.name}' => 'License type.',
                '${license_number}' => 'License number.',
                '${issue_date}' => 'Issue date.',
                '${expiry_date}' => 'Expiry date.',
                '${issuer}' => 'Issuer.',
                '${user.openemis_no}' => 'Student OpenEMIS number.',
                '${user.first_name}' => 'Student first name.',
                '${user.middle_name}' => 'Student middle name.',
                '${user.third_name}' => 'Student third name.',
                '${user.last_name}' => 'Student last name.',
                '${user.preferred_name}' => 'Student preferred name.',
                '${user.email}' => 'Student email.',
                '${user.address}' => 'Student address.',
                '${user.postal_code}' => 'Student postal code.',
                '${user.date_of_birth}' => 'Student date of birth.',
                '${institution.name}' => 'Institution name.',
                '${institution.code}' => 'Institution code.',
                '${institution.address}' => 'Institution address.',
                '${institution.postal_code}' => 'Institution postal code.',
                '${institution.contact_person}' => 'Institution contact person.',
                '${institution.telephone}' => 'Institution telephone number.',
                '${institution.fax}' => 'Institution fax number.',
                '${institution.email}' => 'Institution email.',
                '${institution.website}' => 'Institution website.',
            ]
        ],
    ];

    public function initialize(array $config)
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
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ])
            ->add('value', 'ruleRange', [
                'rule' => ['range', 1, 30],
                'on' => function ($context) { //validate when only value is not empty
                    return isset($context['data']['value']);
                }
            ])
            ->add('threshold', 'ruleRange', [
                'rule' => ['range', 1, 30],
                'on' => function ($context) { //validate when only threshold contain type and not empty
                    $feature = $context['data']['feature'];
                    $alertTypeDetails = $this->getAlertTypeDetailsByFeature($feature);
                    $threshold = $alertTypeDetails[$feature]['threshold'];

                    if (array_key_exists('type', $threshold)) {
                        return isset($context['data']['threshold']);
                    }

                    return false;
                }
            ])
            ;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('enabled', ['options' => $this->getSelectOptions('general.yesno')]);
        $this->field('security_roles', ['after' => 'method']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('message', ['visible' => false]);

        // element control
        $featureOptions = $this->getFeatureOptions();
        if (!empty($featureOptions)) {
            $featureOptions = ['AllFeatures' => 'All Features'] + $featureOptions;
        }

        $selectedFeature = $this->queryString('feature', $featureOptions);
        $extra['selectedFeature'] = $selectedFeature;

        $extra['elements']['control'] = [
            'name' => 'Alert/controls',
            'data' => [
                'featureOptions'=>$featureOptions,
                'selectedFeature'=>$selectedFeature,
            ],
            'options' => [],
            'order' => 3
        ];
        // end element control
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $selectedFeature = $extra['selectedFeature'];

        if ($selectedFeature != 'AllFeatures') {
            $query->where(['feature' => $selectedFeature]);
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
        $this->field('alert_features', ['type' => 'custom_criterias', 'after' => 'message']);

        if ($this->action == 'add') {
            $this->field('enabled', ['visible' => false]);
        } else if ($this->action == 'edit') {
            $this->field('enabled', ['select' => false]);
            $this->field('feature', ['type' => 'readOnly']);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['SecurityRoles']);
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data['feature'])) {
            $feature = $data['feature'];
            $alertTypeDetails = $this->getAlertTypeDetailsByFeature($feature);

            if (!empty($alertTypeDetails[$feature]['threshold']) && !array_key_exists('type', $alertTypeDetails[$feature]['threshold'])) {
                $threshold = $alertTypeDetails[$feature]['threshold'];
                $records = [];

                foreach ($threshold as $key => $obj) {
                    $records[$key] = !empty($data[$obj['field']]) ? $data[$obj['field']] : null;
                }

                $data['threshold'] = json_encode($records, JSON_UNESCAPED_UNICODE);
            }
        }
    }

    public function getAlertTypeDetailsByFeature($feature)
    {
        $alertTypeDetails = [];
        foreach ($this->alertTypeFeatures as $key => $obj) {
            if ($obj['feature'] == $feature) {
                $alertTypeDetails[$obj['feature']] = $obj;
                $alertTypeDetails[$obj['feature']]['model'] = $key;
            }
        }

        return $alertTypeDetails;
    }

    public function getAlertTypeDetailsByAlias($alias)
    {
        return $this->alertTypeFeatures[$alias];
    }

    public function getFeatureOptions()
    {
        $featureOptions = [];
        foreach ($this->alertTypeFeatures as $key => $obj) {
            $featureOptions[$obj['feature']] = __(Inflector::humanize(Inflector::underscore($obj['feature'])));
        }

        return $featureOptions;
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

    public function onGetEnabled(Event $event, Entity $entity)
    {
        return $entity->enabled == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    public function onGetFeature(Event $event, Entity $entity)
    {
        return Inflector::humanize(Inflector::underscore($entity['feature']));
    }

    public function onGetThreshold(Event $event, Entity $entity)
    {
        // due to controllerActionHelper have escapeHtmlSpecialCharacters, unable to json_decode
        $HtmlField = $event->subject()->HtmlField;
        $threshold = $HtmlField->decodeEscapeHtmlEntity($entity->threshold);

        $thresholdData = json_decode($threshold, true);

        return $thresholdData['value'];
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->getFeatureOptions();
            $attr['onChangeReload'] = 'changeFeature';
        }

        return $attr;
    }

    public function addEditOnChangeFeature(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data)) {
            $feature = $data[$this->alias()]['feature'];
            $data[$this->alias()]['method'] = $this->getMethod($feature);
        }
    }

    public function onUpdateFieldSecurityRoles(Event $event, array $attr, $action, Request $request)
    {
        switch ($action) {
            case 'add':
            case 'edit':
                $roleOptions = $this->SecurityRoles
                    ->find('list')
                    ->select([$this->SecurityRoles->aliasField($this->SecurityRoles->primaryKey()), $this->SecurityRoles->aliasField('name')])
                    ->find('visible')
                    ->find('order')
                    ->toArray();

                $attr['type'] = 'chosenSelect';
                $attr['options'] = $roleOptions;
                break;
        }

        return $attr;
    }

/********************************************************************************
**                               Threshold                                     **
*********************************************************************************/

    public function onUpdateFieldThreshold(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($request->data[$this->alias()]['feature'])) {
                $feature = $request->data[$this->alias()]['feature'];
                $type = $this->getThresholdType($feature);
                if ($type == 'integer') {
                    $attr['attr']['min'] = 1;
                    $attr['attr']['max'] = 30;
                }
            } else {
                $type = 'hidden';
            }

            $attr['type'] = $type;
        } else if ($action == 'edit' || $action == 'view') {
            if (!empty($this->paramsPass(0))) {
                $alertRuleId = $this->paramsDecode($this->paramsPass(0));
                $entity = $this->get($alertRuleId);

                $feature = $entity->feature;
                $type = $this->getThresholdType($feature);

                if ($type == 'integer') {
                    $attr['type'] = 'readOnly';
                } else {
                    $attr['visible'] = false;
                }
            }
        }

        return $attr;
    }

    public function onUpdateFieldValue(Event $event, array $attr, $action, Request $request)
    {
        $fieldKey = 'value';
        return $this->getUpdateFieldAttr($fieldKey, $attr, $action, $request);
    }

    public function onUpdateFieldOperand(Event $event, array $attr, $action, Request $request)
    {
        $fieldKey = 'operand_id';
        return $this->getUpdateFieldAttr($fieldKey, $attr, $action, $request);
    }

     public function onUpdateFieldLicenseType(Event $event, array $attr, $action, Request $request)
    {
        $fieldKey = 'license_type_id';
        return $this->getUpdateFieldAttr($fieldKey, $attr, $action, $request);
    }

    public function getUpdateFieldAttr($fieldKey, $attr, $action, $request)
    {
        if ($action == 'add') {
            if (isset($request->data[$this->alias()]['feature'])) {
                $feature = $request->data[$this->alias()]['feature'];
                $alertTypeDetails = $this->getAlertTypeDetailsByFeature($feature);

                if (isset($alertTypeDetails[$feature]['threshold']) && array_key_exists($fieldKey, $alertTypeDetails[$feature]['threshold'])) {
                    $thresholdData = $alertTypeDetails[$feature]['threshold'][$fieldKey];
                    $type = $thresholdData['type'];
                    $attr['visible'] = true;
                    $options = [];

                    if (array_key_exists('option', $thresholdData)) {
                        $options = $this->getSelectOptions($this->aliasField($thresholdData['option']));
                    }

                    if (array_key_exists('lookupModel', $thresholdData)) {
                        $ModelTable = TableRegistry::get($thresholdData['lookupModel']);
                        $options = $ModelTable
                            ->find('list')
                            ->find('visible')
                            ->find('order')
                            ->toArray();
                    }

                    if ($type == 'integer') {
                        $attr['attr']['min'] = 1;
                        $attr['attr']['max'] = 30;
                    } else if ($type = 'select') {
                        if (!empty($options)) {
                            $attr['options'] = $options;
                            $attr['select'] = false;
                        }
                    }
                } else {
                    $type = 'hidden';
                }
            } else {
                $type = 'hidden';
            }

            $attr['type'] = $type;
        } else if ($action == 'edit' || $action == 'view') {
            if (!empty($this->paramsPass(0))) {
                $alertRuleId = $this->paramsDecode($this->paramsPass(0));
                $entity = $this->get($alertRuleId);
                $feature = $entity->feature;
                $alertTypeDetails = $this->getAlertTypeDetailsByFeature($feature);

                $threshold = $entity->threshold;
                $thresholdArray = json_decode($entity->threshold, true);

                if (is_array($thresholdArray) && array_key_exists($fieldKey, $thresholdArray)) {
                    $thresholdData = $alertTypeDetails[$feature]['threshold'][$fieldKey];
                    $type = $thresholdData['type'];

                    if (array_key_exists('option', $thresholdData)) {
                        $options = $this->getSelectOptions($this->aliasField($thresholdData['option']));
                    }

                    if (array_key_exists('lookupModel', $thresholdData)) {
                        $ModelTable = TableRegistry::get($thresholdData['lookupModel']);
                        $options = $ModelTable
                            ->find('list')
                            ->find('visible')
                            ->find('order')
                            ->toArray();
                    }

                    if ($type == 'integer') {
                        $attr['value'] = $thresholdArray[$fieldKey];
                        $attr['attr']['value'] = $thresholdArray[$fieldKey];
                    } else if ($type = 'select') {
                        if (!empty($options) && $action == 'edit') {
                            $attr['value'] = $thresholdArray[$fieldKey];
                            $attr['attr']['value'] = $options[$thresholdArray[$fieldKey]];
                        } else if (!empty($options) && $action == 'view') {
                            $attr['value'] = $options[$thresholdArray[$fieldKey]];
                        }
                    }

                    $attr['type'] = 'readOnly';
                    $attr['visible'] = true;
                }
            }
        }

        return $attr;
    }

    public function onGetSecurityRoles(Event $event, Entity $entity)
    {
        if (!$entity->has('security_roles')) {
            $query = $this->find()
            ->where([$this->aliasField($this->primaryKey()) => $entity->id])
            ->contain(['SecurityRoles']);

            $data = $query->first();
        } else {
            $data = $entity;
        }

        $role = [];
        if ($data->has('security_roles')) {
            foreach ($data->security_roles as $key => $value) {
                $role[] = $value->name;
            }
        }

        return (!empty($role))? implode(', ', $role): ' ';
    }

    public function setupFields(Event $event, Entity $entity)
    {
        // Rule set up section
        $this->field('rule_set_up', ['type' => 'section']);
        $this->field('feature', ['type' => 'select']);
        $this->field('enabled', ['options' => $this->getSelectOptions('general.yesno')]);
        $this->field('method', ['type' => 'readOnly', 'after' => 'threshold']);
        $this->field('security_roles', ['after' => 'method']);

        // threshold field
        $this->field('threshold', ['after' => 'security_roles']);
        $this->field('value', ['visible' => false, 'after' => 'security_roles']);
        $this->field('operand', ['visible' => false, 'after' => 'value']);
        $this->field('license_type', ['visible' => false, 'after' => 'operand']);

        // Alert section
        $this->field('alert_content', ['type' => 'section', 'after' => 'threshold']);
    }

    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $tableHeaders =['Keyword', 'Remarks'];
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

        return $event->subject()->renderElement('Alert/' . $fieldKey, ['attr' => $attr]);
    }
}
