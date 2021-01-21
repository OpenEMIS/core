<?php
namespace Report\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;

use App\Model\Table\AppTable;

class BodyMassStatusReportsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_students');
        parent::initialize($config);

        // Associations
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('PreviousInstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'previous_institution_student_id']);

        // Behaviors
        $this->addBehavior('Excel', [
            'excludes' => [
                'student_status_id', 'academic_period_id', 'start_date', 'start_year', 'end_date', 'end_year', 'previous_institution_student_id'
            ],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
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

    public function onExcelGetAge(Event $event, Entity $entity)
    {
        // Calculate the age
        $age = '';
        if (!empty($entity->academic_period->start_year) && !empty($entity->date_of_birth)) {
            $startYear = $entity->academic_period->start_year;
            $dob = $entity->date_of_birth->format('Y');
            $age = $startYear - $dob;
        }

        return $age;
    }
    
    public function onExcelGetIdentityType(Event $event, Entity $entity)
    {
        $identityTypeName = '';
        if (!empty($entity->identity_type)) {
            $identityType = TableRegistry::get('FieldOption.IdentityTypes')->find()->where(['id'=>$entity->identity_type])->first();
            $identityTypeName = $identityType->name;
        }
        return $identityTypeName;
    }
        
    public function onExcelGetGender(Event $event, Entity $entity)
    {
        $gender = '';
        if (!empty($entity->user->gender->name) ) {
            $gender = $entity->user->gender->name;
        }

        return $gender;
    } 
    
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;

        
        $startDate = (!empty($requestData->start_date))? date('Y-m-d',strtotime($requestData->start_date)): null;
        $endDate = (!empty($requestData->end_date))? date('Y-m-d',strtotime($requestData->end_date)): null;

        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($institutionId)) {
            $conditions['Institutions.id'] = $institutionId;
        }
       
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->findByCode('CURRENT')->first()->id;
        
        $Class = TableRegistry::get('Institution.InstitutionClasses');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $query
            ->select([
                $this->aliasField('student_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('academic_period_id'),
                'bmi_status' => '(case when UserBodyMasses.body_mass_index <= 18.5 then "Underweight"
                        when UserBodyMasses.body_mass_index > 18.5 and UserBodyMasses.body_mass_index <= 24.9 then "Normal"
                        when UserBodyMasses.body_mass_index > 25 and UserBodyMasses.body_mass_index <= 29.9 then "Overweight"
                        else "Obesity" end)',
                'class_name' => 'InstitutionClasses.name',
                'code_name' => 'Institutions.code',
                'dob' => 'YEAR(Users.date_of_birth)',
                'number_of_student' => 'COUNT(*)',
                'date' => 'UserBodyMasses.date'
            ])
            ->contain([
                'Users' => [
                    'fields' => [
                        'openemis_no' => 'Users.openemis_no',
                        'Users.first_name',
                        'Users.middle_name',
                        'Users.third_name',
                        'Users.last_name',
                        'date_of_birth' => 'Users.date_of_birth',
                        'identity_number' => 'Users.identity_number',
                        'identity_type' => 'Users.identity_type_id'
                    ]
                ],
                'EducationGrades' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Users.Genders' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                        'name',
                        'code'
                    ]
                ],
                'AcademicPeriods' => [
                    'fields' => [
                        'name',
                        'start_year'
                    ]
                ]
            ])
            ->innerJoin(
                ['UserBodyMasses' => 'user_body_masses'],
                [
                    'UserBodyMasses.security_user_id = ' . $this->aliasField('student_id'),
                    'UserBodyMasses.academic_period_id = ' . $this->aliasField('academic_period_id')
                ]
            )
            ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                $ClassStudents->aliasField('student_status_id = ') . $enrolledStatus,
                $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
            ])
            ->leftJoin([$Class->alias() => $Class->table()], [
                $Class->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
            ])
             ->where($conditions) 
             ->andWhere(['UserBodyMasses.date >=' => $startDate, 'UserBodyMasses.date <=' => $endDate]) 
             ->group(['code_name', 'Users.gender_id', 'dob', 'bmi_status']);    
 
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $extraFields = [];
        $extraFields[] = [
            'key' => 'BodyMassStatusReports.code_name',
            'field' => 'code_name',
            'type' => 'string',
            'label' => __('Code')
        ];
        $extraFields[] = [
            'key' => 'BodyMassStatusReports.institution_id',
            'field' => 'institution_id',
            'type' => 'string',
            'label' => __('Name')
        ];        
        
        $extraFields[] = [
            'key' => 'BodyMassStatusReports.education_grade_id',
            'field' => 'education_grade_id',
            'type' => 'string',
            'label' => __('Education Grade')
        ];
        $extraFields[] = [
            'key' => 'Users.age',
            'field' => 'age',
            'type' => 'string',
            'label' => __('Age')
        ];
        $extraFields[] = [
            'key' => 'Users.gender',
            'field' => 'gender',
            'type' => 'string',
            'label' => __('Gender')
        ];
        
        $extraFields[] = [
            'key' => 'bmi_status',
            'field' => 'bmi_status',
            'type' => 'string',
            'label' => __('BMI Status')
        ];
        
        $extraFields[] = [
            'key' => 'BodyMassStatusReports.number_of_student',
            'field' => 'number_of_student',
            'type' => 'integer',
            'label' => __('Number of Students')
        ];
         $extraFields[] = [
            'key' => 'BodyMassStatusReports.date',
            'field' => 'date',
            'type' => 'integer',
            'label' => __('Date')
        ];
        
        $fields->exchangeArray($extraFields);
    }

}
