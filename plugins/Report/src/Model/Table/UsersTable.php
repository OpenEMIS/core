<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class UsersTable extends AppTable
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
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) 
    {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    public function onExcelGetUserTypeStudent(Event $event, Entity $entity)
    {
        return 'Student';
    }

    public function onExcelGetUserTypeStaff(Event $event, Entity $entity)
    {
        return 'Staff';
    }

    public function onExcelGetUserTypeGuardian(Event $event, Entity $entity)
    {
        return 'Guardian';
    }

    public function onExcelGetUserTypeOthers(Event $event, Entity $entity)
    {
        return 'Others';
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $userType = $requestData->user_type;
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
                    $this->aliasField('address'),
                    $this->aliasField('email'),
                    $this->aliasField('postal_code'),
                    $this->aliasField('identity_number'),
                    'nationality_name' => 'MainNationalities.name',
                    'identity_type' => 'MainIdentityTypes.name',
                    'gender' => 'Genders.name',
                    'address_area' => 'AddressAreas.name',
                    'birth_area' => 'BirthplaceAreas.name',
                ])
                ->contain(['Genders', 'MainNationalities', 'MainIdentityTypes', 'AddressAreas', 'BirthplaceAreas']);

        if ($userType ==  'Others') {
            $query
                ->where([$this->aliasField('is_staff') => 0]);
        } 

        if ($userType == 'Guardian') {
            $query
                ->where([$this->aliasField('is_guardian') => 1]);
        } 

        if ($userType == 'Staff') {
            $StaffCustomFieldValues = TableRegistry::get('StaffCustomFieldValues');
            $StaffCustomFields = TableRegistry::get('StaffCustomFields');

            $query
                ->leftJoin([$StaffCustomFieldValues->alias() => $StaffCustomFieldValues->table()], [
                        $StaffCustomFieldValues->aliasField('staff_id = ') . $this->aliasField('id'),
                ])
                ->leftJoin([$StaffCustomFields->alias() => $StaffCustomFields->table()], [
                        $StaffCustomFields->aliasField('id = ') . $StaffCustomFieldValues->aliasField('staff_custom_field_id'),
                ])
                ->where([$this->aliasField('is_staff') => 1]);
        }

        if ($userType == 'Student') {
            $StudentCustomFieldValues = TableRegistry::get('StudentCustomFieldValues');
            $StudentCustomFields = TableRegistry::get('StudentCustomFields');

            $query
                ->leftJoin([$StudentCustomFieldValues->alias() => $StudentCustomFieldValues->table()], [
                        $StudentCustomFieldValues->aliasField('student_id = ') . $this->aliasField('id'),
                ])
                ->select(['custom' => $StudentCustomFieldValues->aliasField('text_value')])
                ->where([$this->aliasField('is_student') => 1]);
        }
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {  
        $cloneFields = $fields->getArrayCopy();
        $requestData = json_decode($settings['process']['params']);

        $userType = $requestData->user_type;
        
        $extraFields = [];

        $extraFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $extraFields[] = [
            'key' => 'Users.first_name',
            'field' => 'first_name',
            'type' => 'string',
            'label' => __('First Name')
        ];

        $extraFields[] = [
            'key' => 'Users.middle_name',
            'field' => 'middle_name',
            'type' => 'string',
            'label' => __('Middle Name')
        ];

        $extraFields[] = [
            'key' => 'Users.third_name',
            'field' => 'third_name',
            'type' => 'string',
            'label' => __('Third Name')
        ];

        $extraFields[] = [
            'key' => 'Users.last_name',
            'field' => 'last_name',
            'type' => 'string',
            'label' => __('Last Name')
        ];

        $extraFields[] = [
            'key' => 'Users.preferred_name',
            'field' => 'preferred_name',
            'type' => 'string',
            'label' => __('Preferred Name')
        ];

        $extraFields[] = [
            'key' => 'Genders.name',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender')
        ];

        $extraFields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'date',
            'label' => __('Date of Birth')
        ];

        $extraFields[] = [
            'key' => 'Users.address',
            'field' => 'address',
            'type' => 'string',
            'label' => __('Address')
        ];

        $extraFields[] = [
            'key' => 'AddressAreas.name',
            'field' => 'address_area',
            'type' => 'string',
            'label' => __('Address Area')
        ];

        $extraFields[] = [
            'key' => 'BirthplaceAreas.name',
            'field' => 'birth_area',
            'type' => 'string',
            'label' => __('Birth Place Area')
        ];

        $extraFields[] = [
            'key' => 'MainNationalities.name',
            'field' => 'nationality_name',
            'type' => 'string',
            'label' => __('Nationality')
        ];

        $extraFields[] = [
            'key' => 'MainIdentityTypes.name',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type')
        ];

        $extraFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        $extraFields[] = [
            'key' => 'Users.email',
            'field' => 'email',
            'type' => 'string',
            'label' => __('Email')
        ];

        $extraFields[] = [
            'key' => 'Users.postal_code',
            'field' => 'postal_code',
            'type' => 'string',
            'label' => __('Postal Code')
        ];

        $extraFields[] = [
            'key' => 'userType'.$userType,
            'field' => 'userType'.$userType,
            'type' => 'string',
            'label' => __('User Type')
        ];

        if ($userType == 'Student') {
            $StudentCustomFields = TableRegistry::get('StudentCustomFields');
            $customField = $StudentCustomFields->find()
                            ->select([
                                'id' => $StudentCustomFields->aliasField('id'),
                                'student_custom' => $StudentCustomFields->aliasField('name'),
                    ])->toArray();

            if (!empty($customField)) {
                foreach ($customField as $key => $value) {
                    $customFieldName = $value->student_custom;
                    $id = $value->id;

                    $label = __($customFieldName);
                       $extraFields[] = [
                        'key' => 'custom',
                        'field' => 'custom',
                        'type' => 'string',
                        'label' => $label,
                    ]; 
                }
            }
        }

        if($userType == 'Staff') {
            $extraFields[] = [
                'key' => 'staff_association',
                'field' => 'staff_association',
                'type' => 'string',
                'label' => __('Staff Association ID')
            ]; 
        }
        
        //$newValues = array_merge($fields->getArrayCopy(), $newFields);
        $fields->exchangeArray($extraFields);
    }
}
