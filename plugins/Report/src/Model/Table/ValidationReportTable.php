<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Validation\Validator;
use Cake\Datasource\ConnectionManager;
use App\Model\Traits\OptionsTrait;


//POCOR-8144
class ValidationReportTable extends AppTable  {
    use OptionsTrait;

    public function initialize(array $config) {

        $this->table('institutions');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);

        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event) {
        $controllerName = $this->controller->name;
        $reportName = __('Validation Report');
        $this->controller->Navigation->substituteCrumb($this->alias(), $reportName);
        $this->controller->set('contentHeader', __($controllerName).' - '.$reportName);
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('academic_period_id', ['select' => false]);
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }
    
    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);
        $areaLevelId = $requestData->area_level_id;
        $areaId = $requestData->area_education_id;
        $institution_id = $requestData->institution_id;
        //$institution_status_id = $requestData->institution_status_id;
        $academic_period_id = $requestData->academic_period_id;
        $AreaLvlT = TableRegistry::get('area_levels'); 
        $AreaLvlData = $AreaLvlT->find('all')->where(['id' => $areaLevelId])->first();
        $AreaT = TableRegistry::get('areas');                
        //Level-1
        if($areaId != -1){
            $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $areaId])->toArray();
        }else{
            $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['area_level_id' => $areaLevelId])->toArray();
        }
        
        $childArea =[];
        $childAreaMain = [];
        $childArea3 = [];
        $childArea4 = [];
        foreach($AreaData as $kkk =>$AreaData11 ){
            $childArea[$kkk] = $AreaData11->id;
        }
        //level-2
        foreach($childArea as $kyy =>$AreaDatal2 ){
            $AreaDatas = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal2])->toArray();
            foreach($AreaDatas as $ky =>$AreaDatal22 ){
                $childAreaMain[$kyy.$ky] = $AreaDatal22->id;
            }
        }
        //level-3
        if(!empty($childAreaMain)){
            foreach($childAreaMain as $kyy =>$AreaDatal3 ){
                $AreaDatass = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal3])->toArray();
                foreach($AreaDatass as $ky =>$AreaDatal222 ){
                    $childArea3[$kyy.$ky] = $AreaDatal222->id;
                }
            }
        }   
        //level-4
        if(!empty($childAreaMain)){
            foreach($childArea3 as $kyy =>$AreaDatal4 ){
                $AreaDatasss = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
                foreach($AreaDatasss as $ky =>$AreaDatal44 ){
                    $childArea4[$kyy.$ky] = $AreaDatal44->id;
                }
            }
        }
        $mergeArr = array_merge($childAreaMain,$childArea,$childArea3,$childArea4);
        array_push($mergeArr,$areaId);
        $mergeArr = array_unique($mergeArr);
        $finalIds = implode(',',$mergeArr);
        $finalIds = explode(',',$finalIds);

        $whereClause = [];
        $whereConditions = [];
        $where = [];

        if ($areaId != -1) {
            $whereConditions[] = 'areas.id IN (' . implode(',', $finalIds) . ')';
        }

        /*if ($areaLevelId != -1) {
            $whereConditions[] = 'areas.area_level_id = ' . $areaId;
        }*/

        if ($institution_id != -1) {
            $whereConditions[] = 'institutions.id = ' . $institution_id;
        }
        if (!empty($whereConditions) && $whereConditions != null) {
            $whereClause = 'AND ' . implode(' AND ', $whereConditions);
        }else{
            $whereClause = '';
        }
        if ($institution_id != -1) {
            $where['institutions.id'] = $institution_id;
        }
        if ($areaId != -1) {
            $where['areas.id IN'] = $finalIds;
        }
        // if ($areaLevelId != -1) {
        //     $where['areas.area_level_id'] = $areaId;
        // }

        $join = [];
        $conditions = [];

        $join['areas'] = [
            'type' => 'inner',
            'table' => 'areas',
            'conditions' => [
                'areas.id = institutions.area_id' 
            ]
        ];

        $join['area_levels'] = [
            'type' => 'inner',
            'table' => 'area_levels',
            'conditions' => [
                'area_levels.id = areas.area_level_id' 
            ]
        ];
        $join['institution_types'] = [
            'type' => 'inner',
            'table' => 'institution_types',
            'conditions' => [
                'institution_types.id = institutions.institution_type_id' 
            ]
        ];
        $join['grades'] = [
            'type' => 'left',
            'table' => "(SELECT  aggregated_grades.institution_id
                    ,lowest_stage.name  AS lowest_grade
                    ,highest_stage.name AS highest_grade
                FROM
                (
                    SELECT institution_grades.institution_id
                        ,MIN(education_stages.order) AS lowest
                        ,MAX(education_stages.order) AS highest
                    FROM institution_grades
                    INNER JOIN institutions
                    ON institutions.id = institution_grades.institution_id
                    INNER JOIN areas
                    ON areas.id = institutions.area_id
                    INNER JOIN education_grades
                    ON education_grades.id = institution_grades.education_grade_id
                    INNER JOIN education_stages
                    ON education_stages.id = education_grades.education_stage_id
                    WHERE institution_grades.academic_period_id = $academic_period_id
                     $whereClause
                    GROUP BY institution_grades.institution_id
                ) aggregated_grades
                INNER JOIN education_stages AS lowest_stage
                ON lowest_stage.order = aggregated_grades.lowest
                INNER JOIN education_stages AS highest_stage
                ON highest_stage.order = aggregated_grades.highest
            )",
            'conditions' => [
                    'grades.institution_id = institutions.id',
            ]
        ];
        $join['shifts_data'] = [
            'type' => 'left',
            'table' => "(SELECT institution_shifts.location_institution_id institution_id 
                    ,GROUP_CONCAT(DISTINCT(shift_options.name)) shift_names
                FROM institution_shifts
                INNER JOIN institutions
                ON institutions.id = institution_shifts.institution_id
                INNER JOIN areas
                ON areas.id = institutions.area_id
                INNER JOIN shift_options
                ON shift_options.id = institution_shifts.shift_option_id
                WHERE institution_shifts.academic_period_id = $academic_period_id
                 $whereClause
                GROUP BY institution_shifts.location_institution_id)",
                'conditions' => [
                        'shifts_data.institution_id = institutions.id',
                ]
        ];
        $join['classes_counter'] = [
            'type' => 'left',
            'table' => "(SELECT institution_classes.institution_id
                        ,COUNT(DISTINCT(institution_classes.id)) nb_classes
                    FROM institution_classes
                    INNER JOIN institutions
                    ON institutions.id = institution_classes.institution_id
                    INNER JOIN areas
                    ON areas.id = institutions.area_id
                    WHERE institution_classes.academic_period_id = $academic_period_id
                     $whereClause
                    GROUP BY institution_classes.institution_id)",
                    'conditions' => [
                            'classes_counter.institution_id = institutions.id',
                    ]
        ];
        $join['prev_year_students_counter'] = [
            'type' => 'left',
            'table' => "(SELECT institution_students.institution_id
                        ,COUNT(DISTINCT(institution_students.student_id)) prev_year_nb_students
                    FROM institution_students
                    INNER JOIN institutions
                    ON institutions.id = institution_students.institution_id
                    INNER JOIN areas
                    ON areas.id = institutions.area_id
                    INNER JOIN 
                    (
                        SELECT @previous_year_id := previous_current_join.academic_period_id previous_academic_period_id
                            ,@current_year_id
                        FROM
                        (
                            SELECT academic_periods.id academic_period_id
                                ,@previous_start_year := MAX(academic_periods.start_date) previous_start_year
                            FROM academic_periods
                            LEFT JOIN 
                            (
                                SELECT @current_year_id := academic_periods.id current_academic_periods_id
                                    ,@current_start_year := academic_periods.start_date curent_start_date
                                FROM academic_periods
                                WHERE academic_periods.id = $academic_period_id
                            ) t
                            ON t.current_academic_periods_id = @current_year_id
                            WHERE academic_periods.start_date < @current_start_year
                        ) subq
                        INNER JOIN
                        (
                            SELECT academic_periods.id academic_period_id
                                ,academic_periods.start_date start_year
                            FROM academic_periods
                            LEFT JOIN 
                            (
                                SELECT @current_year_id := academic_periods.id current_academic_periods_id
                                    ,@current_start_year := academic_periods.start_date curent_start_date
                                FROM academic_periods
                                WHERE academic_periods.id = $academic_period_id
                            ) t
                            ON t.current_academic_periods_id = @current_year_id
                            WHERE academic_periods.start_date < @current_start_year
                        ) previous_current_join
                        ON previous_current_join.start_year = @previous_start_year
                    ) academic_period_info
                    WHERE institution_students.academic_period_id = @previous_year_id
                    AND institution_students.student_status_id IN (1, 7, 6, 8)
                     $whereClause
                    GROUP BY institution_students.institution_id)",
                    'conditions' => [
                            'prev_year_students_counter.institution_id = institutions.id',
                    ]
            ];
        $join['curr_year_students_counter'] = [
            'type' => 'left',
            'table' => "(SELECT institution_students.institution_id
                        ,COUNT(DISTINCT(institution_students.student_id)) nb_students_all
                        ,COUNT(DISTINCT(CASE WHEN student_class_details.student_id IS NULL THEN institution_students.student_id END)) nb_students_no_class
                        ,COUNT(DISTINCT(CASE WHEN student_nationalities.security_user_id IS NULL THEN institution_students.student_id END)) nb_students_no_nationality
                        ,COUNT(DISTINCT(CASE WHEN student_identities.security_user_id IS NULL THEN institution_students.student_id END)) nb_students_no_identity
                    FROM institution_students
                    INNER JOIN institutions
                    ON institutions.id = institution_students.institution_id
                    INNER JOIN areas
                    ON areas.id = institutions.area_id
                    INNER JOIN academic_periods
                    ON academic_periods.id = institution_students.academic_period_id
                    INNER JOIN security_users
                    ON security_users.id = institution_students.student_id
                    LEFT JOIN
                    (
                        SELECT institution_class_students.student_id
                            ,institution_class_students.education_grade_id
                            ,institution_class_students.institution_id
                        FROM institution_class_students
                        INNER JOIN institutions
                        ON institutions.id = institution_class_students.institution_id
                        INNER JOIN areas
                        ON areas.id = institutions.area_id
                        INNER JOIN 
                        (
                            SELECT institution_class_students.*
                                ,MAX(institution_class_students.created) max_date
                            FROM institution_class_students
                            INNER JOIN institutions
                            ON institutions.id = institution_class_students.institution_id
                            INNER JOIN areas
                            ON areas.id = institutions.area_id
                            INNER JOIN academic_periods
                            ON academic_periods.id = institution_class_students.academic_period_id
                            WHERE institution_class_students.academic_period_id = $academic_period_id
                            AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_class_students.student_status_id = 1, institution_class_students.student_status_id IN (1, 7, 6, 8))
                             $whereClause
                            GROUP BY institution_class_students.student_id
                                ,institution_class_students.institution_id
                                ,institution_class_students.education_grade_id
                        ) latest_class
                        ON latest_class.student_id = institution_class_students.student_id
                        AND latest_class.education_grade_id = institution_class_students.education_grade_id
                        AND latest_class.academic_period_id = institution_class_students.academic_period_id
                        AND latest_class.institution_id = institution_class_students.institution_id
                        AND latest_class.max_date = institution_class_students.created
                        INNER JOIN academic_periods
                        ON academic_periods.id = institution_class_students.academic_period_id
                        WHERE academic_periods.id = $academic_period_id
                        AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_class_students.student_status_id = 1, institution_class_students.student_status_id IN (1, 7, 6, 8))
                         $whereClause
                        GROUP BY institution_class_students.student_id
                            ,institution_class_students.education_grade_id
                            ,institution_class_students.institution_id
                    ) student_class_details
                    ON student_class_details.student_id = institution_students.student_id
                    AND student_class_details.education_grade_id = institution_students.education_grade_id
                    AND student_class_details.institution_id = institution_students.institution_id
                    LEFT JOIN
                    (
                        SELECT  user_nationalities.security_user_id
                        FROM user_nationalities
                        INNER JOIN nationalities
                        ON nationalities.id = user_nationalities.nationality_id
                        WHERE user_nationalities.preferred = 1
                        GROUP BY  user_nationalities.security_user_id
                    ) AS student_nationalities
                    ON student_nationalities.security_user_id = institution_students.student_id
                    LEFT JOIN
                    (
                        SELECT  user_identities.security_user_id
                        FROM user_identities
                        INNER JOIN identity_types
                        ON identity_types.id = user_identities.identity_type_id
                        WHERE identity_types.default = 1
                        GROUP BY  user_identities.security_user_id
                    ) AS student_identities
                    ON student_identities.security_user_id = institution_students.student_id
                    WHERE academic_periods.id = $academic_period_id
                    AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))
                     $whereClause
                    GROUP BY institution_students.institution_id)",
                                'conditions' => [
                                        'curr_year_students_counter.institution_id = institutions.id',
                                ]
                ];
        $join['staff_counter'] = [
            'type' => 'left',
            'table' => "(SELECT institution_staff.institution_id
                        ,COUNT(DISTINCT(institution_staff.staff_id)) nb_staff
                        ,COUNT(DISTINCT(CASE WHEN subject_staff.staff_id IS NULL THEN institution_staff.staff_id END)) nb_staff_no_subjects
                        ,COUNT(DISTINCT(CASE WHEN staff_nationalities.security_user_id IS NULL THEN institution_staff.staff_id END)) nb_staff_no_nationality
                        ,COUNT(DISTINCT(CASE WHEN staff_identities.security_user_id IS NULL THEN institution_staff.staff_id END)) nb_staff_no_identity
                    FROM institution_staff
                    INNER JOIN institutions
                    ON institutions.id = institution_staff.institution_id
                    INNER JOIN areas
                    ON areas.id = institutions.area_id
                    INNER JOIN academic_periods 
                    ON (((institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.start_date AND institution_staff.end_date >= academic_periods.start_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date <= academic_periods.end_date AND institution_staff.end_date >= academic_periods.end_date) OR (institution_staff.end_date IS NOT NULL AND institution_staff.start_date >= academic_periods.start_date AND institution_staff.end_date <= academic_periods.end_date)) OR (institution_staff.end_date IS NULL AND institution_staff.start_date <= academic_periods.end_date))
                    LEFT JOIN
                    (
                        SELECT institution_subject_staff.institution_id
                            ,institution_subject_staff.staff_id
                        FROM institution_subject_staff
                        INNER JOIN institution_subjects
                        ON institution_subjects.id = institution_subject_staff.institution_subject_id
                        INNER JOIN institutions
                        ON institutions.id = institution_subjects.institution_id
                        INNER JOIN areas
                        ON areas.id = institutions.area_id
                        WHERE institution_subjects.academic_period_id = $academic_period_id
                         $whereClause
                        GROUP BY institution_subject_staff.institution_id
                            ,institution_subject_staff.staff_id
                    ) subject_staff
                    ON subject_staff.institution_id = institution_staff.institution_id
                    AND subject_staff.staff_id = institution_staff.staff_id
                    LEFT JOIN
                    (
                        SELECT  user_nationalities.security_user_id
                        FROM user_nationalities
                        INNER JOIN nationalities
                        ON nationalities.id = user_nationalities.nationality_id
                        WHERE user_nationalities.preferred = 1
                        GROUP BY  user_nationalities.security_user_id
                    ) AS staff_nationalities
                    ON staff_nationalities.security_user_id = institution_staff.staff_id
                    LEFT JOIN
                    (
                        SELECT  user_identities.security_user_id
                        FROM user_identities
                        INNER JOIN identity_types
                        ON identity_types.id = user_identities.identity_type_id
                        WHERE identity_types.default = 1
                        GROUP BY  user_identities.security_user_id
                    ) AS staff_identities
                    ON staff_identities.security_user_id = institution_staff.staff_id
                    WHERE academic_periods.id = $academic_period_id
                    AND institution_staff.staff_status_id = 1
                    $whereClause
                    GROUP BY institution_staff.institution_id
                )",
                'conditions' => [
                    'staff_counter.institution_id = institutions.id',
                ]
        ];
        $query
            ->select([
                'institution_code' => 'institutions.code',
                'institution_name' => 'institutions.name',
                'areas_name' => 'areas.name',
                'shift' => "(IFNULL(shifts_data.shift_names,''))",
                'Institution_contact' => "(IFNULL(institutions.telephone,''))",
                'latitude' => "(IFNULL(institutions.latitude,''))",
                'longitude' => "(IFNULL(institutions.longitude,''))",
                'institution_type' => 'institution_types.name',
                'lowest_grade' => "(IFNULL(grades.lowest_grade, ''))",
                'highest_grade' => "(IFNULL(grades.highest_grade, ''))",
                'number_of_classes' => "(IFNULL(classes_counter.nb_classes, 0))",
                'number_of_student_prev_year' => "(IFNULL(prev_year_students_counter.prev_year_nb_students, 0))",
                'number_of_student' => "(IFNULL(curr_year_students_counter.nb_students_all, 0))",
                'number_of_student_without_class' => "(IFNULL(curr_year_students_counter.nb_students_no_class, 0))",
                'number_of_student_without_nationality' => "(IFNULL(curr_year_students_counter.nb_students_no_nationality, 0))",
                'number_of_student_without_Identities' => "(IFNULL(curr_year_students_counter.nb_students_no_identity, 0))",
                'number_of_staff' => "(IFNULL(staff_counter.nb_staff, 0))",
                'number_of_staff_without_subject' => "(IFNULL(staff_counter.nb_staff_no_subjects, 0))",
                'number_of_staff_without_nationality' => "(IFNULL(staff_counter.nb_staff_no_nationality, 0))",
                'number_of_staff_without_identity' => "(IFNULL(staff_counter.nb_staff_no_identity, 0))",
            ])
            ->from(['institutions' => 'institutions'])
            ->join($join)
            ->where($where)
            ->andWhere(['institutions.institution_status_id' => 1]);
            //print_r($query->Sql());die;
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {

        $extraFields = [];
        
        $extraFields[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution code')
        ];
        $extraFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        $extraFields[] = [
            'key' => 'areas_name', 
            'field' => 'areas_name', 
            'type' => 'string',
            'label' => __('Area Name')
        ];
        $extraFields[] = [
            'key' => 'shift',
            'field' => 'shift',
            'type' => 'string',
            'label' => __('Shift')
        ];
        $extraFields[] = [
            'key' => 'Institution_contact',
            'field' => 'Institution_contact',
            'type' => 'integer',
            'label' => __('Institution Contact')
        ];
        $extraFields[] = [
            'key' => 'latitude',
            'field' => 'latitude',
            'type' => 'decimal',
            'label' => __('Latitude')
        ];
        $extraFields[] = [
            'key' => 'longitude', 
            'field' => 'longitude', 
            'type' => 'decimal',
            'label' => __('Longitude')
        ];

        $extraFields[] = [
            'key' => 'institution_type',
            'field' => 'institution_type',
            'type' => 'string',
            'label' => __('Institution Type')
        ];
        $extraFields[] = [
            'key' => 'lowest_grade',
            'field' => 'lowest_grade',
            'type' => 'string',
            'label' => __('Lowest Grade')
        ];
        $extraFields[] = [
            'key' => 'highest_grade',
            'field' => 'highest_grade',
            'type' => 'string',
            'label' => __('Highest Grade')
        ];
        $extraFields[] = [
            'key' => 'number_of_classes',
            'field' => 'number_of_classes',
            'type' => 'integer',
            'label' => __('Number of Classes')
        ];
        $extraFields[] = [
            'key' => 'number_of_student_prev_year',
            'field' => 'number_of_student_prev_year',
            'type' => 'integer',
            'label' => __('Number of Students in the Previous Year')
        ];
        $extraFields[] = [
            'key' => 'number_of_student',
            'field' => 'number_of_student',
            'type' => 'integer',
            'label' => __('Number of Students')
        ];
        $extraFields[] = [
            'key' => 'number_of_student_without_class',
            'field' => 'number_of_student_without_class',
            'type' => 'integer',
            'label' => __('Number of Students without Classes')
        ];
        $extraFields[] = [
            'key' => 'number_of_student_without_nationality',
            'field' => 'number_of_student_without_nationality',
            'type' => 'integer',
            'label' => __('Number of Students without Nationalities')
        ];
        $extraFields[] = [
            'key' => 'number_of_student_without_Identities',
            'field' => 'number_of_student_without_Identities',
            'type' => 'integer',
            'label' => __('Number of Students without Identities')
        ];
        $extraFields[] = [
            'key' => 'number_of_staff',
            'field' => 'number_of_staff',
            'type' => 'integer',
            'label' => __('Number of Staff')
        ];
        $extraFields[] = [
            'key' => 'number_of_staff_without_subject',
            'field' => 'number_of_staff_without_subject',
            'type' => 'integer',
            'label' => __('Number of Staff without Subjects')
        ];
        $extraFields[] = [
            'key' => 'number_of_staff_without_nationality',
            'field' => 'number_of_staff_without_nationality',
            'type' => 'integer',
            'label' => __('Number of Staff without Nationalities')
        ];
        $extraFields[] = [
            'key' => 'number_of_staff_without_identity',
            'field' => 'number_of_staff_without_identity',
            'type' => 'integer',
            'label' => __('Number of Staff without Identities')
        ];
        $fields->exchangeArray($extraFields);
    }


}
