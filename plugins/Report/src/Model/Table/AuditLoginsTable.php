<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
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

    public function initialize(array $config): void
    {
        $this->setTable('security_users');
        parent::initialize($config);
        $this->setEntityClass('User.User');

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
    //POCOR-8070 :: Modify query and fields 
    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);

        $reportStartDate = (new DateTime($requestData->report_start_date))->format('Y-m-d H:i:s');
        $reportEndDate = (new DateTime($requestData->report_end_date))->format('Y-m-d H:i:s');

        $query
            ->select([
                'login_date_time' => 'UserLogins.login_date_time',
                'ip_address' => 'UserLogins.ip_address',
                'openemis_no' => $this->aliasField('openemis_no'),
                'user_name' => "(CONCAT_WS(' ',`first_name`,NULLIF(`middle_name`, ''),NULLIF(`third_name`, ''), `last_name`))",
                'nationality_name' => 'MainNationalities.name',
                'main_identity_type' => 'MainIdentityTypes.name',
                'identity_type' => 'MainIdentityTypes.name',
                'identity_number' => $this->aliasField('identity_number')
            ])
            ->innerJoin(['UserLogins' => 'security_user_logins'], [
                'UserLogins.security_user_id = ' . $this->aliasField('id')
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
                'UserLogins.login_date_time >=' => $reportStartDate,
                'UserLogins.login_date_time <=' => $reportEndDate
            ]);

        switch ($requestData->sort_by) {
            case "LastLoginDESC":
                $query->order(['login_date_time' =>'DESC']);
                break;
            case "LastLoginASC":
                $query->order(['login_date_time' =>'ASC']);
                break;
            default:    // By default sort by nothing (Default Sort)
                break;
        }
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key' => 'login_date_time',
            'field' => 'login_date_time',
            'type' => 'string',
            'label' => __('Login date and time')
        ];
        $newFields[] = [
            'key' => 'ip_address',
            'field' => 'ip_address',
            'type' => 'string',
            'label' => __('IP address')
        ];
        $newFields[] = [
            'key' => 'AuditLogins.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];
        $newFields[] = [
            'key' => 'user_name',
            'field' => 'user_name',
            'type' => 'string',
            'label' => __('Name')
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
            'label' => __('Default Identity Type')
        ];
        $newFields[] = [
            'key' => 'AuditLogins.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        
        $fields->exchangeArray($newFields);
    }
    
}
