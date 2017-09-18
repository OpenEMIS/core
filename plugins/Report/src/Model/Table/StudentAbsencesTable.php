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

class StudentAbsencesTable extends AppTable {
    public function initialize(array $config) {
        $this->table('institution_student_absences');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('StudentAbsenceReasons', ['className' => 'Institution.StudentAbsenceReasons']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);

        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Excel', [
            'excludes' => [
                'start_year',
                'end_year',
                'student_id',
                'institution_id',
                'full_day',
                'start_date',
                'start_time',
                'end_time',
                'end_date',
                'student_absence_reason_id',
            ],
            'pages' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {

        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;

        // if (!is_null($academicPeriodId) && $academicPeriodId != 0) {
        //  $query->find('academicPeriod', ['academic_period_id' => $academicPeriodId]);
        // }

        $query
            ->select([
                'openemis_no' => 'Users.openemis_no',
                'student_id' => 'StudentAbsences.student_id',
                'institution_id' => 'StudentAbsences.institution_id',
                'code' => 'Institutions.code',
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'area_administrative_code' => 'AreaAdministratives.code',
                'area_administrative_name' => 'AreaAdministratives.name',
                'area_level_name' => 'AreaLevels.name',
                'absence_type_id' => 'StudentAbsences.absence_type_id',
                'absence_type' => 'AbsenceTypes.name',
                'student_absence_reason_id' => 'StudentAbsences.student_absence_reason_id',
                'comment' => 'StudentAbsences.comment',
                'full_day' => 'StudentAbsences.full_day',
                'start_date' => 'StudentAbsences.start_date',
                'end_date' => 'StudentAbsences.end_date',
                'start_time' => 'StudentAbsences.start_time',
                'end_time' => 'StudentAbsences.end_time'
            ])
            ->where([
                $this->aliasField('start_date >= ') => '2017-01-01',
                $this->aliasField('end_date <= ') => '2017-12-31'
            ])
            ->contain([
                //'Users', 'Institutions', 'StudentAbsenceReasons', 'AbsenceTypes',
                'Institutions.Areas.AreaLevels',
                'Institutions.AreaAdministratives',

            ])
            ->order([
                $this->aliasField('student_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('start_date')
            ]);
        
        // pr($query);//die;
    }

    // To select another one more field from the containable data
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {
        pr($fields);die;
        $newArray = [];
        $newArray[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'StudentAbsences.student_id',
            'field' => 'student_id',
            'type' => 'integer',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'StudentAbsences.institution_id',
            'field' => 'institution_id',
            'type' => 'string',
            'label' => ''
        ];
        $newArray[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
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
            'key' => 'StudentAbsences.absences',
            'field' => 'absences',
            'type' => 'string',
            'label' => __('Absences')
        ];
        $newArray[] = [
            'key' => 'StudentAbsences.student_absence_reason_id',
            'field' => 'student_absence_reason_id',
            'type' => 'string',
            'label' => ''
        ];

        $newFields = array_merge($newArray, $fields->getArrayCopy());
        $fields->exchangeArray($newFields);
    }

    public function onExcelGetStudentAbsenceReasonId(Event $event, Entity $entity) {
        if ($entity->student_absence_reason_id == 0) {
            return __('Unexcused');
        }
    }

    public function onExcelGetAbsences(Event $event, Entity $entity) {
        $startDate = "";
        $endDate = "";

        if (!empty($entity->start_date)) {
            $startDate = $this->formatDate($entity->start_date);
        } else {
            $startDate = $entity->start_date;
        }

        if (!empty($entity->end_date)) {
            $endDate = $this->formatDate($entity->end_date);
        } else {
            $endDate = $entity->end_date;
        }

        if ($entity->full_day) {
            return sprintf('%s %s (%s - %s)', __('Full'), __('Day'), $startDate, $endDate);
        } else {
            $startTime = $entity->start_time;
            $endTime = $entity->end_time;
            return sprintf('%s (%s - %s) %s (%s - %s)', __('Non Full Day'), $startDate, $endDate, __('Time'), $startTime, $endTime);
        }
    }

    public function onExcelGetAbsenceTypeId(Event $event, Entity $entity) {
        // return $entity->absence_type;
        pr($entity);
    }
}
