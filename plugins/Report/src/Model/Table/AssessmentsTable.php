<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Cake\Database\Expression\QueryExpression;

class AssessmentsTable extends AppTable
{
       // For reports
    private $assessmentItemResults = [];
    private $lastQueriedClass = null;
    private $allowedSubjects = [];
    private $assessmentPeriodWeightedMark = 0;
    private $totalMark = 0;
    private $totalWeightedMark = 0;
    private $results;
    private $i = 1;

    private $assessmentPeriodWeights = [];

    public function initialize(array $config): void
    {
        $this->setTable('institution_class_students');
        parent::initialize($config);
         $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id', 'joinType' => 'INNER']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'joinType' => 'INNER']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'joinType' => 'INNER']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses', 'joinType' => 'INNER']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'joinType' => 'INNER']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'joinType' => 'INNER']);
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'next_institution_class_id']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);

        $this->hasMany('SubjectStudents', [
            'className' => 'Institution.InstitutionSubjectStudents',
            'foreignKey' => ['institution_class_id', 'student_id'],
            'bindingKey' => ['institution_class_id', 'student_id']
        ]);
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);

        $this->addBehavior('Report.ReportList');
    }
    
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $institutionId = $requestData->institution_id ?? null;
        $areaId = $requestData->area_education_id ?? null;
        $academicPeriodId = $requestData->academic_period_id ?? null;
        $gradeId = $requestData->education_grade_id ?? null;
        $superAdmin = $requestData->super_admin ?? false;
        $userId = $requestData->user_id ?? null;
        $academicTerm = $requestData->academic_term ?? null;

        $StudentStatuses = $this->StudentStatuses;
        $institutionIds = [];
        $conditions = [];
        $conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        // Area filter
        /*if (!empty($areaId) && $areaId > 0) {
            $conditions[$this->aliasField('area_id')] = $areaId;
        }*/

        // Grade filter
        if (!empty($gradeId) && $gradeId > 0) {
            $conditions[$this->aliasField('education_grade_id')] = $gradeId;
        }

        // Institution filter or fallback to accessible institutions
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        } else {
            if (!$superAdmin && !empty($userId)) {
                $institutionObj = $this->Institutions->find('byAccess', ['userId' => $userId])->toArray();
                if (!empty($institutionObj)) {
                    foreach ($institutionObj as $value) {
                        $institutionIds[] = $value->id;
                    }
                    $conditions[$this->aliasField('institution_id IN')] = $institutionIds;
                } else {
                    // If no accessible institutions, return empty
                    $query->where(['1 = 0']);
                    return;
                }
            }
        }

        // Exclude withdrawn/transferred students
        $conditions[$StudentStatuses->aliasField('code NOT IN ')] = ['TRANSFERRED', 'WITHDRAWN'];

        $query
            ->contain([
                'InstitutionClasses.Institutions',
                'AcademicPeriods',
                'Users.BirthplaceAreas',
                'Users.Nationalities.NationalitiesLookUp'
            ])
            ->innerJoin(['InstitutionClassGrades' => 'institution_class_grades'], [
                'InstitutionClassGrades.institution_class_id = ' . $this->aliasField('institution_class_id')
            ])
            ->leftJoin(['StudentStatuses' => 'student_statuses'], [
                'StudentStatuses.id = ' . $this->aliasField('student_status_id')
            ])
            ->select([
                'code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'institution_id' => 'Institutions.id',
                'openemis_number' => 'Users.openemis_no',
                'birth_place_area' => 'BirthplaceAreas.name',
                'dob' => 'Users.date_of_birth',
                'class_name' => 'InstitutionClasses.name',
                'academic_period_name' => 'AcademicPeriods.name',
                'academic_period_id' => 'AcademicPeriods.id',
                'user_name' => $query->func()->concat([
                                'Users.first_name' => 'literal',
                                ' ',
                                'Users.middle_name' => 'literal',
                                ' ',
                                'Users.third_name' => 'literal',
                                ' ',
                                'Users.last_name' => 'literal',
                            ]),
                'student_id' => 'Assessments.student_id',
                'institution_class_id' => 'InstitutionClasses.id',

            ])
            ->where($conditions);
           
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $originalField)
    {
        $requestData = json_decode($settings['process']['params']);
        $institutionId = $requestData->institution_id ?? null;
        $academicPeriodId = $requestData->academic_period_id;
        $educationGradeId = $requestData->education_grade_id;
        // Dynamically fetch assessment_id
        $AssessmentsTable = TableRegistry::getTableLocator()->get('Assessment.Assessments');
        $assessment = $AssessmentsTable->find()
            ->where([
                'academic_period_id IS' => $academicPeriodId,
                'education_grade_id IS' => $educationGradeId // Optional: only active assessments
            ])
            ->orderDesc('id')
            ->first();

        if (!$assessment) {
            // Exit or throw an exception gracefully
            return;
        }

        $assessmentId = $assessment->id;

        $AssessmentPeriodsTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentPeriods');
        $AssessmentItemsGradingTypesTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemsGradingTypes');
        $AssessmentItemsTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentItems');

        $fields = new ArrayObject();
        $fields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period_name',
            'type' => 'integer',
            'label' => '',
        ];
        $fields[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_number',
            'type' => 'string',
            'label' => '',
        ];
        $fields[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'Users.first_name',
            'field' => 'user_name',
            'type' => 'string',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'InstitutionClasses.class_name',
            'field' => 'class_name',
            'type' => 'string',
            'label' => __('Class'),
        ];

        $fields[] = [
            'key' => 'UserNationalities.nationality_id',
            'field' => 'nationality',
            'type' => 'nationality',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'Users.birthplace_area_id',
            'field' => 'birth_place_area',
            'type' => 'string',
            'label' => '',
        ];

        $fields[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'dob',
            'type' => 'date',
            'label' => '',
        ];



        $assessmentPeriods = $AssessmentPeriodsTable
            ->find()
            ->where([$AssessmentPeriodsTable->aliasField('assessment_id IS') => $assessmentId])
            ->order([$AssessmentPeriodsTable->aliasField('academic_term'), $AssessmentPeriodsTable->aliasField('start_date')])
            ->toArray();

        $assessmentGradeTypes = $AssessmentItemsGradingTypesTable->getAssessmentGradeTypes($assessmentId);
        $assessmentSubjects = TableRegistry::getTableLocator()->get('Assessment.AssessmentItems')->getSubjects($assessmentId);
        foreach ($assessmentSubjects as $subject) {
            foreach ($assessmentPeriods as $period) {
                $subjectId = $subject['subject_id'];
                $assessmentPeriodId = $period->id;
                $resultType = $assessmentGradeTypes[$subjectId][$assessmentPeriodId];

                $label = __($subject['education_subject_name']).' - '.$period->name;
                if ($resultType == 'MARKS') {
                    $label = $label.' ('.$period->weight.') ';
                }
                $fields[] = [
                    'key' => $subject['assessment_item_id'],
                    'field' => 'assessment_item',
                    'type' => 'subject',
                    'label' => $label,
                    'institutionId' => $institutionId,
                    'assessmentId' => $assessmentId,
                    'subjectId' => $subjectId,
                    'assessmentPeriodWeight' => $period->weight,
                    'academicPeriodId' => $academicPeriodId,
                    'assessmentPeriodId' => $assessmentPeriodId,
                    'resultType' => $resultType

                ];
            }

            $fields[] = [
                'key' => 'assessment_period_weighted_mark',
                'field' => 'assessment_item',
                'type' => 'assessment_period_weighted_mark',
                'label' => __('Weighted Marks').' ('.$subject['subject_weight'].') ',
                'subjectWeight' => $subject['subject_weight']
            ];
        }

        $fields[] = [
            'key' => 'total_mark',
            'field' => 'assessment_item',
            'type' => 'total_mark',
            'label' => __('Total Marks')
        ];

        $fields[] = [
            'key' => 'total_weighted_mark',
            'field' => 'assessment_item',
            'type' => 'total_weighted_mark',
            'label' => __('Total Weighted Marks')
        ];

        $originalField->exchangeArray($fields);
    }

    public function onExcelRenderNationality(Event $event, Entity $entity, array $attr)
    {
        if ($entity->user->nationalities) {
            $nationalities = $entity->user->nationalities;
            $allNationalities = '';
            foreach ($nationalities as $nationality) {
                $allNationalities .= $nationality->nationalities_look_up->name . ', ';
            }
            return rtrim($allNationalities, ', ');
        } else {
            return '';
        }
    }

    public function onExcelRenderSubject(Event $event, Entity $entity, array $attr)
    {
        $studentId = $entity->student_id;
        $classId = $entity->institution_class_id;
        $institutionId = $entity->institution_id ?? null;
        $academicPeriodId = $entity->academic_period_id ?? null;

        if (!array_key_exists($studentId, $this->assessmentItemResults)) {
            $this->assessmentItemResults[$studentId] = [];
        }

        if (!array_key_exists($subjectId, $this->assessmentItemResults[$studentId])) {
            $AssessmentItemResultsTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentItemResults');

            $studentResults = $AssessmentItemResultsTable->getAssessmentItemResults($academicPeriodId, $assessmentId, $subjectId, $studentId, $classId);
            if (isset($studentResults[$studentId][$subjectId])) {
                $this->assessmentItemResults[$studentId][$subjectId] = $studentResults[$studentId][$subjectId];
            }
        }
        /*$allSubjectsPermission = $this->allSubjectsPermission;
        $mySubjectsPermission = $this->mySubjectsPermission;
        $staffId = $this->staffId;
        $printedResult = '';
        $renderResult = true;
        if (!$allSubjectsPermission && !$mySubjectsPermission) {
            $printedResult = __('No Access');
            $renderResult = false;
        } elseif (!$allSubjectsPermission && $mySubjectsPermission) {
            $classId = $this->institution_class_id;

            if ($this->lastQueriedClass != $classId) {
                $AssessmentItemsTable = TableRegistry::getTableLocator()->get('Assessment.AssessmentItems');
                $allowedSubjects = $AssessmentItemsTable
                ->find('list', [
                    'keyField' => 'assessment_item_id',
                    'valueField' => 'subject_id'
                ])
                ->find('staffSubjects', ['class_id' => $classId, 'staff_id' => $staffId])
                ->select(['assessment_item_id' => $AssessmentItemsTable->aliasField('id'), 'subject_id' => $AssessmentItemsTable->aliasField('education_subject_id')])
                ->where([$AssessmentItemsTable->aliasField('assessment_id') => $assessmentId])
                ->enableHydration(false)
                ->toArray();
                $this->allowedSubjects = $allowedSubjects;
                $this->lastQueriedClass = $classId;
            }
       // } */
        $renderResult = true;
        if ($renderResult) {
            if (isset($this->assessmentItemResults[$studentId][$subjectId][$assessmentPeriodId])) {
                $result = $this->assessmentItemResults[$studentId][$subjectId][$assessmentPeriodId];
                switch ($resultType) {
                    case 'MARKS':
                        // Add logic to add weighted mark to subjectWeightedMark
                        if ($result['mark'] != 'EXEMPT' && $result['mark'] != 'UNASSIGN') {

                            $this->assessmentPeriodWeightedMark += ((float)$result['marks'] * (float)$attr['assessmentPeriodWeight']);
                            $this->assessmentPeriodWeights[] = $attr['assessmentPeriodWeight'];
                        }
                        $printedResult = $result['mark'];
                        break;
                    case 'GRADES':
                        $printedResult = $result['grade_code'] . ' - ' . $result['grade_name'];
                        break;
                    case 'DURATION':
                        $printedResult = '';
                        if (!is_null($result['marks'])) {
                            $duration = number_format($result['marks'], 2, ':', '');
                            $printedResult = ' '.$duration;
                        }
                        break;
                }
            }
        }

        return $printedResult;
    }

    public function onExcelRenderAssessmentPeriodWeightedMark(Event $event, Entity $entity, array $attr)
    {
        $weightsum = array_sum($this->assessmentPeriodWeights);
        $assessmentPeriodWeightedMark = $this->assessmentPeriodWeightedMark;
        if ($weightsum > 0) {
            $assessmentPeriodWeightedMark = $assessmentPeriodWeightedMark / $weightsum;
        }
        $this->assessmentPeriodWeights = [];
        if (is_numeric($assessmentPeriodWeightedMark)) {
            $this->totalMark += $assessmentPeriodWeightedMark;
            $this->totalWeightedMark += ($assessmentPeriodWeightedMark * $attr['subjectWeight']);
        }
        // reset the assessmentPeriodWeightedMark mark
        $this->assessmentPeriodWeightedMark = 0;
        if(is_numeric($assessmentPeriodWeightedMark)){
            $assessmentPeriodWeightedMark = number_format($assessmentPeriodWeightedMark, 2);
        }
        return ' '.$assessmentPeriodWeightedMark;
    }

    public function onExcelRenderTotalWeightedMark(Event $event, Entity $entity, array $attr)
    {
        $totalWeightedMark = $this->totalWeightedMark;
        $this->totalWeightedMark = 0;
        if(is_numeric($totalWeightedMark)){
            $totalWeightedMark = number_format($totalWeightedMark, 2);
        }
        return ' '.$totalWeightedMark;
    }

    public function onExcelRenderTotalMark(Event $event, Entity $entity, array $attr)
    {
        $totalMark = $this->totalMark;
        $this->totalMark = 0;
        if(is_numeric($totalMark)){
            $totalMark = number_format($totalMark, 2);
        }
        return ' '.$totalMark;
    }

}
