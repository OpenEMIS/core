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

use App\Model\Traits\OptionsTrait;

class AuditLoginsTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);
        $this->entityClass('User.User');

        $this->hasMany('Identities', ['className' => 'User.Identities',      'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Nationalities', ['className' => 'User.UserNationalities',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Contacts', ['className' => 'User.Contacts',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Attachments', ['className' => 'User.Attachments',     'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('BankAccounts', ['className' => 'User.BankAccounts',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Comments', ['className' => 'User.Comments',        'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Languages', ['className' => 'User.UserLanguages',   'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Awards', ['className' => 'User.Awards',          'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->hasMany('Logins', ['className' => 'SSO.SecurityUserLogins', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Counsellings', ['className' => 'Counselling.Counsellings', 'foreignKey' => 'counselor_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('BodyMasses', ['className' => 'User.UserBodyMasses', 'foreignKey' => 'security_user_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        
        $this->hasMany('SpecialNeeds', ['className' => 'SpecialNeeds.SpecialNeedsAssessments',    'foreignKey' => 'security_user_id', 'dependent' => true]);
        
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

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);

        $reportStartDate = (new DateTime($requestData->report_start_date))->format('Y-m-d H:i:s');
        $reportEndDate = (new DateTime($requestData->report_end_date))->format('Y-m-d H:i:s');

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

        switch ($requestData->sort_by) {
            case "LastLoginDESC":
                $query->order(['last_login' =>'DESC']);
                break;
            case "LastLoginASC":
                $query->order(['last_login' =>'ASC']);
                break;
            default:    // By default sort by nothing (Default Sort)
                break;
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'AuditLogins.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $newFields[] = [
            'key' => 'AuditLogins.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Modified By')
        ];
        $newFields[] = [
            'key' => 'AuditLogins.email',
            'field' => 'email',
            'type' => 'string',
            'label' => __('Email')
        ];
        $newFields[] = [
            'key' => 'MainIdentityTypes.name',
            'field' => 'nationality_name',
            'type' => 'string',
            'label' => __('Nationality')
        ];
        $newFields[] = [
            'key' => 'MainNationalities.name',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type')
        ];
        $newFields[] = [
            'key' => 'AuditLogins.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        $newFields[] = [
            'key' => 'AuditLogins.external_reference',
            'field' => 'external_reference',
            'type' => 'string',
            'label' => __('External Reference')
        ];
        $newFields[] = [
            'key' => 'AuditLogins.status',
            'field' => 'status',
            'type' => 'string',
            'label' => __('Status')
        ];
        $newFields[] = [
            'key' => 'AuditLogins.last_login',
            'field' => 'last_login',
            'type' => 'datetime',
            'label' => __('Last Login')
        ];
        $newFields[] = [
            'key' => 'AuditLogins.preferred_language',
            'field' => 'preferred_language',
            'type' => 'string',
            'label' => __('Preferred Language')
        ];

        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStatus(Event $event, Entity $entity)
    {
        $options = $this->getSelectOptions('general.active');

        if (array_key_exists($entity->status, $options)) {
            return $options[$entity->status];
        }

        return '';
    }
}
