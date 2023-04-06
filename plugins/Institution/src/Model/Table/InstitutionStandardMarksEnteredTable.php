<?php

namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Network\Session;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Datasource\ConnectionManager;

/**
 * Marks Entered by Staff
 * POCOR-6630
 */
class InstitutionStandardMarksEnteredTable extends AppTable
{

    public function initialize(array $config)
    {
         $this->table('assessment_item_results');
        parent::initialize($config);

        $this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('AssessmentGradingOptions', ['className' => 'Assessment.AssessmentGradingOptions']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses','foreignKey' => 'institution_classes_id']);
        $this->belongsTo('CreatedUser', ['className' => 'User.Users', 'foreignKey'=>'created_user_id']);
        $this->addBehavior('Report.ReportList');
        // Behaviours
        $this->addBehavior('Excel', [
            'excludes' => [],
            'pages' => false,
            'autoFields' => false
        ]);
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);

        $controllerName = $this->controller->name;
        $institutions_crumb = __('Institutions');
        $parent_crumb       = __('Statistics');
        $reportName         = __('Standard');
        
        //# START: Crumb
        $this->Navigation->removeCrumb($this->getHeader($this->alias));
        $this->Navigation->addCrumb($institutions_crumb . ' ' . $parent_crumb);
        //# END: Crumb
        $this->controller->set('contentHeader', __($institutions_crumb) . ' ' . $parent_crumb . ' - ' . $reportName);
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
    }

    public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request)
    {
        $session = $this->request->session();
        $institution_id = $session->read('Institution.Institutions.id');
        $request->data[$this->alias()]['current_institution_id'] = $institution_id;
        $request->data[$this->alias()]['institution_id'] = $institution_id;
        if ($action == 'add') {
            $attr['value'] = 'xlsx';
            $attr['attr']['value'] = 'Excel';
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $options = $options = $this->controller->getInstitutionStatisticStandardReportFeature();
        $attr['options'] = $options;
        $attr['onChangeReload'] = true;
        if (!(isset($this->request->data[$this->alias()]['feature']))) {
            $option = $attr['options'];
            reset($option);
            $this->request->data[$this->alias()]['feature'] = key($option);
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature                = $this->request->data[$this->alias()]['feature'];
            $AcademicPeriodTable    = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodOptions  = $AcademicPeriodTable->getYearList();
            $currentPeriod          = $AcademicPeriodTable->getCurrent();
            $attr['options']        = $academicPeriodOptions;
            $attr['type']           = 'select';
            $attr['select']         = false;
            $attr['onChangeReload'] = true;
            if (empty($request->data[$this->alias()]['academic_period_id'])) {
                $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
            }
            return $attr;
        }
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
        $requestData           = json_decode($settings['process']['params']);
        //echo "<pre>";print_r($requestData);die;
        $academicPeriodId      = $requestData->academic_period_id;
        $institutionId         = $requestData->institution_id;
        $assessmentId         = $requestData->assessment_id;
        $assessmentPeriodId   = $requestData->assessment_period_id;
        // $Users = TableRegistry::get('User.Users');
        // $this->AssessmentItemResults = TableRegistry::get('assessment_item_results');
        // $where = [];
        // $join = [];
        // if (empty($assessmentId) && ($assessmentId == 0)) { 
        //        $assessmentIdd = NULL;
        //        $wheres = "'assessment_item_results.academic_period_id = '.$academicPeriodId
        //        'AND assessment_item_results.institution_id = '.$institutionId
        //        'AND assessment_item_results.assessment_period_id = '.$assessmentPeriodId";
        // }else{
        //     $wheres = "assessment_item_results.assessment_id = ".$assessmentId;
        //     $assessmentIdd = "AND assessments.id = ".$assessmentId;
        //     $where[$this->aliasField('assessment_id')] = $assessmentId;
        //     $wheres = "assessment_item_results.academic_period_id = $academicPeriodId
        //        AND assessment_item_results.institution_id = $institutionId
        //        AND assessment_item_results.assessment_id = $assessmentId
        //        AND assessment_item_results.assessment_period_id = $assessmentPeriodId";
        // }

        // $where[$this->aliasField('assessment_period_id')] = $assessmentPeriodId;
        // $where[$this->aliasField('academic_period_id')] = $academicPeriodId;
        // $where[$this->aliasField('institution_id')] = $institutionId;
        //     $query = $this->AssessmentItemResults->find();
        //     $query->select([
        //         'academic_periods_name' => 'academic_periods.name',
        //         'education_grades_name' => 'education_grades.name',
        //         'institution_class_name' => 'institution_classes.name',
        //         'education_subjects_name' => 'education_subjects.name',
        //         'assessment_period_name' => 'assessment_periods.name',
        //         'openemis_no'=> "(SELECT IFNULL(staff_info.openemis_no, ''))",
        //         'identity_number'=> "(SELECT IFNULL(staff_info.identity_number, ''))",
        //         'staff_name'=> "(SELECT IFNULL(staff_info.staff_name, ''))",
        //         'identity_number'=> "(SELECT IFNULL(staff_info.identity_number, ''))",
        //         'academic_term'=> "(SELECT IFNULL(assessment_periods.academic_term, ''))",
        //        'marks_entry_percentage'=> "(SELECT IFNULL(CONCAT(ROUND(COUNT(DISTINCT(assessment_item_results.student_id)) / MAX(student_counts.total_students) * 100, 2), '%'), ''))",
        //        'marks_entered'=> "(SELECT COUNT(DISTINCT(assessment_item_results.student_id)))",
        //        'marks_not_entered'=> "(SELECT 'ABS(MAX(student_counts.total_students) - COUNT(DISTINCT(assessment_item_results.student_id))))",
        //     ]);
        //     $join['assessment_item_results'] = [
        //         'type' => 'inner',
        //         'table' => "(SELECT assessment_item_results.student_id
        //                     ,assessment_item_results.assessment_id
        //                     ,assessment_item_results.education_subject_id
        //                     ,assessment_item_results.assessment_period_id
        //                     ,MAX(assessment_item_results.created) latest_created
        //                 FROM assessment_item_results
        //                 WHERE $wheres
        //                 GROUP BY assessment_item_results.academic_period_id
        //                     ,assessment_item_results.institution_id
        //                     ,assessment_item_results.student_id
        //                     ,assessment_item_results.assessment_id
        //                     ,assessment_item_results.education_subject_id
        //                     ,assessment_item_results.assessment_period_id
        //                 ) latest_grades) latest_grades",
        //                 'conditions' => [
        //                         'latest_grades.student_id = assessment_item_results.student_id',
        //                         'latest_grades.assessment_id = assessment_item_results.assessment_id',
        //                         'latest_grades.education_subject_id = assessment_item_results.education_subject_id',
        //                         'latest_grades.assessment_period_id = assessment_item_results.assessment_period_id',
        //                         'latest_grades.latest_created = assessment_item_results.created'
        //                     ],
        //             ];
           
        //    $query->join($join);

        //     $query->leftJoin(
        //         ['staff_info' => function($q) {
        //             return $q->select([
        //                 'institution_subjects.academic_period_id',
        //                 'institution_subjects.institution_id',
        //                 'institution_subjects.education_grade_id',
        //                 'institution_subjects.education_subject_id',
        //                 'IFNULL(GROUP_CONCAT(DISTINCT(staff_identities.identity_number)), \'\') AS identity_number',
        //                 'GROUP_CONCAT(DISTINCT(security_users.openemis_no)) AS openemis_no',
        //                 'GROUP_CONCAT(DISTINCT(CONCAT_WS(\' \',security_users.first_name,security_users.middle_name,security_users.third_name,security_users.last_name))) AS staff_name',
        //             ])
        //             ->from('institution_subject_staff')
        //             ->innerJoin(
        //                 ['institution_class_subjects' => 'institution_class_subjects'],
        //                 ['institution_class_subjects.institution_subject_id = institution_subject_staff.institution_subject_id']
        //             )
        //             ->innerJoin(
        //                 ['institution_subjects' => 'institution_subjects'],
        //                 ['institution_subjects.id = institution_subject_staff.institution_subject_id', 
        //                 'institution_subjects.institution_id = institution_subject_staff.institution_id']
        //             )
        //             ->innerJoin(
        //                 ['assessments' => 'assessments'],
        //                 ['assessments.academic_period_id = institution_subjects.academic_period_id', 
        //                 'assessments.education_grade_id = institution_subjects.education_grade_id']
        //             )
        //             ->innerJoin(
        //                 ['security_users' => 'security_users'],
        //                 ['security_users.id = institution_subject_staff.staff_id']
        //             )
        //             ->leftJoin(
        //                 ['staff_identities' => function($q) {
        //                     return $q->select([
        //                         'user_identities.security_user_id',
        //                         'GROUP_CONCAT(identity_types.name) AS identity_type',
        //                         'GROUP_CONCAT(user_identities.number) AS identity_number'
        //                     ])
        //                 ->from('user_identities')
        //                 ->innerJoin(
        //                     ['identity_types' => 'identity_types'],
        //                     ['identity_types.id = user_identities.identity_type_id'])
        //                 ->where(['identity_types.default' => 1])
        //                 ->group(['user_identities.security_user_id'])
        //                 ->andWhere(['staff_identities.security_user_id' => 'institution_subject_staff.staff_id'])
        //                 ->andWhere(['institution_subjects.academic_period_id'=>32,
        //                         'institution_subjects.institution_id' => 6,
        //                          'assessments.id' => 32
        //                     ])
        //                 ->group(['institution_subjects.academic_period_id'
        //                     ,'institution_subjects.institution_id'
        //                     ,'assessments.id'
        //                     ,'institution_class_subjects.institution_class_id'
        //                     ,'institution_subjects.education_subject_id']);
        //             }
        //         ])
        //         ->andwhere(['staff_info.academic_period_id' => 'assessment_item_results.academic_period_id'])
        //         ->andwhere(['staff_info.institution_id' => 'assessment_item_results.institution_id',
        //                     'staff_info.education_grade_id' => 'assessment_item_results.education_grade_id',
        //                     'staff_info.education_subject_id' => 'assessment_item_results.education_subject_id'
        //                     ]);
        //         ///work here    for condition
        //          }
        //     ]);
        //     $join['institution_subject_students'] = [
        //         'type' => 'inner',
        //         'table' => "(SELECT institution_subject_students.academic_period_id
        //                     ,institution_subject_students.institution_id
        //                     ,institution_subject_students.education_grade_id
        //                     ,institution_subject_students.institution_class_id
        //                     ,institution_subject_students.education_subject_id
        //                     ,COUNT(DISTINCT(institution_subject_students.student_id)) total_students
        //                 FROM institution_subject_students
        //                 INNER JOIN assessments
        //                 ON assessments.academic_period_id = institution_subject_students.academic_period_id
        //                 AND assessments.education_grade_id = institution_subject_students.education_grade_id
        //                 INNER JOIN academic_periods
        //                 ON academic_periods.id = institution_subject_students.academic_period_id
        //                 WHERE institution_subject_students.academic_period_id = $academicPeriodId
        //                 AND institution_subject_students.institution_id = $institutionId
        //                 AND assessments.id = $assessmentId
        //                 AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_subject_students.student_status_id = 1, institution_subject_students.student_status_id IN (1, 7, 6, 8))
        //                 GROUP BY institution_subject_students.academic_period_id
        //                     ,institution_subject_students.institution_id
        //                     ,assessments.id
        //                     ,institution_subject_students.institution_class_id
        //                     ,institution_subject_students.education_subject_id) student_counts",
        //                     'conditions' => [
        //                         'student_counts.academic_period_id = assessment_item_results.academic_period_id',
        //                         'student_counts.institution_id = assessment_item_results.institution_id',
        //                         'student_counts.education_grade_id = assessment_item_results.education_grade_id',
        //                         'student_counts.institution_class_id  = assessment_item_results.institution_class_id',
        //                         'student_counts.education_subject_id  = assessment_item_results.education_subject_id'
        //                     ],
        //              ];
        //              $query->join($join)
        //              ->innerJoin(
        //                 ['assessment_periods' => 'assessment_periods'],
        //                 ['assessment_periods.id = assessment_item_results.assessment_period_id',
        //                 'assessment_periods.assessment_id = assessment_item_results.assessment_id']
        //             )
        //             ->innerJoin(
        //                 ['institution_classes' => 'institution_classes'],
        //                 ['institution_classes.id = assessment_item_results.institution_classes_id']
        //             )
        //             ->innerJoin(
        //                 ['academic_periods' => 'academic_periods'],
        //                 ['academic_periods.id = assessment_item_results.academic_period_id']
        //             )
        //             ->innerJoin(
        //                 ['education_grades' => 'education_grades'],
        //                 ['education_grades.id = assessment_item_results.education_grade_id']
        //             )
        //             ->innerJoin(
        //                 ['education_subjects' => 'education_subjects'],
        //                 ['education_subjects.id = assessment_item_results.education_subject_id']
        //             )
        //             ->where($where)
        //             ->group(['assessment_item_results.academic_period_id'
        //                     ,'assessment_item_results.institution_id'
        //                     ,'assessment_item_results.assessment_id'
        //                     ,'assessment_item_results.institution_classes_id'
        //                     ,'education_subjects.id' 
        //                     ,'assessment_periods.id'
        //                     ,'assessment_periods.academic_term']);       
        
        


        //=======Rishabh================================================

        $join=[];
        $join['latest_grades'] = [
            'type' => 'inner',
            'table' => "(SELECT assessment_item_results.student_id
            ,assessment_item_results.assessment_id
            ,assessment_item_results.education_subject_id
            ,assessment_item_results.assessment_period_id
            ,MAX(assessment_item_results.created) latest_created
        FROM assessment_item_results
        WHERE assessment_item_results.academic_period_id = 31
        AND assessment_item_results.institution_id = 6
        AND assessment_item_results.assessment_id = 31
        AND assessment_item_results.assessment_period_id = 30
        GROUP BY assessment_item_results.academic_period_id
            ,assessment_item_results.institution_id
            ,assessment_item_results.student_id
            ,assessment_item_results.assessment_id
            ,assessment_item_results.education_subject_id
            ,assessment_item_results.assessment_period_id)",
            'conditions'=>[
                'latest_grades.student_id = assessment_item_results.student_id',
                'latest_grades.assessment_id = assessment_item_results.assessment_id',
                'latest_grades.education_subject_id = assessment_item_results.education_subject_id',
                'latest_grades.assessment_period_id = assessment_item_results.assessment_period_id',
                'latest_grades.latest_created = assessment_item_results.created'
            ]
            ]; 

            $join['staff_info'] = [
                'type' => 'left',
                'table' => "(SELECT institution_subjects.academic_period_id
                ,institution_subjects.institution_id
                ,institution_subjects.education_grade_id
                ,institution_subjects.education_subject_id
                ,IFNULL(GROUP_CONCAT(DISTINCT(staff_identities.identity_number)), '') identity_number
                ,GROUP_CONCAT(DISTINCT(security_users.openemis_no)) openemis_no
                ,GROUP_CONCAT(DISTINCT(CONCAT_WS(' ',security_users.first_name,security_users.middle_name,security_users.third_name,security_users.last_name))) staff_name
            FROM institution_subject_staff
            INNER JOIN institution_class_subjects
            ON institution_class_subjects.institution_subject_id = institution_subject_staff.institution_subject_id
            INNER JOIN institution_subjects
            ON institution_subjects.id = institution_subject_staff.institution_subject_id
            AND institution_subjects.institution_id = institution_subject_staff.institution_id
            INNER JOIN assessments
            ON assessments.academic_period_id = institution_subjects.academic_period_id
            AND assessments.education_grade_id = institution_subjects.education_grade_id
            INNER JOIN security_users
            ON security_users.id = institution_subject_staff.staff_id
            LEFT JOIN
            (
                SELECT  user_identities.security_user_id
                    ,GROUP_CONCAT(identity_types.name) identity_type
                    ,GROUP_CONCAT(user_identities.number) identity_number
                FROM user_identities
                INNER JOIN identity_types
                ON identity_types.id = user_identities.identity_type_id
                WHERE identity_types.default = 1
                GROUP BY  user_identities.security_user_id
            ) AS staff_identities
            ON staff_identities.security_user_id = institution_subject_staff.staff_id
            WHERE institution_subjects.academic_period_id = 31
            AND institution_subjects.institution_id = 6
            AND assessments.id = 31
            GROUP BY institution_subjects.academic_period_id
                ,institution_subjects.institution_id
                ,assessments.id
                ,institution_class_subjects.institution_class_id
                ,institution_subjects.education_subject_id)",
                'conditions'=>[
                    'staff_info.academic_period_id = assessment_item_results.academic_period_id',
                    'staff_info.institution_id = assessment_item_results.institution_id',
                    'staff_info.education_grade_id = assessment_item_results.education_grade_id',
                    'staff_info.education_subject_id = assessment_item_results.education_subject_id'
                ]
                ]; 


                $join['student_counts'] = [
                    'type' => 'inner',
                    'table' => "(SELECT institution_subject_students.academic_period_id
                    ,institution_subject_students.institution_id
                    ,institution_subject_students.education_grade_id
                    ,institution_subject_students.institution_class_id
                    ,institution_subject_students.education_subject_id
                    ,COUNT(DISTINCT(institution_subject_students.student_id)) total_students
                FROM institution_subject_students
                INNER JOIN assessments
                ON assessments.academic_period_id = institution_subject_students.academic_period_id
                AND assessments.education_grade_id = institution_subject_students.education_grade_id
                INNER JOIN academic_periods
                ON academic_periods.id = institution_subject_students.academic_period_id
                WHERE institution_subject_students.academic_period_id = 31
                AND institution_subject_students.institution_id = 6
                AND assessments.id = 31
                AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_subject_students.student_status_id = 1, institution_subject_students.student_status_id IN (1, 7, 6, 8))
                GROUP BY institution_subject_students.academic_period_id
                    ,institution_subject_students.institution_id
                    ,assessments.id
                    ,institution_subject_students.institution_class_id
                    ,institution_subject_students.education_subject_id)",
                    'conditions'=>[
                        'student_counts.academic_period_id = assessment_item_results.academic_period_id',
                        'student_counts.institution_id = assessment_item_results.institution_id',
                        'student_counts.education_grade_id = assessment_item_results.education_grade_id',
                        'student_counts.institution_class_id = assessment_item_results.institution_classes_id',
                        'student_counts.education_subject_id = assessment_item_results.education_subject_id'
                    ]
                    ];

            $query->select([
                'academic_periods_name'=>'academic_periods.name',                                                                                                     
                'education_grades_name' => 'education_grades.name',  
                'institution_class_name' =>'institution_classes.name',  
                'education_subjects_name'=>'education_subjects.name',  
                'openemis_no' =>"(IFNULL(staff_info.openemis_no, ''))",
                'identity_number' => "(IFNULL(staff_info.identity_number, ''))",
                'staff_name' => "(IFNULL(staff_info.staff_name, ''))",
                'assessment_period_name'=>'assessment_periods.name',
                'academic_term'=> "(IFNULL(assessment_periods.academic_term, ''))",
                'marks_entry_percentage' => "(IFNULL(CONCAT(ROUND(COUNT(DISTINCT(assessment_item_results.student_id)) / MAX(student_counts.total_students) * 100, 2), '%'), ''))",
                'marks_entered'=> "(COUNT(DISTINCT(assessment_item_results.student_id)))",
                'marks_not_entered'=>"(ABS(MAX(student_counts.total_students) - COUNT(DISTINCT(assessment_item_results.student_id))))"
                
            ])
            ->from(['assessment_item_results' => 'assessment_item_results'])
            ->where([
                'assessment_item_results.academic_period_id'=>31,
                'assessment_item_results.institution_id' => 6,
                'assessment_item_results.assessment_id' =>31,
                'assessment_item_results.assessment_period_id' =>30
            ])
            ->group([
                'assessment_item_results.academic_period_id',
                'assessment_item_results.institution_id',
                'assessment_item_results.assessment_id',
                'assessment_item_results.institution_classes_id',
                'education_subjects.id',
                'assessment_periods.id',
                'assessment_periods.academic_term'
            ]);
            $query->join($join);
            $query
            ->innerJoin(
                ['assessment_periods' => 'assessment_periods'],
                ['assessment_periods.id = assessment_item_results.assessment_period_id',
                'assessment_periods.assessment_id = assessment_item_results.assessment_id']
            )
            ->innerJoin(
                ['institution_classes' => 'institution_classes'],
                ['institution_classes.id = assessment_item_results.institution_classes_id']
            )
            ->innerJoin(
                ['academic_periods' => 'academic_periods'],
                ['academic_periods.id = assessment_item_results.academic_period_id']
            )
            ->innerJoin(
                ['education_grades' => 'education_grades'],
                ['education_grades.id = assessment_item_results.education_grade_id']
            )
            ->innerJoin(
                ['education_subjects' => 'education_subjects'],
                ['education_subjects.id = assessment_item_results.education_subject_id']
            );
            




        //==========END=================================================
                        
print_r($query->toArray());die;
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use($wheres, $institutionId, $academicPeriodId,$assessmentIdd,$assessmentPeriodId)
            {
                return $results->map(function ($row) use($wheres, $institutionId, $academicPeriodId,$assessmentIdd,$assessmentPeriodId)
                {
                    $row['referrer_teacher_name'] = $row['fname'] .' '.$row['mname'].' '.$row['tname'].' '. $row['lname'];
                    $row['where_condition'] = $wheres;
                    $row['institutionId'] = $institutionId;
                    $row['academicPeriodId'] = $academicPeriodId;
                    $row['assessmentIdd'] = $assessmentIdd;
                    $row['assessmentPeriodId'] = $assessmentPeriodId;
                    return $row;
                });
            });
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
        $newFields[] = [
            'key'   => 'academic_periods_name',
            'field' => 'academic_periods_name',
            'type'  => 'integer',
            'label' => __('Academic Period'),
        ];
        $newFields[] = [
            'key'   => 'education_grades_name',
            'field' => 'education_grades_name',
            'type'  => 'string',
            'label' => __('Education Grade'),
        ];
        $newFields[] = [
            'key'   => 'class',
            'field' => 'class',
            'type'  => 'string',
            'label' => __('Class'),
        ];
        $newFields[] = [
            'key'   => 'subject',
            'field' => 'subject',
            'type'  => 'string',
            'label' => __('Subject'),
        ];
        $newFields[] = [
            'key'   => 'openemis_no',
            'field' => 'openemis_no',
            'type'  => 'integer',
            'label' => __('OpenEMIS ID'),
        ];
        $newFields[] = [
            'key' => 'identity_number',
            'field' =>'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        $newFields[] = [
            'key'   => 'staff_name',
            'field' => 'staff_name',
            'type'  => 'string',
            'label' => __('Teacher Name'),
        ];
        
        $newFields[] = [
            'key'   => 'assessment_periods_name',
            'field' => 'assessment_periods_name',
            'type'  => 'integer',
            'label' => __('Assessment Period'),
        ];
        $newFields[] = [
            'key'   => 'academic_term',
            'field' => 'academic_term',
            'type'  => 'string',
            'label' => __('Assessment Term'),
        ];
        
        $newFields[] = [
            'key'   => 'entry_percentage',
            'field' => 'entry_percentage',
            'type'  => 'integer',
            'label' => __('School marks entry percentage'),
        ];
        $newFields[] = [
            'key'   => 'marks_entered',
            'field' => 'marks_entered',
            'type'  => 'integer',
            'label' => __('The total number of marks entered'),
        ];
        $newFields[] = [
            'key'   => 'marks_not_entered',
            'field' => 'marks_not_entered',
            'type'  => 'integer',
            'label' => __('The total number of marks not entered'),
        ];

        $fields->exchangeArray($newFields);
    }

    /**
     * Get student identity type
     */
    public function onExcelGetUserIdentitiesDefault(Event $event, Entity $entity)
    {
        $return = [];
        if ($entity->has('user')) {
            if ($entity->user->has('identities')) {
                if (!empty($entity->user->identities)) {
                    $identities = $entity->user->identities;
                    foreach ($identities as $key => $value) {
                        if ($value->identity_type->default == 1) {
                            $return[] = $value->number;
                        }
                    }
                }
            }
        }
        else{ //POCOR-7098
           foreach ($entity->created_user->identities as $key => $identitiesValue) {
                        if ($identitiesValue->identity_type->default == 1) {
                            $return[] = $identitiesValue->number;
                        }
                    } 
        }
        return implode(', ', array_values($return));
    }

    /**
     * get total marks entered
    */
    /*public function onExcelGetEntryPercentage(Event $event, Entity $entity)
    {

        $wheres = $entity->where_condition;
      //  print_r($wheres);die;
        $institutionId = $entity->institutionId;
        $academicPeriodId = $entity->academicPeriodId;
        $assessmentIdd = $entity->assessmentIdd;
        $assessmentPeriodId = $entity->assessmentPeriodId;
                $connection = ConnectionManager::get('default');
                $statement = $connection->prepare("SELECT academic_periods.name academic_periods_name 
                                        ,education_grades.name education_grades_name 
                                        ,institution_classes.name institution_class_name 
                                        ,education_subjects.name education_subjects_name 
                                        ,IFNULL(staff_info.openemis_no, '') openemis_no
                                        ,IFNULL(staff_info.identity_number, '') identity_number
                                        ,IFNULL(staff_info.staff_name, '') staff_name   
                                        ,assessment_periods.name assessment_period_name
                                        ,IFNULL(assessment_periods.academic_term, '') academic_term
                                        ,IFNULL(CONCAT(ROUND(COUNT(DISTINCT(assessment_item_results.student_id)) / MAX(student_counts.total_students) * 100, 2), '%'), '') marks_entry_percentage  
                                        ,COUNT(DISTINCT(assessment_item_results.student_id)) marks_entered  
                                        ,ABS(MAX(student_counts.total_students) - COUNT(DISTINCT(assessment_item_results.student_id))) marks_not_entered                                                                 
                                    FROM assessment_item_results
     
                                    INNER JOIN 
                                    (
                                        SELECT assessment_item_results.student_id
                                            ,assessment_item_results.assessment_id
                                            ,assessment_item_results.education_subject_id
                                            ,assessment_item_results.assessment_period_id
                                            ,MAX(assessment_item_results.created) latest_created
                                        FROM assessment_item_results
                                        WHERE $wheres
                                        GROUP BY assessment_item_results.academic_period_id
                                            ,assessment_item_results.institution_id
                                            ,assessment_item_results.student_id
                                            ,assessment_item_results.assessment_id
                                            ,assessment_item_results.education_subject_id
                                            ,assessment_item_results.assessment_period_id
                                    ) latest_grades
                                    ON latest_grades.student_id = assessment_item_results.student_id
                                    AND latest_grades.assessment_id = assessment_item_results.assessment_id
                                    AND latest_grades.education_subject_id = assessment_item_results.education_subject_id
                                    AND latest_grades.assessment_period_id = assessment_item_results.assessment_period_id
                                    AND latest_grades.latest_created = assessment_item_results.created
                                    LEFT JOIN
                                    (
                                        SELECT institution_subjects.academic_period_id
                                            ,institution_subjects.institution_id
                                            ,institution_subjects.education_grade_id
                                            ,institution_subjects.education_subject_id
                                            ,IFNULL(GROUP_CONCAT(DISTINCT(staff_identities.identity_number)), '') identity_number
                                            ,GROUP_CONCAT(DISTINCT(security_users.openemis_no)) openemis_no
                                            ,GROUP_CONCAT(DISTINCT(CONCAT_WS(' ',security_users.first_name,security_users.middle_name,security_users.third_name,security_users.last_name))) staff_name
                                        FROM institution_subject_staff
                                        INNER JOIN institution_class_subjects
                                        ON institution_class_subjects.institution_subject_id = institution_subject_staff.institution_subject_id
                                        INNER JOIN institution_subjects
                                        ON institution_subjects.id = institution_subject_staff.institution_subject_id
                                        AND institution_subjects.institution_id = institution_subject_staff.institution_id
                                        INNER JOIN assessments
                                        ON assessments.academic_period_id = institution_subjects.academic_period_id
                                        AND assessments.education_grade_id = institution_subjects.education_grade_id
                                        INNER JOIN security_users
                                        ON security_users.id = institution_subject_staff.staff_id
                                        LEFT JOIN
                                        (
                                            SELECT  user_identities.security_user_id
                                                ,GROUP_CONCAT(identity_types.name) identity_type
                                                ,GROUP_CONCAT(user_identities.number) identity_number
                                            FROM user_identities
                                            INNER JOIN identity_types
                                            ON identity_types.id = user_identities.identity_type_id
                                            WHERE identity_types.default = 1
                                            GROUP BY  user_identities.security_user_id
                                        ) AS staff_identities
                                        ON staff_identities.security_user_id = institution_subject_staff.staff_id
                                        WHERE institution_subjects.academic_period_id = $academicPeriodId
                                        AND institution_subjects.institution_id = $institutionId
                                        $assessmentIdd
                                        GROUP BY institution_subjects.academic_period_id
                                            ,institution_subjects.institution_id
                                            ,assessments.id
                                            ,institution_class_subjects.institution_class_id
                                            ,institution_subjects.education_subject_id
                                    ) staff_info
                                    ON staff_info.academic_period_id = assessment_item_results.academic_period_id
                                    AND staff_info.institution_id = assessment_item_results.institution_id
                                    AND staff_info.education_grade_id = assessment_item_results.education_grade_id
                                    AND staff_info.education_subject_id = assessment_item_results.education_subject_id
                                    INNER JOIN
                                    (
                                        SELECT institution_subject_students.academic_period_id
                                            ,institution_subject_students.institution_id
                                            ,institution_subject_students.education_grade_id
                                            ,institution_subject_students.institution_class_id
                                            ,institution_subject_students.education_subject_id
                                            ,COUNT(DISTINCT(institution_subject_students.student_id)) total_students
                                        FROM institution_subject_students
                                        INNER JOIN assessments
                                        ON assessments.academic_period_id = institution_subject_students.academic_period_id
                                        AND assessments.education_grade_id = institution_subject_students.education_grade_id
                                        INNER JOIN academic_periods
                                        ON academic_periods.id = institution_subject_students.academic_period_id
                                        WHERE institution_subject_students.academic_period_id = $academicPeriodId
                                        AND institution_subject_students.institution_id = $institutionId
                                        $assessmentIdd
                                        AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_subject_students.student_status_id = 1, institution_subject_students.student_status_id IN (1, 7, 6, 8))
                                        GROUP BY institution_subject_students.academic_period_id
                                            ,institution_subject_students.institution_id
                                            ,assessments.id
                                            ,institution_subject_students.institution_class_id
                                            ,institution_subject_students.education_subject_id
                                    ) student_counts
                                    ON student_counts.academic_period_id = assessment_item_results.academic_period_id
                                    AND student_counts.institution_id = assessment_item_results.institution_id
                                    AND student_counts.education_grade_id = assessment_item_results.education_grade_id
                                    AND student_counts.institution_class_id = assessment_item_results.institution_classes_id
                                    AND student_counts.education_subject_id = assessment_item_results.education_subject_id
                                    INNER JOIN assessment_periods
                                    ON assessment_periods.id = assessment_item_results.assessment_period_id
                                    AND assessment_periods.assessment_id = assessment_item_results.assessment_id
                                    INNER JOIN institution_classes
                                    ON institution_classes.id = assessment_item_results.institution_classes_id
                                    INNER JOIN academic_periods
                                    ON academic_periods.id = assessment_item_results.academic_period_id
                                    INNER JOIN education_grades
                                    ON education_grades.id = assessment_item_results.education_grade_id
                                    INNER JOIN education_subjects
                                    ON education_subjects.id = assessment_item_results.education_subject_id
                                    WHERE $wheres
                                    GROUP BY assessment_item_results.academic_period_id
                                        ,assessment_item_results.institution_id
                                        ,assessment_item_results.assessment_id
                                        ,assessment_item_results.institution_classes_id
                                        ,education_subjects.id 
                                        ,assessment_periods.id
                                        ,assessment_periods.academic_term"); 
            $statement->execute();
            $list =  $statement->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($list as  $value) {
                $academic_periods_name = $value['academic_periods_name'];
                $education_grades_name = $value['education_grades_name'];
                $institution_class_name = $value['institution_class_name'];
                $education_subjects_name = $value['education_subjects_name'];
                $openemis_no = $value['openemis_no'];
                $identity_number = $value['identity_number'];
                $staff_name = $value['staff_name'];
                $assessment_period_name = $value['assessment_period_name'];
                $academic_term = $value['academic_term'];
                $marks_entry_percentage = $value['marks_entry_percentage'];
                $marks_entered = $value['marks_entered'];
                $marks_not_entered = $value['marks_not_entered'];
            
            $entity->openemis_no = $openemis_no;
            $entity->staff_name = $staff_name;
            $entity->academic_periods_name = $academic_periods_name;
            $entity->education_grades_name = $education_grades_name;
            $entity->institution_class_name = $institution_class_name;
            $entity->education_subjects_name = $education_subjects_name;
            $entity->identity_number = $identity_number;
            $entity->marks_entered = $marks_entered;
            $entity->assessment_period_name = $assessment_period_name;
            $entity->academic_term = $academic_term;
            $entity->marks_not_entered = $marks_not_entered;
            }

            return $entity->marks_entry_percentage;

    }*/
    

}
