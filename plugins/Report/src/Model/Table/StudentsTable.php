<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StudentsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('security_users');
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
        $this->addBehavior('Report.CustomFieldList', [
            'model' => 'Student.Students',
            'formFilterClass' => null,
            'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],
            'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
        ]);
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $query
            ->select([
                'username' => 'Students.username',
                'openemis_no' => 'Students.openemis_no',
                'first_name' => 'Students.first_name',
                'middle_name' => 'Students.middle_name',
                'third_name' => 'Students.third_name',
                'last_name' => 'Students.last_name',
                'preferred_name' => 'Students.preferred_name',
                'email' => 'Students.email',
                'address' => 'Students.address',
                'postal_code' => 'Students.postal_code',
                'address_area' => 'AddressAreas.name',
                'birthplace_area' => 'BirthplaceAreas.name',
                'gender' => 'Genders.name',
                'date_of_birth' => 'Students.date_of_birth',
                'date_of_death' => 'Students.date_of_death',
                'nationality_name' => 'MainNationalities.name',
                'identity_type' => 'MainIdentityTypes.name',
                'identity_number' => 'Students.identity_number',
                'external_reference' => 'Students.external_reference',
                'last_login' => 'Students.last_login',
                'preferred_language' => 'Students.preferred_language'
            ])
            ->contain(['Genders', 'AddressAreas', 'BirthplaceAreas', 'MainNationalities', 'MainIdentityTypes'])
            ->where([$this->aliasField('is_student') => 1]);
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

            if ($field['field'] == 'address_area_id') { 
                $fields[$key] = [
                    'key' => 'AddressAreas.name',
                    'field' => 'address_area',
                    'type' => 'string',
                    'label' => __('Address Area')
                ];
            }

            if ($field['field'] == 'birthplace_area_id') { 
                $fields[$key] = [
                    'key' => 'BirthplaceAreas.name',
                    'field' => 'birthplace_area',
                    'type' => 'string',
                    'label' => __('Birthplace Area')
                ];
            }

            if ($field['field'] == 'gender_id') { 
                $fields[$key] = [
                    'key' => 'Genders.name',
                    'field' => 'gender',
                    'type' => 'string',
                    'label' => __('Gender')
                ];
            }
        }
    }
}
