<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

/**
 * Get the Student Absence per day Report details in excel file 
 * @ticket POCOR-7276
 * return array
 */
class StudentAbsencesPerDaysTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_absence_details'); 
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'institution_class_id']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Institution.EducationGrades', 'foreignKey' =>'education_grade_id']);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'excludes' => [
                'start_year',
                'end_year',
                'full_day',
                'start_date',
                'start_time',
                'end_time',
                'end_date'
            ],
            'pages' => false,
            'autoFields' => false
        ]);
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
         $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $InstitutionSubjects = TableRegistry::get('institution_subjects');
        $grades = TableRegistry::get('education_grades');
        $academicPeriod = TableRegistry::get('academic_periods');
        $securityUsers = TableRegistry::get('security_users');
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
                //$conditions['institutions.area_id IN'] = $allselectedAreas;
                $conditions = "AND institutions.area_id IN = ".$allselectedAreas;
        }
        if (empty($institutionId) && $institutionId == 0) { 
           $condition = NULL;
        }else{
            $condition = "AND institution_student_absence_details.institution_id = ".$institutionId;
        }

        $subQuery = "(SELECT areas.code area_code
        ,areas.name area_name
        ,institutions.name institution_name
        ,institutions.code institution_code
        ,education_grades.name education_grade_name
        ,institution_classes.name institution_class_name
        ,security_users.openemis_no 
        ,REPLACE(REPLACE(CONCAT_WS(' ', security_users.first_name, security_users.middle_name, security_users.third_name, security_users.last_name), '   ', ' '), '  ', ' ') student_name
        ,genders.name gender_name
        ,institution_student_absence_details.date absence_date
        ,period_counter.attendance_per_day 
        ,IF(attendance_type.value = 1, 'Mark absent if one or more records absent', 'Mark present if one or more records present') absence_configuration
        ,IFNULL(student_identities.identity_type, '') identity_type
        ,IFNULL(student_identities.identity_number, '') identity_number
        ,IFNULL(security_users.address, '') student_address
        ,IFNULL(contact_info.contacts, '') student_contacts
        ,attendance_type.value 
    FROM institution_student_absence_details
    INNER JOIN security_users
    ON security_users.id = institution_student_absence_details.student_id
    INNER JOIN genders
    ON genders.id = security_users.gender_id
    INNER JOIN institutions
    ON institutions.id = institution_student_absence_details.institution_id
    INNER JOIN areas
    ON areas.id = institutions.area_id
    INNER JOIN institution_classes
    ON institution_classes.id = institution_student_absence_details.institution_class_id
    INNER JOIN education_grades
    ON education_grades.id = institution_student_absence_details.education_grade_id
    INNER JOIN academic_periods
    ON academic_periods.id = institution_student_absence_details.academic_period_id
    INNER JOIN 
    (
        SELECT student_mark_type_status_grades.education_grade_id
            ,student_mark_type_statuses.academic_period_id
            ,student_attendance_mark_types.attendance_per_day
        FROM student_mark_type_status_grades
        INNER JOIN student_mark_type_statuses
        ON student_mark_type_statuses.id = student_mark_type_status_grades.student_mark_type_status_id
        INNER JOIN student_attendance_mark_types
        ON student_attendance_mark_types.id = student_mark_type_statuses.student_attendance_mark_type_id
        GROUP BY student_mark_type_status_grades.education_grade_id
            ,student_mark_type_statuses.academic_period_id
    ) period_counter
    ON period_counter.education_grade_id = education_grades.id
    AND period_counter.academic_period_id = academic_periods.id
    CROSS JOIN
    (
        SELECT config_items.value
        FROM config_items
        WHERE config_items.code LIKE 'calculate_daily_attendance'
    ) attendance_type
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
    ) AS student_identities
    ON student_identities.security_user_id = security_users.id
    LEFT JOIN 
    (
        SELECT user_contacts.security_user_id
            ,GROUP_CONCAT(CONCAT(' ', contact_options.name, ' (', contact_types.name, '): ', user_contacts.value)) contacts
        FROM user_contacts
        INNER JOIN contact_types
        ON contact_types.id = user_contacts.contact_type_id
        INNER JOIN contact_options
        ON contact_options.id = contact_types.contact_option_id
        WHERE user_contacts.preferred = 1
        GROUP BY user_contacts.security_user_id
    ) contact_info
    ON contact_info.security_user_id = security_users.id
    WHERE institution_student_absence_details.subject_id = 0
    AND institution_student_absence_details.academic_period_id = $academicPeriodId
    AND institution_student_absence_details.absence_type_id != 3
    $condition
    GROUP BY institutions.id 
        ,education_grades.id
        ,institution_classes.id
        ,institution_student_absence_details.student_id
        ,institution_student_absence_details.date
    HAVING 
        CASE 
            WHEN attendance_type.value = 1 
            THEN COUNT(*) >= 1 
            ELSE COUNT(*) >= period_counter.attendance_per_day
        END
    ORDER BY institutions.name
        ,education_grades.name
        ,institution_classes.name
        ,security_users.openemis_no
        ,institution_student_absence_details.date)";
    

        $query
            ->select([
                'area_code' => 'subq.area_code',
                'area_name' => 'subq.area_name',
                'institution_code' => 'subq.institution_code',
                'institution_name' => 'subq.institution_name',
                'education_grade_name' => 'subq.education_grade_name',
                'institution_class_name' => 'subq.institution_class_name',
                'openemis_no' => 'subq.openemis_no',
                'student_name' => 'subq.student_name',
                'gender_name' => 'subq.gender_name',
                'absence_date' => 'subq.absence_date',
                'attendance_per_day' => 'subq.attendance_per_day',
                'absence_configuration' => 'subq.absence_configuration',
                'identity_type' => 'subq.identity_type',
                'identity_number' => 'subq.identity_number',
                'student_address' => 'subq.student_address',
                'student_contacts' => 'subq.student_contacts',
            ])
            ->from(['subq' => $subQuery]);
            
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {

        $extraFields = [];

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
            'field' => 'institution_class_name',
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
            'key' => 'gender_name',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => __('Gender Name')
        ];
        $extraFields[] = [
            'key' => 'absence_date',
            'field' => 'absence_date',
            'type' => 'date',
            'label' => __('Absence Date')
        ];
        $extraFields[] = [
            'key' => 'attendance_per_day',
            'field' => 'attendance_per_day',
            'type' => 'string',
            'label' => __('Number of Period')
        ];
        $extraFields[] = [
            'key' => 'absence_configuration',
            'field' => 'absence_configuration',
            'type' => 'string',
            'label' => __('Attendance Configuration')
        ];
        $extraFields[] = [
            'key' => 'identity_type',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Default Identity Type')
        ];
        $extraFields[] = [
            'key' => 'identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        $extraFields[] = [
            'key' => 'student_address',
            'field' => 'student_address',
            'type' => 'string',
            'label' => __('Address')
        ];
        $extraFields[] = [
            'key' => 'student_contacts',
            'field' => 'student_contacts',
            'type' => 'string',
            'label' => __('Contacts')
        ];
        
        $fields->exchangeArray($extraFields);
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
}
