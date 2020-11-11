<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class DirectoryTable extends AppTable
{
    const NO_FILTER = 0;
    const STUDENT = 1;
    const STAFF = 2;
   
    public function initialize(array $config)
    {
        $this->table('security_users');
        $this->entityClass('User.User');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->addBehavior('Excel', [
            'excludes' => ['is_student', 'is_staff', 'is_guardian', 'photo_name', 'super_admin', 'status'],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('user_type', ['type' => 'hidden']);
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('filter_types', ['type' => 'hidden']);
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getFeatureOptions($this->alias());
            $attr['onChangeReload'] = true;
            if (!(isset($this->request->data[$this->alias()]['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
            }
            return $attr;
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $feature = $requestData->feature;
        $filter = $requestData->filter_types;
        $condition = [];
        switch ($filter) {
            case self::STUDENT:
                $condition[] = [$this->aliasField('is_student') => 1];
                break;

            case self::STAFF:
                $condition[] = [$this->aliasField('is_staff') => 1];
                break;

            case self::NO_FILTER:
                break;
        }
        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('openemis_no'),
                $this->aliasField('first_name'),
                $this->aliasField('middle_name'),
                $this->aliasField('third_name'),
                $this->aliasField('last_name'),
                $this->aliasField('preferred_name'),
                $this->aliasField('date_of_birth'),
                 $this->aliasField('identity_number'),
                'nationality_name' => 'MainNationalities.name',
                'identity_type' => 'MainIdentityTypes.name',
                'gender' => 'Genders.name',
            ])
            ->contain(['Genders', 'MainNationalities', 'MainIdentityTypes'])
            ->where([$condition]);
    }

    public function onUpdateFieldFilterTypes(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if ($feature == 'Report.Directory') {
                $option[self::NO_FILTER] = __('All Users');
                $option[self::STUDENT] = __('Students');
                $option[self::STAFF] = __('Staff');
                $attr['type'] = 'select';
                $attr['options'] = $option;
                $attr['onChangeReload'] = true;
                return $attr;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
        }
    }

    public function onUpdateFieldUserType(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.Users'])) {
                $options = [
                    'Guardian' => __('Guardian'),
                    'Others' => __('Others'),
                    'Staff' => __('Staff'),
                    'Student' => __('Student'),
                ];
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $options;
                return $attr;
            }
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraFields[] = [
            'key' => 'Directory.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $extraFields[] = [
            'key' => 'MainNationalities.name',
            'field' => 'nationality_name',
            'type' => 'string',
            'label' => __('Default Nationality')
        ];

        $extraFields[] = [
            'key' => 'MainIdentityTypes.name',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Default Identity Type')
        ];

        $extraFields[] = [
            'key' => 'Directory.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Default Identity Number')
        ];

        $extraFields[] = [
            'key' => 'Directory.id',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Name')
        ];

        $extraFields[] = [
            'key' => 'Genders.name',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender')
        ];

        $extraFields[] = [
            'key' => 'Directory.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'date',
            'label' => __('DOB')
        ];

        $fields->exchangeArray($extraFields);
    }
}
