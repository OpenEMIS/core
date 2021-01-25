<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

class StudentNotAssignedClassTable extends AppTable
{
    
    const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;

    public function initialize(array $config)
    {
        $this->table('institution_students');
        parent::initialize($config);
        $this->belongsTo('InstitutionClassStudents', ['className' => 'Institution.InstitutionClassStudents']);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'pages' => false
        ]);
    }

    public function beforeAction(Event $event)
    { 
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
    }

   public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
       $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
       $requestData = json_decode($settings['process']['params']);
       $academicPeriodId = $requestData->academic_period_id;
       $institutionId = $requestData->institution_id;
       $insClassStudent = TableRegistry::get('Institution.InstitutionClassStudents');
       $analysis = '';

       $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($institutionId)) {
            $conditions['Institutions.id'] = $institutionId;
        }

        $newConditions = [];
        if (!empty($academicPeriodId)) {
            $newConditions[$insClassStudent->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($institutionId)) {
            $newConditions['Institutions.id'] = $institutionId;
        }

        $subquery = $insClassStudent
            ->find()
            ->select(['InstitutionClassStudents.student_id'])
            ->where([$newConditions]);

        $query
            ->select([   
                'student_id' =>'Users.id',             
                'student_first_name' => 'Users.first_name',
                'student_last_name' => 'Users.last_name',
                'openemis_no' => 'Users.openemis_no',
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'education_grade_name' => 'EducationGrades.name',
                'gender_name' => 'Genders.name',
                'date_of_birth' => 'Users.date_of_birth',
            ])
           ->leftJoin(['Users' => 'security_users'], [
                            'Users.id = ' . 'StudentNotAssignedClass.student_id'
                       ])

            ->leftJoin(['Genders' => 'genders'], [
                        'Users.gender_id = ' . 'Genders.id'
                    ])

           ->leftJoin(['Institutions' => 'institutions'], [
                      'StudentNotAssignedClass.institution_id = ' . 'Institutions.id'
                       ])

           ->leftJoin(['StudentStatuses' => 'student_statuses'], [
                       'StudentNotAssignedClass.student_status_id = ' . 'StudentStatuses.id'
                      ])

            ->leftJoin(['EducationGrades' => 'education_grades'], [
                     'StudentNotAssignedClass.education_grade_id = ' . 'EducationGrades.id'
                    ])
            ->group(['StudentNotAssignedClass.student_id'])
            ->where([
                    $this->aliasfield('student_id NOT IN ') => $subquery,
                    'StudentStatuses.code' => 'CURRENT',
                    $conditions
                    ]);
       }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();

        $extraFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
        ];

        $extraFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $extraFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        
        $extraFields[] = [
            'key' => 'Users.first_name',
            'field' => 'student_first_name',
            'type' => 'string',
            'label' => __('Student First Name')
        ];    

        $extraFields[] = [
            'key' => 'Users.last_name',
            'field' => 'student_last_name',
            'type' => 'string',
            'label' => __('Student Last Name')
        ];

        $extraFields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'string',
            'label' => __('Date Of Birth')
        ];

        $extraFields[] = [
            'key' => 'Genders.name',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => __('Gender')
        ];

       $extraFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Education Grade')
        ];

        $extraFields[] = [
            'key' => 'analysis',
            'field' => 'analysis',
            'type' => 'string',
            'label' => __('Analysis')
        ];

       $fields->exchangeArray($extraFields);
    }

    public function onExcelGetAnalysis(Event $event, Entity $entity)
    {
        $value = '#N/A';
        
        return $value;
    }

}
