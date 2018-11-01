<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class StudentAbsencesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_absences');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        // $this->belongsTo('StudentAbsenceReasons', ['className' => 'Institution.StudentAbsenceReasons', 'foreignKey' =>'student_absence_reason_id']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
        $this->belongsTo('InstitutionStudentAbsenceDays', ['className' => 'Institution.InstitutionStudentAbsenceDays', 'foreignKey' =>'institution_student_absence_day_id']);
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
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {

        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;

        if (!is_null($academicPeriodId) && $academicPeriodId != 0) {
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $periodEntity = $AcademicPeriods->get($academicPeriodId);

            $startDate = $periodEntity->start_date->format('Y-m-d');
            $endDate = $periodEntity->end_date->format('Y-m-d');
        }

        $query
            ->select([
                'openemis_no' => 'Users.openemis_no',
                'student_first_name' => 'Users.first_name',
                'student_middle_name' => 'Users.middle_name',
                'student_third_name' => 'Users.third_name',
                'student_last_name' => 'Users.last_name',
                'student_preferred_name' => 'Users.preferred_name',
                'institution_name' => 'Institutions.name',
                'institution_code' => 'Institutions.code',
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'area_administrative_code' => 'AreaAdministratives.code',
                'area_administrative_name' => 'AreaAdministratives.name',
                'area_level_name' => 'AreaLevels.name',
                'absence_type' => 'AbsenceTypes.name',
                // 'student_absence_reason' => 'StudentAbsenceReasons.name',
                'date' => 'StudentAbsences.date',
                // 'comment' => 'StudentAbsences.comment',
                // 'full_day' => 'StudentAbsences.full_day',
                // 'start_date' => 'StudentAbsences.start_date',
                // 'end_date' => 'StudentAbsences.end_date',
                // 'start_time' => 'StudentAbsences.start_time',
                // 'end_time' => 'StudentAbsences.end_time'
            ])
            ->where([
                $this->aliasField('date >= ') => $startDate,
                $this->aliasField('date <= ') => $endDate
                // $this->aliasField('start_date >= ') => $startDate,
                // $this->aliasField('end_date <= ') => $endDate
            ])
            ->contain([
                'Users',
                'Institutions.Areas.AreaLevels',
                'Institutions.AreaAdministratives',

            ])
            ->order([
                $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('date')
            ]);
    }

    // To select another one more field from the containable data
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newArray = [];
        $newArray[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'Users.student_name',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student')
        ];
        $newArray[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'Areas.code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Education Code')
        ];

        $newArray[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Education')
        ];

        $newArray[] = [
            'key' => 'AreaAdministratives.code',
            'field' => 'area_administrative_code',
            'type' => 'string',
            'label' => __('Area Administrative Code')
        ];

        $newArray[] = [
            'key' => 'AreaAdministratives.name',
            'field' => 'area_administrative_name',
            'type' => 'string',
            'label' => __('Area Administrative')
        ];
        $newArray[] = [
            'key' => 'AreaLevels.name',
            'field' => 'area_level_name',
            'type' => 'string',
            'label' => __('Area Level')
        ];
        $newArray[] = [
            'key' => 'StudentAbsences.date',
            'field' => 'date',
            'type' => 'string',
            'label' => __('Date')
        ];
        // $newArray[] = [
        //     'key' => 'StudentAbsences.absences',
        //     'field' => 'absences',
        //     'type' => 'string',
        //     'label' => __('Absences')
        // ];
        // $newArray[] = [
        //     'key' => 'StudentAbsences.comment',
        //     'field' => 'comment',
        //     'type' => 'text',
        //     'label' => __('Comment')
        // ];
        $newArray[] = [
            'key' => 'StudentAbsences.absence_type_id',
            'field' => 'absence_type_id',
            'type' => 'integer',
            'label' => __('Absence Type'),
        ];
        // $newArray[] = [
        //     'key' => 'StudentAbsences.student_absence_reason_id',
        //     'field' => 'student_absence_reason_id',
        //     'type' => 'string',
        //     'label' => __('Absence Reason')
        // ];

        // $newFields = array_merge($newArray, $fields->getArrayCopy());
        $fields->exchangeArray($newArray);
    }

    public function onExcelGetDate(Event $event, Entity $entity)
    {
        return $this->formatDate($entity->date);
    }

    // public function onExcelGetAbsences(Event $event, Entity $entity)
    // {
    //     $startDate = "";
    //     $endDate = "";

    //     if (!empty($entity->start_date)) {
    //         $startDate = $this->formatDate($entity->start_date);
    //     } else {
    //         $startDate = $entity->start_date;
    //     }

    //     if (!empty($entity->end_date)) {
    //         $endDate = $this->formatDate($entity->end_date);
    //     } else {
    //         $endDate = $entity->end_date;
    //     }

    //     if ($entity->full_day) {
    //         return sprintf('%s %s (%s - %s)', __('Full'), __('Day'), $startDate, $endDate);
    //     } else {
    //         $startTime = $entity->start_time;
    //         $endTime = $entity->end_time;
    //         return sprintf('%s (%s - %s) %s (%s - %s)', __('Non Full Day'), $startDate, $endDate, __('Time'), $startTime, $endTime);
    //     }
    // }

    public function onExcelGetAbsenceTypeId(Event $event, Entity $entity)
    {
        return $entity->absence_type;
    }

    // public function onExcelGetStudentAbsenceReasonId(Event $event, Entity $entity)
    // {
    //     if (empty($entity->student_absence_reason)) {
    //         return __('Unexcused');
    //     } else {
    //         return $entity->student_absence_reason;
    //     }
    // }

    public function onExcelGetInstitutionName(Event $event, Entity $entity)
    {

        return $entity->institution_id;
    }

    public function onExcelGetStudentName(Event $event, Entity $entity)
    {
        //cant use $this->Users->get() since it will load big data and cause memory allocation problem

        $studentName = [];
        ($entity->student_first_name) ? $studentName[] = $entity->student_first_name : '';
        ($entity->student_middle_name) ? $studentName[] = $entity->student_middle_name : '';
        ($entity->student_third_name) ? $studentName[] = $entity->student_third_name : '';
        ($entity->student_last_name) ? $studentName[] = $entity->student_last_name : '';

        return implode(' ', $studentName);
    }
}
