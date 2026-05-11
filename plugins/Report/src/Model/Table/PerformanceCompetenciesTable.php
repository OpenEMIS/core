<?php
namespace Report\Model\Table;

use ArrayObject;
use ZipArchive;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;

/**
 * POCOR-9077
 *
 * This method prepares the base query used for exporting
 * Performance > Competencies data to Excel.
 * It retrieves competency assessment data
 *
 */
class PerformanceCompetenciesTable extends AppTable
{
    public function initialize(array $config): void
    {
        $this->setTable('institution_competency_results');
        parent::initialize($config);
        $this->addBehavior('Excel', [
            'excludes' => ['is_student', 'photo_name', 'is_staff', 'is_guardian',  'super_admin', 'status'],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);

        $academicPeriodId   = $requestData->academic_period_id ?? null;
        $institutionId      = $requestData->institution_id ?? null;
        $selectedArea       = $requestData->area_education_id ?? null;
        $educationGradeId   = $requestData->education_grade_id ?? null;
        $competencyPeriodId = $requestData->Competencies_period ?? null;

        $where = [];

        if (!empty($academicPeriodId)) {
            $where['AcademicPeriods.id'] = $academicPeriodId;
        }

        if (!empty($selectedArea) && $selectedArea != -1) {
            $areaIds = [];
            $childAreas = $this->getChildren($selectedArea, $areaIds);
            $allAreas = array_merge([$selectedArea], $childAreas);

            $where['Institutions.area_id IN'] = $allAreas;
        }

        if (!empty($institutionId) && $institutionId != -1) {
            $where['Institutions.id'] = $institutionId;
        }

        if (!empty($educationGradeId) && $educationGradeId != -1) {
            $where['EducationGrades.id'] = $educationGradeId;
        }

        if (!empty($competencyPeriodId) && $competencyPeriodId != -1) {
            $where['CompetencyPeriods.id'] = $competencyPeriodId;
        }

        $query
            ->select([
                'academic_period' => 'AcademicPeriods.name',
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'education_grade_name' => 'EducationGrades.name',
                'competency_period' => 'CompetencyPeriods.name',
                'competency_item' => 'CompetencyItems.name',
                'openemis_no' => 'SecurityUsers.openemis_no',

                'student_name' => $query->newExpr(
                    "CONCAT(
                        COALESCE(NULLIF(SecurityUsers.preferred_name,''), SecurityUsers.first_name),
                        ' ',
                        SecurityUsers.last_name
                    )"
                ),

                'competency_criteria_name' => 'CompetencyCriterias.name',
                'competency_mark' => 'CompetencyGradingOptions.code',
                'competency_grading_options_name' => 'CompetencyGradingOptions.name',
                'competency_grading_types' => 'CompetencyGradingTypes.name',
                'result' => $this->aliasField('comments'),
                'comment' => 'InstitutionCompetencyItemComments.comments'
            ])
            ->innerJoin(
                ['AcademicPeriods' => 'academic_periods'],
                ['AcademicPeriods.id = ' . $this->aliasField('academic_period_id')]
            )
            ->innerJoin(
                ['Institutions' => 'institutions'],
                ['Institutions.id = ' . $this->aliasField('institution_id')]
            )
            ->leftJoin(
                ['Areas' => 'areas'],
                ['Areas.id = Institutions.area_id']
            )
            ->leftJoin(
                ['SecurityUsers' => 'security_users'],
                ['SecurityUsers.id = ' . $this->aliasField('student_id')]
            )
            ->leftJoin(
                ['InstitutionClassStudents' => 'institution_class_students'],
                [
                    'InstitutionClassStudents.student_id =  ' . $this->aliasField('student_id'),
                    'InstitutionClassStudents.institution_id =  ' . $this->aliasField('institution_id'),
                    'InstitutionClassStudents.academic_period_id = ' . $this->aliasField('academic_period_id')
                ]
            )
            ->leftJoin(
                ['EducationGrades' => 'education_grades'],
                ['EducationGrades.id = InstitutionClassStudents.education_grade_id']
            )
            ->innerJoin(
                ['CompetencyPeriods' => 'competency_periods'],
                ['CompetencyPeriods.id = ' . $this->aliasField('competency_period_id')]
            )
            ->innerJoin(
                ['CompetencyItems' => 'competency_items'],
                ['CompetencyItems.id = ' . $this->aliasField('competency_item_id')]
            )
            ->leftJoin(
                ['CompetencyCriterias' => 'competency_criterias'],
                ['CompetencyCriterias.id = ' . $this->aliasField('competency_criteria_id')]
            )
            ->leftJoin(
                ['CompetencyGradingOptions' => 'competency_grading_options'],
                ['CompetencyGradingOptions.id = ' . $this->aliasField('competency_grading_option_id')]
            )
            ->leftJoin(
                ['CompetencyGradingTypes' => 'competency_grading_types'],
                ['CompetencyGradingTypes.id = CompetencyGradingOptions.competency_grading_type_id']
            )
            ->leftJoin(
                ['InstitutionCompetencyItemComments' => 'institution_competency_item_comments'],
                [
                    'InstitutionCompetencyItemComments.student_id = ' . $this->aliasField('student_id'),
                    'InstitutionCompetencyItemComments.institution_id = ' . $this->aliasField('institution_id'),
                    'InstitutionCompetencyItemComments.academic_period_id = ' . $this->aliasField('academic_period_id'),
                    'InstitutionCompetencyItemComments.competency_period_id = ' . $this->aliasField('competency_period_id'),
                    'InstitutionCompetencyItemComments.competency_item_id = ' . $this->aliasField('competency_item_id')
                ]
            )
            ->where($where)
            ->order([
                'Institutions.name' => 'ASC',
                'EducationGrades.name' => 'ASC',
                'SecurityUsers.openemis_no' => 'ASC',
                'student_name' => 'ASC',
                'CompetencyItems.name' => 'ASC',
                'CompetencyCriterias.code' => 'ASC',
                'CompetencyGradingOptions.code' => 'ASC'
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

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
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
            'key' => 'competency_period',
            'field' => 'competency_period',
            'type' => 'string',
            'label' => __('Competency Period')
        ];
        $extraFields[] = [
            'key' => 'competency_criteria_name',
            'field' => 'competency_criteria_name',
            'type' => 'string',
            'label' => __('Competency Criteria Name')
        ];
        $extraFields[] = [
            'key' => 'competency_mark',
            'field' => 'competency_mark',
            'type' => 'string',
            'label' => __('Competency Mark')
        ];

        $extraFields[] = [
            'key' => 'competency_grading_options_name',
            'field' => 'competency_grading_options_name',
            'type' => 'string',
            'label' => __('Competency Grading Option')
        ];
        $extraFields[] = [
            'key' => 'competency_grading_types',
            'field' => 'competency_grading_types',
            'type' => 'string',
            'label' => __('Competency Grading Type')
        ];
        $extraFields[] = [
            'key' => 'result',
            'field' => 'result',
            'type' => 'string',
            'label' => __('Competency Comment')
        ];
        $extraFields[] = [
            'key' => 'comment',
            'field' => 'comment',
            'type' => 'string',
            'label' => __('Overall Comment')
        ];
        
        $fields->exchangeArray($extraFields);

    }
}
