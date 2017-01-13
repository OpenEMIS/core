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

class AlertTypesTable extends ControllerActionTable
{
    use OptionsTrait;

    private $alertTypeCodes = [
        'InstitutionStudentAbsences' => [
            'code' => 'Attendance',
            'name' => 'Student Absent',
            'method' => 'Email',
            'threshold' => ['type' => 'integer'],
            'placeholder' => ['{name}']
        ],
    ];

    public function initialize(array $config)
    {
        $this->table('alerts');
        parent::initialize($config);

        $this->belongsToMany('SecurityRoles', [
            'className' => 'Security.SecurityRoles',
            'joinTable' => 'alerts_roles',
            'foreignKey' => 'alert_id',
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

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);

        if ($this->action == 'add') {
            $this->field('enabled', ['visible' => false]);
        } elseif ($this->action == 'edit') {
            $this->field('code', ['type' => 'readOnly']);
        }
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['SecurityRoles']);
    }

    public function getAlertTypeDetailsByCode($code)
    {
        $alertTypeDetails = [];
        foreach ($this->alertTypeCodes as $key => $obj) {
            if ($obj['code'] == $code) {
                $alertTypeDetails[$obj['code']] = $obj;
                $alertTypeDetails[$obj['code']]['model'] = $key;
            }
        }

        return $alertTypeDetails;
    }

    public function getAlertTypeDetailsByAlias($alias)
    {
        return $this->alertTypeCodes[$alias];
    }

    public function getCodeOptions()
    {
        $codeOptions = [];
        foreach ($this->alertTypeCodes as $key => $obj) {
            $codeOptions[$obj['code']] = __($obj['code']);
        }

        return $codeOptions;
    }

    public function getMethod($code)
    {
        $method = '';
        if (!empty($code)) {
            $alertTypeDetails = $this->getAlertTypeDetailsByCode($code);
            $method = $alertTypeDetails[$code]['method'];
        }

        return $method;
    }

    public function getThresholdType($code)
    {
        $type = '';
        if (!empty($code)) {
            $alertTypeDetails = $this->getAlertTypeDetailsByCode($code);
            $type = $alertTypeDetails[$code]['threshold']['type'];
        }

        return $type;
    }

    public function onGetEnabled(Event $event, Entity $entity) {
        return $entity->enabled == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
    }

    public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->getCodeOptions();
            $attr['onChangeReload'] = 'changeCode';
        }

        return $attr;
    }

    public function addEditOnChangeCode(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (isset($data)) {
            $code = $data[$this->alias()]['code'];
            $data[$this->alias()]['method'] = $this->getMethod($code);
        }
    }

    public function onUpdateFieldThreshold(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($request->data[$this->alias()]['code'])) {
                $code = $request->data[$this->alias()]['code'];
                $type = $this->getThresholdType($code);
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
        // pr('onUpdateFieldRole');die;
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
            default:
                # code...
                break;
        }

        return $attr;
    }

    public function onGetSecurityRoles(Event $event, Entity $entity)
    {
        if (!$entity->has('security_roles')) {
            $query = $this->find()
            ->where([$this->aliasField($this->primaryKey()) => $entity->id])
            ->contain(['SecurityRoles'])
            ;
            $data = $query->first();
        }
        else {
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
        $this->field('code', ['type' => 'select']);
        $this->field('enabled', ['options' => $this->getSelectOptions('general.yesno')]);
        $this->field('method', ['type' => 'readOnly', 'after' => 'threshold']);
        $this->field('threshold', ['after' => 'name']);
        $this->field('security_roles', ['after' => 'method']);

        $this->setFieldOrder('enabled', 'code', 'name', 'subject', 'message');
    }
}
