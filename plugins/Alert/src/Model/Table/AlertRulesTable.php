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

    private $alertTypeFeatures = [];

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
        $this->addBehavior('Alert.AlertRuleAttendance');
        $this->addBehavior('Alert.AlertRuleLicenseValidity');
        $this->addBehavior('Alert.AlertRuleStaffLeave');
        $this->addBehavior('Alert.AlertRuleEmploymentPeriod');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table'
            ])
            ;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('message', ['visible' => false]);
        $this->field('enabled', ['options' => $this->getSelectOptions('general.yesno')]);
        $this->field('security_roles', ['after' => 'method']);

        // element control
        $featureOptions = $this->getFeatureOptions();
        if (!empty($featureOptions)) {
            $featureOptions = ['-1' => __('All Features')] + $featureOptions;
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

        if ($selectedFeature != -1) {
            $query->where(['feature' => $selectedFeature]);
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
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

        ksort($featureOptions);

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

    public function onGetFeature(Event $event, Entity $entity)
    {
        return Inflector::humanize(Inflector::underscore($entity->feature));
    }

    public function onGetEnabled(Event $event, Entity $entity)
    {
        return $entity->enabled == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    public function onGetThreshold(Event $event, Entity $entity)
    {
        // temporary solution
        $origEntity = $this->get($entity->id);
        if ($origEntity->has('feature') && !empty($origEntity->feature)) {
            $event = $this->dispatchEvent('AlertRule.onGet.'.$origEntity->feature.'.Threshold', [$origEntity], $this);
            if ($event->isStopped()) { return $event->result; }
            if (!empty($event->result)) {
                return $event->result;
            }
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
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

    public function addEditOnChangeFeature(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data)) {
            $feature = $data[$this->alias()]['feature'];
            $data[$this->alias()]['method'] = $this->getMethod($feature);
        }
    }

    public function onUpdateFieldEnabled(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['visible'] = false;
        } else if ($action == 'edit') {
            $attr['select'] = false;
            $attr['options'] = $this->getSelectOptions('general.yesno');
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

/********************************************************************************
**                               Threshold                                     **
*********************************************************************************/

    public function onUpdateFieldThreshold(Event $event, array $attr, $action, Request $request)
    {
        $entity = $attr['entity'];
        if ($action == 'add') {
            $attr['type'] = 'hidden';
        } else if ($action == 'view' || $action == 'edit') {
            $attr['visible'] = false;
        }

        if ($entity->has('feature') && !empty($entity->feature)) {
            $event = $this->dispatchEvent('AlertRule.UpdateField.'.$entity->feature.'.Threshold', [$attr, $action, $request], $this);
            if ($event->isStopped()) { return $event->result; }
            if (!empty($event->result)) {
                $attr = $event->result;

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
        $this->field('feature', ['type' => 'select', 'entity' => $entity]);
        $this->field('enabled', ['type' => 'select']);
        $this->field('method', ['type' => 'readOnly', 'after' => 'threshold']);
        $this->field('security_roles', ['after' => 'method']);
        $this->field('threshold', ['after' => 'security_roles', 'entity' => $entity]);

        // Alert section
        $this->field('alert_content', ['type' => 'section', 'after' => 'threshold']);
        $this->field('alert_features', ['type' => 'custom_criterias', 'after' => 'message']);

        $event = $this->dispatchEvent('AlertRule.setupFields', [$entity], $this);
        if ($event->isStopped()) { return $event->result; }
    }

    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if ($action == 'add' || $action == 'edit') {
            $tableHeaders =[__('Keyword'), __('Remarks')];
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

    public function getAlertRuleTypes() {
        return $this->alertTypeFeatures;
    }

    public function addAlertRuleType($newAlertRuleType, $_config) {
        $this->alertTypeFeatures[$newAlertRuleType] = $_config;
    }
}
