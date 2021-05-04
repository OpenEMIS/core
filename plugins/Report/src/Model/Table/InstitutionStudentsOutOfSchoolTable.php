<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use App\Model\Traits\MessagesTrait;

class InstitutionStudentsOutOfSchoolTable extends AppTable  {
    use MessagesTrait;

    public function initialize(array $config) {
        $this->table('security_users');
        parent::initialize($config);

        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);
        $this->hasMany('Identities',        ['className' => 'User.Identities',      'foreignKey' => 'security_user_id', 'dependent' => true]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'excludes' => ['super_admin', 'is_student', 'is_staff', 'is_guardian', 'photo_name', 'date_of_death', 'last_login', 'status', 'username'], 
            'pages' => false
        ]);
    }

    public function onExcelBeforeQuery (Event $event, ArrayObject $settings, Query $query) {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        // echo "<pre>"; print_r($academicPeriodId); die();
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $enrolled = $StudentStatuses->getIdByCode('CURRENT');

        $query->join([
            'InstitutionStudent' => [
                'type' => 'left',
                'table' => 'institution_students',
                'conditions' => [
                    $this->aliasField($this->primaryKey()) . ' = InstitutionStudent.student_id'
                ],
            ],
            'InstitutionStudentFilter' => [
                'type' => 'left',
                'table' => 'institution_students',
                'conditions' => [
                    $this->aliasField($this->primaryKey()) . ' = InstitutionStudentFilter.student_id',
                    'InstitutionStudentFilter.student_status_id = '.$enrolled
                ],
            ],
            'StudentStatus' => [
                'type' => 'left',
                'table' => 'student_statuses',
                'conditions' => [
                    'StudentStatus.id = InstitutionStudent.student_status_id'
                ]
            ],
            'AcademicPeriod' => [
                'type' => 'left',
                'table' => 'academic_periods',
                'conditions' => [
                    'AcademicPeriod.id = InstitutionStudent.academic_period_id'
                ]
            ],
            'EducationGrade' => [
                'type' => 'left',
                'table' => 'education_grades',
                'conditions' => [
                    'EducationGrade.id = InstitutionStudent.education_grade_id'
                ]
            ]
        ]);

        $query->contain(['MainNationalities', 'MainIdentityTypes']);

        $query->select([
                    'EndDate' => 'InstitutionStudent.end_date', 'StudentStatus' => 'StudentStatus.name', 'AcademicPeriod' => 'AcademicPeriod.name', 'EducationGrade' => 'EducationGrade.name',
                    'nationality_id' => 'MainNationalities.name', 'identity_type_id' => 'MainIdentityTypes.name'
                ]);
        $query->autoFields('true');

        $query->where([$this->aliasField('is_student') => 1,'InstitutionStudent.academic_period_id' => $academicPeriodId]);

        // omit all records who are 'enrolled'
        $query->where([
            'OR' => [
                    'InstitutionStudent.student_status_id != ' => $enrolled,
                    'InstitutionStudent.student_status_id IS NULL'
                ]
            ]);

        // omit all students in current records who has 'enrolled'
        $query->where([
            'InstitutionStudentFilter.student_status_id IS NULL',
            // 'InstitutionStudent.academic_period_id' => $academicPeriodId,
        ]);

        $query->group([$this->aliasField($this->primaryKey())]);
        $query->order(['InstitutionStudent.end_date desc']);
    }

    public function onExcelRenderAge(Event $event, Entity $entity, $attr) {
        $age = '';
        if ($entity->has('date_of_birth')) {
            if (!empty($entity->date_of_birth)) {
                $dateOfBirth = $entity->date_of_birth->format('Y-m-d');
                $today = date('Y-m-d');
                $age = date_diff(date_create($dateOfBirth), date_create($today))->y;
            }
        }
    
        return $age;
    }

    public function onExcelGetStudentStatus(Event $event, Entity $entity) {
        return (!$entity->has('StudentStatus') || empty($entity->StudentStatus))? $this->getMessage('Institution.InstitutionStudents.notInSchool'): $entity->StudentStatus;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $settings['identity'] = $identity;

        $extraField[] = [
            'key' => 'AcademicPeriod.name',
            'field' => 'AcademicPeriod',
            'type' => 'string',
            'label' => 'Academic Period',
        ];

        $extraField[] = [
            'key' => 'StudentStatus.name',
            'field' => 'StudentStatus',
            'type' => 'string',
            'label' => 'Student Status',
        ];

    
        $extraField[] = [
            'key' => 'EducationGrade.name',
            'field' => 'EducationGrade',
            'type' => 'string',
            'label' => 'Education Grade',
        ];

        $extraField[] = [
            'key' => 'InstitutionStudent.end_date',
            'field' => 'EndDate',
            'type' => 'string',
            'label' => 'End Date',
        ];

        $extraField[] = [
            'key' => 'Age',
            'field' => 'Age',
            'type' => 'Age',
            'label' => 'Age at End Date',
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('Openemis No')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.student_name',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Full Name')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.preferred_name',
            'field' => 'preferred_name',
            'type' => 'string',
            'label' => __('Preferred Name')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.email',
            'field' => 'email',
            'type' => 'string',
            'label' => __('Email')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.address',
            'field' => 'address',
            'type' => 'string',
            'label' => __('Address')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.postal_code',
            'field' => 'postal_code',
            'type' => 'string',
            'label' => __('Postal Code')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.address_area_id',
            'field' => 'address_area_id',
            'type' => 'string',
            'label' => __('Address Area')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.birthplace_area_id',
            'field' => 'birthplace_area_id',
            'type' => 'string',
            'label' => __('Birthplace Area')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.gender_id',
            'field' => 'gender_id',
            'type' => 'string',
            'label' => __('Gender')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'string',
            'label' => __('Date Of Birth')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.nationality_id',
            'field' => 'nationality_id',
            'type' => 'string',
            'label' => __('Nationality')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.identity_type_id',
            'field' => 'identity_type_id',
            'type' => 'string',
            'label' => __('Identity Type')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudentsOutOfSchool.external_reference',
            'field' => 'external_reference',
            'type' => 'string',
            'label' => __('External Reference')
        ];


        // $newFields = array_merge($extraField, $fields->getArrayCopy());
        $fields->exchangeArray($extraField);
    }

    public function onExcelGetStudentName(Event $event, Entity $entity)
    {
        $studentName = [];
        ($entity->first_name) ? $studentName[] = $entity->first_name : '';
        ($entity->middle_name) ? $studentName[] = $entity->middle_name : '';
        ($entity->third_name) ? $studentName[] = $entity->third_name : '';
        ($entity->last_name) ? $studentName[] = $entity->last_name : '';

        return implode(' ', $studentName);
    }

    public function onExcelGetDateOfBirth(Event $event, Entity $entity)
    {
        $dateOfBirth = '';
        if ($entity->has('date_of_birth')) {
            if (!empty($entity->date_of_birth)) {
                $dateOfBirth = $entity->date_of_birth->format('F d,Y');
            }
        }
    
        return $dateOfBirth;
    }
}