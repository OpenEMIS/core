<?php
namespace Alert\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
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
                '${user.photo_name}' => 'Student photo name.',
                '${user.photo_content}' => 'Student photo content.',
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
    ];

    public function initialize(array $config)
    {
        $this->table('alert_rules');
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
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => __('This field has to be unique')
            ]);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('enabled', ['options' => $this->getSelectOptions('general.yesno')]);
        $this->field('security_roles', ['after' => 'method']);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // element control
        $featureOptions = $this->getFeatureOptions();
        if (!empty($featureOptions)) {
            $featureOptions = [0 => 'All Features'] + $featureOptions;
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

        $conditions = [];
        if ($selectedFeature != 0) {
            $conditions = [
                'feature' => $selectedFeature
            ];
        }

        return $query->where([$conditions]);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
        $this->field('alert_features', ['type' => 'custom_criterias', 'after' => 'message']);

        if ($this->action == 'add') {
            $this->field('enabled', ['visible' => false]);
        } elseif ($this->action == 'edit') {
            $this->field('enabled', ['select' => false]);
            $this->field('feature', ['type' => 'readOnly']);
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['SecurityRoles']);
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
            $featureOptions[$obj['feature']] = __($obj['feature']);
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
            $type = $alertTypeDetails[$feature]['threshold']['type'];
        }

        return $type;
    }

    public function onGetEnabled(Event $event, Entity $entity) {
        return $entity->enabled == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
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

    public function onUpdateFieldThreshold(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($request->data[$this->alias()]['feature'])) {
                $feature = $request->data[$this->alias()]['feature'];
                $type = $this->getThresholdType($feature);
            } else {
                $type = 'hidden';
            }

            $attr['type'] = $type;
        } else if ($action == 'edit') {
            $attr['type'] = 'readOnly';
        }

        return $attr;
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
        $this->field('feature', ['type' => 'select']);
        $this->field('enabled', ['options' => $this->getSelectOptions('general.yesno')]);
        $this->field('method', ['type' => 'readOnly', 'after' => 'threshold']);
        $this->field('threshold', ['after' => 'name']);
        $this->field('security_roles', ['after' => 'method']);

        $this->setFieldOrder('enabled', 'feature', 'name', 'subject', 'message');
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
