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

class SubjectsBookListsTable extends AppTable
{

    const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;

    public function initialize(array $config): void
    {

        $this->setTable('institution_subject_students');
        parent::initialize($config);
        $this->belongsTo('IdentityTypes', ['className' => 'FieldOption.IdentityTypes']);
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
            'name' => $this->getAlias(),
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
       $educationGradeId = $requestData->education_grade_id;
       $educationSubjectId = $requestData->education_subject_id;
       $areaId = $requestData->area_education_id;
       $conditions = [];

        if (!empty($educationSubjectId) && $educationSubjectId != -1) { // POCOR-8993
            $conditions[$this->aliasField('education_subject_id')] = $educationSubjectId;
        }
        if (!empty($institutionId) && $institutionId != -1) { // POCOR-8993
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }
        if (!empty($academicPeriodId) && $academicPeriodId != -1) { // POCOR-8993
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        if (!empty($educationGradeId) && $educationGradeId != -1) { // POCOR-8993
            $conditions[$this->aliasField('education_grade_id')] = $educationGradeId;
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions['Institutions.area_id'] = $areaId;
        }
       $query
            ->select([
                'student_id' =>'Users.id',
                'student_first_name' => 'Users.first_name',
                'student_last_name' => 'Users.last_name',
                'openemis_no' => 'Users.openemis_no',
                'identity_number' => 'Users.identity_number',
                'institution_name' => 'Institutions.name',
                'institution_code' => 'Institutions.code',
                'education_grade_name' => 'EducationGrades.name',
                'date_of_birth' => 'Users.date_of_birth',
                'institution_class_name' => 'InstitutionClasses.name',
                'education_subject_name' => 'EducationSubjects.name',
                'textbook_code' => 'Textbooks.code',
                'textbook_name' => 'Textbooks.title',
                'start_date' => 'InstitutionStudents.start_date',//POCOR-5740
            ])
           ->InnerJoin(['Users' => 'security_users'], [
                            'Users.id = ' . 'SubjectsBookLists.student_id'
                       ])
           ->InnerJoin(['Institutions' => 'institutions'], [
                      'SubjectsBookLists.institution_id = ' . 'Institutions.id'
                       ])

            ->InnerJoin(['EducationGrades' => 'education_grades'], [
                     'SubjectsBookLists.education_grade_id = ' . 'EducationGrades.id'
                       ])

            ->leftJoin(['InstitutionClasses' => 'institution_classes'], [
                    'InstitutionClasses.id = ' . 'SubjectsBookLists.institution_class_id'
                      ])
            //POCOR-5740 starts
            ->innerJoin(['InstitutionStudents' => 'institution_students'], [ // POCOR-8993 instead of left join, to remove deleted records
                      'InstitutionStudents.student_id = ' . 'SubjectsBookLists.student_id'
                    ])
            //POCOR-5740 ends
            ->innerJoin(['EducationSubjects' => 'education_subjects'], [ // POCOR-8993  instead of left join, to remove deleted records
                    'EducationSubjects.id = ' . 'SubjectsBookLists.education_subject_id'
                     ])
            ->innerJoin(['Textbooks' => 'textbooks'], [ // POCOR-8993  instead of left join, to remove deleted records
                    'Textbooks.education_subject_id = ' . 'SubjectsBookLists.education_subject_id'
                    ])
            ->where($conditions)
            ->group(['Users.id', // POCOR-8993  to remove dublicates
                'Textbooks.code', // POCOR-8993 to remove dublicates
                'Textbooks.title', // POCOR-8993  to remove dublicates
            ], true)
            //POCOR-5740 starts
            ->order([
                // POCOR-8993 added order to make list more clear
                'Users.first_name' => 'ASC',
                'Users.last_name' => 'ASC',
                'InstitutionStudents.start_date' => 'DESC',
                'Textbooks.title' => 'ASC',
            ], true);
            //POCOR-5740 ends
        }


    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();

        $extraFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        $extraFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $extraFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Education Grade')
        ];

        $extraFields[] = [
            'key' => 'InstitutionClasses.name',
            'field' => 'institution_class_name',
            'type' => 'string',
            'label' => __('Class Name')
        ];

        $extraFields[] = [
            'key' => 'EducationSubjects.name',
            'field' => 'education_subject_name',
            'type' => 'string',
            'label' => __('Subject Name')
        ];

        $extraFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS ID')
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
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity number')
        ];

        $extraFields[] = [
            'key' => 'Textbooks.code',
            'field' => 'textbook_code',
            'type' => 'string',
            'label' => __('Textbook Code')
        ];

        $extraFields[] = [
            'key' => 'Textbooks.title',
            'field' => 'textbook_name',
            'type' => 'string',
            'label' => __('Textbook name')
        ];
        //POCOR-5740 starts
        $extraFields[] = [
            'key' => 'institution_students.start_date',
            'field' => 'start_date',
            'type' => 'string',
            'label' => __('Date of Admission')
        ];
        //POCOR-5740 ends
        $fields->exchangeArray($extraFields);
     }
}
