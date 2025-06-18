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

class AssessmentsTable extends AppTable
{

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
    public function onExcelBeforeStartbkp(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $requestData = json_decode($settings['process']['params']);
        $areaId = $requestData->area_education_id;
        $institutionId = $requestData->institution_id;
        $userId = $requestData->user_id;
        $superAdmin = $requestData->super_admin;
        $institutionIds = [];
        $conditions = [];
        if ($areaId > 0) {
            $conditions[$this->aliasField('area_id')] = $areaId;
        }
        if ($gradeId > 0) {
            $conditions[$this->aliasField('education_grade_id')] = $gradeId;
        }
        if ($institutionId > 0) {
            $conditions[$this->aliasField('institution_id')] = $institutionId;
        } else {//Added condition to get only user's accessiable institution data
            if (!$superAdmin) {
                $institutionObj = $this->Institutions->find('byAccess', ['userId' => $userId])->toArray();
                if (!empty($institutionObj)) {
                    foreach ($institutionObj as $value) {
                        $institutionIds[] = $value->id;
                    }
                }
                $conditions[$this->aliasField('institution_id IN')] = $institutionIds;
            }
        }
    
        $InstitutionClassesTable =TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        /*$name = $InstitutionClassesTable
            ->find()
            ->where([$InstitutionClassesTable->aliasField('id') => $classId])
            ->first();

        $sheets[] = [
            'name' => isset($name['name']) ? $name['name'] : __('Class Not Found'),
            'table' => $this,
            'query' => $this->find(),
            'assessmentId' => $assessmentId,
            'classId' => $classId,
            'staffId' => $userId,
            'institutionId' => $institutionId,
            'allSubjectsPermission' => $allSubjectsPermission,
            'mySubjectsPermission' => $mySubjectsPermission,
            'allClassesPermission' => $allClassesPermission,
            'myClassesPermission' => $myClassesPermission,
            'orientation' => 'landscape'
        ];
        $this->allSubjectsPermission = $allSubjectsPermission;
        $this->mySubjectsPermission = $mySubjectsPermission;
        $this->staffId = $userId;*/
        $SubjectStudents = $this->SubjectStudents;
        $options = [
            'institution_id' => $this->institution_id,
            'academic_period_id' => $this->academic_period_id,
           // 'institution_class_id' => $this->institution_class_id,
            //'assessment_id' => $this->assessment_id,
            'education_grade_id' => $this->education_grade_id,
        ];
        $results = $SubjectStudents->find('StudentResults', $options)
            ->toArray();
        $student_results = [];
        $this->i = 1;
        foreach ($results as $result){
            $arresult = $result->toArray();
            $arresult['i'] = $this->i;
            $this->i = $this->i + 1;
            $student_id = $result['student_id'];
            $education_subject_id = $result['education_subject_id'];
            $assessment_period_id = $result['assessment_period_id'];
            if(!isset($student_results[$student_id])){
                $student_results[$student_id] = [];
            }
            if(!isset($student_results[$student_id][$education_subject_id])){
                $student_results[$student_id][$education_subject_id] = [];
            }
            if(!isset($student_results[$student_id][$education_subject_id][$assessment_period_id])){
                $student_results[$student_id][$education_subject_id][$assessment_period_id] = $arresult;
            } else {
//                Log::debug('arresult');
//                Log::debug($arresult);
            }
        }
        $this->results = $student_results;
        $this->assessmentItemResults = $student_results;

        $this->i = 1;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query)
{
    $requestData = json_decode($settings['process']['params']);
    $institutionId = $requestData->institution_id ?? null;
    $areaId = $requestData->area_education_id ?? null;
    $gradeId = $requestData->education_grade_id ?? null;
    $superAdmin = $requestData->super_admin ?? false;
    $userId = $requestData->user_id ?? null;
    $academicTerm = $requestData->academic_term ?? null;

    $StudentStatuses = $this->StudentStatuses;
    $institutionIds = [];
    $conditions = [];

    // Area filter
    if (!empty($areaId) && $areaId > 0) {
        $conditions[$this->aliasField('area_id')] = $areaId;
    }

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
            'institution_id' => 'Institutions.name',
            'openemis_number' => 'Users.openemis_no',
            'birth_place_area' => 'BirthplaceAreas.name',
            'dob' => 'Users.date_of_birth',
            'class_name' => 'InstitutionClasses.name'
        ])
        ->where($conditions)
        ->order(['class_name']);
}


  

public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $originalField)
{
    $requestData = json_decode($settings['process']['params']);
    $institutionId = $requestData->institution_id ?? null;
    $academicPeriodId = $requestData->academic_period_id;
  

    // Dynamically fetch assessment_id
    $AssessmentsTable = TableRegistry::getTableLocator()->get('Assessment.Assessments');
    $assessment = $AssessmentsTable->find()
        ->where([
            'academic_period_id' => $academicPeriodId,
         ///   'status' => 1 // Optional: only active assessments
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

    // Fixed fields
    $fields[] = ['key' => 'Users.openemis_no', 'field' => 'openemis_number', 'type' => 'string', 'label' => ''];
    $fields[] = ['key' => 'Institutions.code', 'field' => 'code', 'type' => 'string', 'label' => ''];
    $fields[] = ['key' => 'Institutions.name', 'field' => 'institution_id', 'type' => 'string', 'label' => ''];
    $fields[] = ['key' => 'InstitutionClassStudents.student_id', 'field' => 'student_id', 'type' => 'string', 'label' => ''];
    $fields[] = ['key' => 'InstitutionClasses.class_name', 'field' => 'class_name', 'type' => 'string', 'label' => __('Class')];
    $fields[] = ['key' => 'UserNationalities.nationality_id', 'field' => 'nationality', 'type' => 'nationality', 'label' => ''];
    $fields[] = ['key' => 'Users.birthplace_area_id', 'field' => 'birth_place_area', 'type' => 'string', 'label' => ''];
    $fields[] = ['key' => 'Users.date_of_birth', 'field' => 'dob', 'type' => 'date', 'label' => ''];

    // Dynamic assessment fields
    $assessmentPeriods = $AssessmentPeriodsTable
        ->find()
        ->where(['assessment_id' => $assessmentId])
        ->order(['academic_term', 'start_date'])
        ->toArray();

    $assessmentGradeTypes = $AssessmentItemsGradingTypesTable->getAssessmentGradeTypes($assessmentId);
    $assessmentSubjects = $AssessmentItemsTable->getSubjects($assessmentId);

    foreach ($assessmentSubjects as $subject) {
        foreach ($assessmentPeriods as $period) {
            $subjectId = $subject['subject_id'];
            $assessmentPeriodId = $period->id;
            $resultType = $assessmentGradeTypes[$subjectId][$assessmentPeriodId] ?? null;

            $label = __($subject['education_subject_name']) . ' - ' . $period->name;
            if ($resultType === 'MARKS') {
                $label .= ' (' . $period->weight . ')';
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
            'label' => __('Weighted Marks') . ' (' . $subject['subject_weight'] . ')',
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



    

}
