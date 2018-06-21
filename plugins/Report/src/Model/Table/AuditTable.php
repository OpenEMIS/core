<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Validation\Validator;

class AuditTable extends AppTable
{
    //User type ddl
    private $userTypeOption = ['0' => "All Type", '1' => 'Student', '2' => 'Staff', '3' => 'Guardian'];

    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);
        $this->hasMany('Identities', ['className' => 'User.Identities',      'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Nationalities', ['className' => 'User.UserNationalities',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('SpecialNeeds', ['className' => 'User.SpecialNeeds',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Contacts', ['className' => 'User.Contacts',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Attachments', ['className' => 'User.Attachments',     'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('BankAccounts', ['className' => 'User.BankAccounts',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Comments', ['className' => 'User.Comments',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Languages', ['className' => 'User.UserLanguages',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Awards', ['className' => 'User.Awards',          'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Logins', ['className' => 'SSO.SecurityUserLogins', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Counsellings', ['className' => 'Counselling.Counsellings', 'foreignKey' => 'counselor_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('BodyMasses', ['className' => 'User.UserBodyMasses', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        
        $this->addBehavior('Excel', [
            'excludes' => ['username', 'address', 'postal_code',
                            'address_area_id', 'birthplace_area_id', 'gender_id', 'date_of_birth', 'date_of_death', 'super_admin',
                            'photo_name', 'photo_content',  'photo_name', 'is_student', 'is_staff', 'is_guardian'],
            'pages' => false,
            'autoFields' => false
        ]);

        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $this->addBehavior('Report.ReportList');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        $validator
            ->add('report_start_date', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'report_end_date', true],
                    'on' => function ($context) {
                        if (array_key_exists('feature', $context['data'])) {
                            $feature = $context['data']['feature'];
                            switch ($feature) {
                                case "Report.Audit":
                                    return in_array($feature, ['Report.Audit']);
                                case "Report.AuditInstitution":
                                    return in_array($feature, ['Report.AuditInstitution']);
                                case "Report.AuditUser":
                                    return in_array($feature, ['Report.AuditUser']);
                                default:
                                    return in_array($feature, ['Report.Audit']);
                            }
                            
                        }
                        return true;
                    }
                ],
            ]);

        return $validator;
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('user_type', ['type' => 'hidden']);
        $this->ControllerAction->field('report_start_date');
        $this->ControllerAction->field('report_end_date');
    }

    public function onExcelGetStatus(Event $event, Entity $entity)
    {
        if ($entity->status == 1) {
            return __('Active');
        } else {
            return __('Inactive');
        }
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

    public function onUpdateFieldUserType(Event $event, array $attr, $action, Request $request)
    {
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, ['Report.AuditUser'])) {
                $attr['options'] = $this->userTypeOption;
                $attr['type'] = 'select';
                $attr['select'] = false;
            }
            return $attr;
        }
    }

    public function onGetReportName(Event $event, ArrayObject $data)
    {
        return __('Overview');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $reportStartDate = (new DateTime($requestData->report_start_date))->format('Y-m-d');
        $reportEndDate = (new DateTime($requestData->report_end_date))->format('Y-m-d');

        $query
            ->select([
                'openemis_no' => $this->aliasField('openemis_no'),
                'first_name' => $this->aliasField('first_name'),
                'middle_name' => $this->aliasField('middle_name'),
                'third_name' => $this->aliasField('third_name'),
                'last_name' => $this->aliasField('last_name'),
                'preferred_name' => $this->aliasField('preferred_name'),
                'email' => $this->aliasField('email'),
                'nationality_name' => 'MainNationalities.name',
                'identity_type' => 'MainIdentityTypes.name',
                'identity_number' => $this->aliasField('identity_number'),
                'external_reference' => $this->aliasField('external_reference'),
                'status' => $this->aliasField('status'),
                'last_login' => $this->aliasField('last_login'),
                'preferred_language' => $this->aliasField('preferred_language')
            ])
            ->contain([
                'MainNationalities' => [
                    'fields' => [
                        'MainNationalities.name'
                    ]
                ],
                'MainIdentityTypes' => [
                    'fields' => [
                        'MainIdentityTypes.name'
                    ]
                ]
            ])
            ->where([
                $this->aliasField('last_login >= "') . $reportStartDate . '"',
                $this->aliasField('last_login <= "') . $reportEndDate . '"'
            ]);

    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        foreach ($fields as $key => $field) {
            if ($field['field'] == 'identity_type_id') {
                $fields[$key] = [
                    'key' => 'MainIdentityTypes.name',
                    'field' => 'identity_type',
                    'type' => 'string',
                    'label' => __('Main Identity Type')
                ];
            }

            if ($field['field'] == 'nationality_id') {
                $fields[$key] = [
                    'key' => 'MainNationalities.name',
                    'field' => 'nationality_name',
                    'type' => 'string',
                    'label' => __('Main Nationality')
                ];
            }
        }
    }

    public function onUpdateFieldReportStartDate(Event $event, array $attr, $action, Request $request)
    {
        $attr['type'] = 'date';
        return $attr;
    }

    public function onUpdateFieldReportEndDate(Event $event, array $attr, $action, Request $request)
    {
        $attr['type'] = 'date';
        $attr['value'] = Time::now();
        return $attr;
    }
}
