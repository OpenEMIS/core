<?php
namespace Report\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\AppTable;

class BodyMassesTable extends AppTable
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
        if (!is_null($entity->academic_period->start_year) && !is_null($entity->date_of_birth)) {
            $startYear = $entity->academic_period->start_year;
            $dob = $entity->date_of_birth->format('Y');
            $age = $startYear - $dob;
        }

        return $age;
    }

    public function onExcelGetGender(Event $event, Entity $entity)
    {
        $gender = '';
        if (!is_null($entity->user->gender->name) ) {
            $gender = $entity->user->gender->name;
        }

        return $gender;
    }    

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;

        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($institutionId)) {
            $conditions['Institutions.id'] = $institutionId;
        }

        $query
            ->select([
                $this->aliasField('student_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('academic_period_id'),
                'bm_date' => 'UserBodyMasses.date',
                'bm_height' => 'UserBodyMasses.height',
                'bm_weight' => 'UserBodyMasses.weight',
                'bm_body_mass_index' => 'UserBodyMasses.body_mass_index',
                'bm_comment' => 'UserBodyMasses.comment'
            ])
            ->contain([
                'Users' => [
                    'fields' => [
                        'openemis_no' => 'Users.openemis_no',
                        'Users.first_name',
                        'Users.middle_name',
                        'Users.third_name',
                        'Users.last_name',
                        'date_of_birth' => 'Users.date_of_birth'
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
                        'name'
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
             ->where($conditions);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();

        $extraFields = [];

        $extraFieldsFirst[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
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
            'key' => 'date',
            'field' => 'bm_date',
            'type' => 'string',
            'label' => __('Date')
        ];

        $extraFields[] = [
            'key' => 'height',
            'field' => 'bm_height',
            'type' => 'string',
            'label' => __('Height')
        ];

        $extraFields[] = [
            'key' => 'weight',
            'field' => 'bm_weight',
            'type' => 'string',
            'label' => __('Weight')
        ];

        $extraFields[] = [
            'key' => 'body_mass_index',
            'field' => 'bm_body_mass_index',
            'type' => 'string',
            'label' => __('Body Mass Index')
        ];

        $extraFields[] = [
            'key' => 'Comment',
            'field' => 'bm_comment',
            'type' => 'string',
            'label' => __('Comment')
        ];            

        $newFields = array_merge($extraFieldsFirst, $cloneFields, $extraFields);
        $fields->exchangeArray($newFields);
    }
}
