<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\ORM\TableRegistry;

class InstitutionStaffTable extends AppTable  {
	use OptionsTrait;

    public function initialize(array $config) {
        $this->table('institution_staff');
        parent::initialize($config);

        $this->belongsTo('Users',           ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Positions',       ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('Institutions',    ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('StaffTypes',      ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('StaffStatuses',   ['className' => 'Staff.StaffStatuses']);
        $this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'excludes' => ['start_year', 'end_year', 'FTE', 'security_group_user_id'],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets) {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery (Event $event, ArrayObject $settings, Query $query) {
        // Setting request data and modifying fetch condition
        $requestData = json_decode($settings['process']['params']);
        $statusId = $requestData->status;
        $typeId = $requestData->type;

        if ($statusId!=0) {
            $query->where([
                $this->aliasField('staff_status_id') => $statusId
            ]);
        }

        if ($typeId!=0) {
            $query->where([
                $this->aliasField('staff_type_id') => $typeId
            ]);
        }

        $query
        ->contain([
            'Users.Genders',
            'Institutions.Areas',
            'Institutions.AreaAdministratives',
            'Positions.StaffPositionTitles',
            'Institutions.Types',
            'Institutions.Sectors',
            'Institutions.Providers',
            'Users.Identities.IdentityTypes',
            'Users.Contacts'
        ])
        ->select([
            'openemis_no' => 'Users.openemis_no',
            'first_name' => 'Users.first_name',
            'middle_name' => 'Users.middle_name',
            'last_name' => 'Users.last_name',
            'number' => 'Users.identity_number',
            'username' => 'Users.username',
            'dob' => 'Users.date_of_birth',
            'code' => 'Institutions.code',
            'gender' => 'Genders.name',
            'area_name' => 'Areas.name',
            'area_code' => 'Areas.code',
            'area_administrative_code' => 'AreaAdministratives.code',
            'area_administrative_name' => 'AreaAdministratives.name',
            'position_title_teaching' => 'StaffPositionTitles.type',
            'institution_type' => 'Types.name',
            'position_title' => 'StaffPositionTitles.name',
            'institution_sector' => 'Sectors.name',
            'institution_provider' => 'Providers.name'
        ]);
    }

    public function onExcelGetFTE(Event $event, Entity $entity) {
        return $entity->FTE*100;
    }

    public function onExcelGetPositionTitleTeaching(Event $event, Entity $entity) {
        $yesno = $this->getSelectOptions('general.yesno');
        return (array_key_exists($entity->position_title_teaching, $yesno))? $yesno[$entity->position_title_teaching]: '';
    }

    public function onExcelRenderAge(Event $event, Entity $entity, $attr) {
        $age = '';
        if ($entity->has('user')) {
            if ($entity->user->has('date_of_birth')) {
                if (!empty($entity->user->date_of_birth)) {
                    $yearOfBirth = $entity->user->date_of_birth->format('Y');
                    $age = date("Y")-$yearOfBirth;
                }
            }
        }
        return $age;
    }

    public function onExcelGetEducationGrades(Event $event, Entity $entity)
    {
        $ClassesTable = TableRegistry::get('Institution.InstitutionClasses');

        $query = $ClassesTable
            ->find()
            ->contain(['EducationGrades'])
            ->hydrate(false)
            ->where([$ClassesTable->aliasField('staff_id') => $entity->staff_id]);

        $classes = $query->toArray();
        $grades = [];

        foreach ($classes as $class) {
            foreach ($class['education_grades'] as $grade) {
                $grades[$grade['id']] = $grade['name'];
            }
        }

        return implode(', ', array_values($grades));
    }

    public function onExcelGetUserIdentities(Event $event, Entity $entity)
    {
        $return = [];
        if ($entity->has('user')) {
            if ($entity->user->has('identities')) {
                if (!empty($entity->user->identities)) {
                    $identities = $entity->user->identities;
                    foreach ($identities as $key => $value) {
                        $return[] = '([' . $value->identity_type->name . ']' . ' - ' . $value->number . ')';
                    }
                }
            }
        }

        return implode(', ', array_values($return));
    }

    public function onExcelRenderContactOption(Event $event, Entity $entity, array $attr)
    {
        $contactTypes = $attr['contactTypes'];

        $result = [];
        if ($entity->has('user')) {
            if ($entity->user->has('contacts')) {
                $userContacts = $entity->user->contacts;
                foreach ($userContacts as $key => $obj) {
                    if (in_array($obj->contact_type_id, $contactTypes)) {
                        $result[] = $obj->value;
                    }
                }
            }
        }

        return implode(', ', $result);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) 
    {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $settings['identity'] = $identity;

        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Staff.institution_id',
            'field' => 'institution_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_type_id',
            'field' => 'institution_type',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_sector_id',
            'field' => 'institution_sector',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_provider_id',
            'field' => 'institution_provider',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.first_name',
            'field' => 'first_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.middle_name',
            'field' => 'middle_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.last_name',
            'field' => 'last_name',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.identities',
            'field' => 'user_identities',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Users.gender_id',
            'field' => 'gender',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Institutions.area_code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Education Code')
        ];

        $newFields[] = [
            'key' => 'Institutions.area',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Education')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.code',
            'field' => 'area_administrative_code',
            'type' => 'string',
            'label' => __('Area Administrative Code')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.name',
            'field' => 'area_administrative_name',
            'type' => 'string',
            'label' => __('Area Administrative')
        ];

        $newFields[] = [
            'key' => 'Staff.FTE',
            'field' => 'FTE',
            'type' => 'integer',
            'label' => 'FTE (%)',
        ];

        $newFields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'dob',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Age',
            'field' => 'Age',
            'type' => 'Age',
            'label' => __('Age'),
        ];

        $newFields[] = [
            'key' => 'InstitutionStaff.start_date',
            'field' => 'start_date',
            'type' => 'date',
            'label' => ''
        ];

         $newFields[] = [
            'key' => 'InstitutionStaff.end_date',
            'field' => 'end_date',
            'type' => 'date',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaff.staff_type_id',
            'field' => 'staff_type_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Education.education_grades',
            'field' => 'education_grades',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaff.staff_status_id',
            'field' => 'staff_status_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'InstitutionStaff.institution_position_id',
            'field' => 'institution_position_id',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Positions.position_title',
            'field' => 'position_title',
            'type' => 'string',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'Positions.position_title_teaching',
            'field' => 'position_title_teaching',
            'type' => 'string',
            'label' => __('Teaching')
        ];

        $newFields[] = [
            'key' => 'Users.username',
            'field' => 'username',
            'type' => 'string',
            'label' => __('Username')
        ];

        $displayContactOptions = ['MOBILE', 'PHONE', 'EMAIL'];
        $ContactOptionsTable = TableRegistry::get('User.ContactOptions');
        $options = $ContactOptionsTable->find('list')
            ->where([$ContactOptionsTable->aliasField('code IN') => $displayContactOptions])
            ->order('order')
            ->toArray();

        $ContactTypesTable = TableRegistry::get('User.ContactTypes');
        foreach ($options as $id => $name) {
            $contactTypes = $ContactTypesTable->find()
                ->where([$ContactTypesTable->aliasField('contact_option_id') => $id])
                ->extract('id')
                ->toArray();

            $newFields[] = [
                'key' => 'contact_option',
                'field' => 'contact_option',
                'type' => 'contact_option',
                'label' => __($name),
                'formatting' => 'string',
                'contactTypes' => $contactTypes
            ];
        }

        $fields->exchangeArray($newFields);
    }
}