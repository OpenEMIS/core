<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class InstitutionSubjectsClassesTable extends AppTable  {
	public function initialize(array $config) {
            $this->table('institution_subjects');
            parent::initialize($config);
            $this->addBehavior('Report.ReportList');
            $this->addBehavior('Excel', [
                'pages' => false
            ]);
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets)
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
        $education_grade_id = $requestData->education_grade_id;
       
        $conditions = [];
        if (!empty($academicPeriodId)) {
            $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        
        if (!empty($institutionId)) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        }
        //POCOR-5852 starts
        if ($education_grade_id > 0) {
            $conditions[$this->aliasField('education_grade_id')] = $education_grade_id;
        }
        //POCOR-5852 ends
         $query
            ->select([
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name' ,
                'status' => 'Users.status',
                'Student_name' => $query->func()->concat([
                    'Users.first_name' => 'literal',
                    " ",
                    'Users.last_name' => 'literal'
                    ]),
                'openemis_number' => 'Users.openemis_no',
               
                'subjects' => $query->func()->group_concat(['DISTINCT EducationSubjects.name' => 'literal',
                    " "
                ]),
              
                
                'identity_number' => 'Users.identity_number',
                'identity_type' => 'IdentityTypes.name',
                'academic_period' => 'AcademicPeriods.name',
                'education_grade' => 'EducationGrades.name',
                'class' => 'InstitutionClasses.name'
             ])
            ->innerJoin(['InstitutionStudents' => 'institution_students'], [
                            $this->aliasfield('institution_id') . ' = '.'InstitutionStudents.institution_id',
                        ])
             ->innerJoin(['Users' => 'security_users'], [
                            'Users.id = ' . 'InstitutionStudents.student_id',
                        ])
             ->leftJoin(['Institutions' => 'institutions'], [
                           $this->aliasfield('institution_id') . ' = '.'Institutions.id'
                        ])
             ->leftJoin(['AcademicPeriods' => 'academic_periods'], [
                           $this->aliasfield('academic_period_id') . ' = AcademicPeriods.id'
                        ])
             ->leftJoin(['EducationGrades' => 'education_grades'], [
                           $this->aliasfield('education_grade_id') . ' = EducationGrades.id'
                        ])
             ->leftJoin(['IdentityTypes' => 'identity_types'], [
                           'Users.identity_type_id' . ' = IdentityTypes.id'
                        ])
              ->leftJoin(['EducationSubjects' => 'education_subjects'], [
                           $this->aliasfield('education_subject_id') . ' = EducationSubjects.id'
                        ])
               ->leftJoin(['InstitutionClassSubjects' => 'institution_class_subjects'], [
                           $this->aliasfield('id') . ' = InstitutionClassSubjects.institution_subject_id'
                        ])
              ->leftJoin(['InstitutionClasses' => 'institution_classes'], [
                           'InstitutionClassSubjects.institution_class_id' . ' = InstitutionClasses.id'
                        ])
               ->where($conditions)
              ->group(['Users.id']);
    }

  

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) 
    {   
         $cloneFields = $fields->getArrayCopy();

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
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period',
            'type' => 'integer',
            'label' => __('Academic Period')
        ];

        $extraFields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade',
            'type' => 'string',
            'label' => __('Education Grade')
        ];



        $extraFields[] = [
            'key' => 'Users.status',
            'field' => 'status',
            'type' => 'string',
            'label' => __('Student Status')
        ];

        $extraFields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_number',
            'type' => 'integer',
            'label' => __('OpenEMIS ID')
        ];



        $extraFields[] = [
            'key' => '',
            'field' => 'Student_name',
            'type' => 'string',
            'label' => __('Student Name')
        ];

        $extraFields[] = [
            'key' => 'IdentityTypes.name',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Default Identity Type')
        ];

        $extraFields[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];


        $extraFields[] = [
            'key' => 'InstitutionClasses.name',
            'field' => 'class',
            'type' => 'string',
            'label' => __('Class Name')
        ];

        $extraFields[] = [
            'key' => 'EducationSubjects.name',
            'field' => 'subjects',
            'type' => 'string',
            'label' => __('Subjects')
        ];
        
       $newFields = $extraFields;
       $fields->exchangeArray($newFields);
       
   }
}
