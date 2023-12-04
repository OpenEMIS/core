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

class CompetenciesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_competency_results');
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
            $condition = "AND institutions.id = ".$institutionId;
        }

        if (empty($EducationGradeId) && $EducationGradeId == "-1") { 
            $condition = NULL;
        }else{
            $condition = "AND education_grades.id = ".$EducationGradeId;
        }
        
        if (empty($InstitutionClassId) || $InstitutionClassId == "-1") { 
            $condition = NULL;
        }else{
            $condition = "AND institution_classes.id = ".$InstitutionClassId;
        }
        $query->select(['academic_period' => 'academic_periods.name',
                        'area_code' => 'areas.code',
                        'area_name' => 'areas.name',
                        'institution_code' => 'institutions.code',
                        'institution_name' => 'institutions.name',
                        'education_grade_name' => 'education_grades.name',
                        'class_name' => "(IFNULL(institution_classes.name,''))",
                        'openemis_no' => 'security_users.openemis_no',
                        'student_name' => "(REPLACE(CONCAT_WS(' ',security_users.first_name,security_users.middle_name,security_users.third_name,security_users.last_name), '  ', ' '))",
                        'competency_item' => 'competency_items.name',
                        'competency_criteria_code' => "(IFNULL(competency_criterias.code,''))",
                        'competency_criteria_name' => "(IFNULL(competency_criterias.name,''))",
                        'result' => "(IFNULL(competency_grading_options.name,''))",
                        'comment' => "(IFNULL(institution_competency_results.comments,''))"
        ])
        ->from(['institution_competency_results' => 'institution_competency_results'])
            ->innerJoin(
                ['security_users' => 'security_users'],
                ['security_users.id = institution_competency_results.student_id']
            )
            ->innerJoin(
                ['institutions' => 'institutions'],
                ['institutions.id = institution_competency_results.institution_id']
            )
            ->innerJoin(
                ['areas' => 'areas'],
                ['areas.id = institutions.area_id']
            )
            ->innerJoin(
                ['competency_templates' => 'competency_templates'],
                ['competency_templates.id = institution_competency_results.competency_template_id']
            )
            ->innerJoin(
                ['education_grades' => 'education_grades'],
                ['education_grades.id = competency_templates.education_grade_id']
            )
            ->innerJoin(
                ['competency_items' => 'competency_items'],
                ['competency_items.id = institution_competency_results.competency_item_id']
            )
            ->innerJoin(
                ['academic_periods' => 'academic_periods'],
                ['academic_periods.id = institution_competency_results.academic_period_id']
            )
            ->LeftJoin(
                ['competency_criterias' => 'competency_criterias'],
                ['competency_criterias.id = institution_competency_results.competency_criteria_id']
            )
            ->LeftJoin(
                ['competency_grading_options' => 'competency_grading_options'],
                ['competency_grading_options.id = institution_competency_results.competency_grading_option_id']
            )
            ->LeftJoin(
                ['institution_class_students' => 'institution_class_students'],
                ['institution_class_students.student_id = institution_competency_results.student_id',
                 'institution_class_students.institution_id = institution_competency_results.institution_id',
                 'institution_class_students.academic_period_id = institution_competency_results.academic_period_id'
                ]
            )
            ->LeftJoin(
                ['institution_classes' => 'institution_classes'],
                ['institution_classes.id = institution_class_students.institution_class_id']
            )
            ->where(['academic_periods.id'=>$academicPeriodId,$condition])
            ->order(['areas.name','institutions.name','education_grades.name',"(IFNULL(institution_classes.name,''))"
                        ,'security_users.openemis_no'
                        ,"competency_items.name"
                        ,"(IFNULL(competency_criterias.code,''))"
                        ,"(IFNULL(competency_grading_options.name,''))"
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
            'label' => __('Education Grade')
        ];
        $extraFields[] = [
            'key' => 'institution_class_name',
            'field' => 'class_name',
            'type' => 'string',
            'label' => __('Institution Class')
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
            'key' => 'competency_item',
            'field' => 'competency_item',
            'type' => 'string',
            'label' => __('Competency Item')
        ];
        $extraFields[] = [
            'key' => 'competency_criteria_code',
            'field' => 'competency_criteria_code',
            'type' => 'string',
            'label' => __('Competency Criteria Code')
        ];
        $extraFields[] = [
            'key' => 'competency_criteria_name',
            'field' => 'competency_criteria_name',
            'type' => 'string',
            'label' => __('Competency Criteria Name')
        ];
        $extraFields[] = [
            'key' => 'result',
            'field' => 'result',
            'type' => 'string',
            'label' => __('Result')
        ];
        $extraFields[] = [
            'key' => 'comment',
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Comment')
        ];
        
        
        $fields->exchangeArray($extraFields);

    }
}
