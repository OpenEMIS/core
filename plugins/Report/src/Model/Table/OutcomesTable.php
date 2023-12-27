<?php
namespace Report\Model\Table;

use ArrayObject;
use ZipArchive;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class OutcomesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_outcome_results');
        parent::initialize($config);

       
        $this->addBehavior('Excel', [
            'excludes' => ['is_student', 'photo_name', 'is_staff', 'is_guardian',  'super_admin', 'status'],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {
        $requestData = json_decode($settings['process']['params']);
        
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $EducationGradeId = $requestData->education_grade_id;
        $EducationSubjectId = $requestData->education_subject_id;
        $InstitutionClassId = $requestData->institution_class_id;
        $selectedArea = $requestData->area_education_id;
        $conditions = [];
        if ($areaId != -1 && $areaId != '') {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }
            $conditions = "AND institutions.area_id IN = ".$allselectedAreas;
        }
        if (empty($institutionId) && $institutionId == 0) { 
           $condition = NULL;
        }else{
            $condition = "AND institution_outcome_results.institution_id = ".$institutionId;
        }

        if (empty($EducationGradeId) && $EducationGradeId == "-1") { 
            $condition = NULL;
        }else{
            $condition = "AND education_grades.id = ".$EducationGradeId;
        }

        if (empty($EducationSubjectId) && $EducationSubjectId == "-1") { 
            $condition = NULL;
        }else{
            $condition = "AND outcome_criterias.education_subject_id = ".$EducationSubjectId;
        }
        
        if (empty($InstitutionClassId) || $InstitutionClassId == "-1") { 
            $condition = NULL;
        }else{
            $condition = "AND institution_classes.id = ".$InstitutionClassId;
        }
        $condition = "(IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_class_students.student_status_id = 1, institution_class_students.student_status_id IN (1, 7, 6, 8)))";

        
        $query->select(['academic_period' => 'academic_periods.name',
                        'area_code' => 'areas.code',
                        'area_name' => 'areas.name',
                        'institution_code' => 'institutions.code',
                        'institution_name' => 'institutions.name',
                        'education_grade_name' => 'education_grades.name',
                        'class_name' => "institution_classes.name",
                        'openemis_no' => 'security_users.openemis_no',
                        'student_name' => "(REPLACE(CONCAT_WS(' ',security_users.first_name,security_users.middle_name,security_users.third_name,security_users.last_name), '  ', ' '))",
                        'subject' => 'education_subjects.name',
                        'criteria_code' => "outcome_criterias.code",
                        'criteria_name' => "outcome_criterias.name",
                        'result' => "outcome_grading_options.name"
        ])
        ->from(['institution_outcome_results' => 'institution_outcome_results'])
            ->innerJoin(
                ['institutions' => 'institutions'],
                ['institutions.id = institution_outcome_results.institution_id']
            )
            ->innerJoin(
                ['areas' => 'areas'],
                ['areas.id = institutions.area_id']
            )
            ->innerJoin(
                ['outcome_grading_options' => 'outcome_grading_options'],
                ['institution_outcome_results.outcome_grading_option_id = outcome_grading_options.id']
            )
            ->innerJoin(
                ['education_grades' => 'education_grades'],
                ['education_grades.id = institution_outcome_results.education_grade_id']
            )
            ->innerJoin(
                ['outcome_criterias' => 'outcome_criterias'],
                ['institution_outcome_results.outcome_criteria_id = outcome_criterias.id']
            )
            ->innerJoin(
                ['education_subjects' => 'education_subjects'],
                ['outcome_criterias.education_subject_id = education_subjects.id']
            )
            
            ->innerJoin(
                ['institution_class_students' => 'institution_class_students'],
                ['institution_class_students.student_id = institution_outcome_results.student_id',
                 'institution_class_students.education_grade_id = institution_outcome_results.education_grade_id',
                 'institution_class_students.institution_id = institution_outcome_results.institution_id',
                 'institution_class_students.academic_period_id = institution_outcome_results.academic_period_id'
                ]
            )
            ->innerJoin(
                ['institution_classes' => 'institution_classes'],
                ['institution_classes.id = institution_class_students.institution_class_id']
            )
            ->innerJoin(
                ['security_users' => 'security_users'],
                ['institution_class_students.student_id = security_users.id']
            )
            ->innerJoin(
                ['academic_periods' => 'academic_periods'],
                ['academic_periods.id = institution_outcome_results.academic_period_id']
            )
            ->where(['academic_periods.id'=>$academicPeriodId,$condition])
            ->order(['institution_class_students.education_grade_id' =>'ASC','institution_classes.id' =>'ASC'
            ]);
    }

    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::get('Area.Areas');
        $result = $Areas->find()
                            ->where([
                                $Areas->aliasField('parent_id') => $id
                            ]) 
                             ->toArray();
        foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $extraFields = [];

        $extraFields[] = [
            'key' => 'academic_period',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $extraFields[] = [
            'key' => 'area_code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $extraFields[] = [
            'key' => 'area_name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Name')
        ];
        $extraFields[] = [
            'key' => '',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];
        $extraFields[] = [
            'key' => '',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution')
        ];
        $extraFields[] = [
            'key' => 'education_grade_name',
            'field' => 'education_grade_name',
            'type' => 'string',
            'label' => __('Grade')
        ];
        $extraFields[] = [
            'key' => 'institution_class_name',
            'field' => 'class_name',
            'type' => 'string',
            'label' => __('Class')
        ];
        
        $extraFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('OpenEMIS No')
        ];
        
        $extraFields[] = [
            'key' => 'student_name',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student Name')
        ];
        $extraFields[] = [
            'key' => 'subject',
            'field' => 'subject',
            'type' => 'string',
            'label' => __('Subject')
        ];
        $extraFields[] = [
            'key' => 'criteria_code',
            'field' => 'criteria_code',
            'type' => 'string',
            'label' => __('Criteria Code')
        ];
        $extraFields[] = [
            'key' => 'criteria_name',
            'field' => 'criteria_name',
            'type' => 'string',
            'label' => __('Criteria')
        ];
        $extraFields[] = [
            'key' => 'result',
            'field' => 'result',
            'type' => 'string',
            'label' => __('Result')
        ];
        
        
        
        $fields->exchangeArray($extraFields);

    }
}
