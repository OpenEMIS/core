<?php

namespace Student\Model\Table;

use Archive\Model\Table\DataManagementConnectionsTable as ArchiveConnections;
use ArrayObject;
use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ResultSetInterface;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Locator\TableLocator;
use App\Model\Table\ControllerActionTable;

//POCOR-6658

class AttendancesTable extends ControllerActionTable
{
    private $allDayOptions = [];
    private $selectedDate;
    private $_absenceData = [];

    public function initialize(array $config): void
    {
        $this->setTable('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        //$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'institution_class_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
//        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' => 'next_institution_class_id']);
//        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        //$this->hasOne('StudentAbsencesPeriodDetails', ['className' => 'Institution.StudentAbsencesPeriodDetails']);institution_class_id

        $this->addBehavior('Institution.InstitutionTab',
            ['appliedAction' => ['Students'=>
                ['student_status_id', 'academic_period_id',],
        'StudentUser'=>
            ['student_status_id',
                'academic_period_id',]]]);

        $AbsenceTypesTable = TableRegistry::get('Institution.AbsenceTypes');
        $this->absenceList = $AbsenceTypesTable->getAbsenceTypeList();
        $this->absenceCodeList = $AbsenceTypesTable->getCodeList();

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view']
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->select([
                'student_id' => $this->aliasField('student_id'),
                'date' => 'AttendanceMarkedRecords.date',
                'period' => 'AttendanceMarkedRecords.period',
                'subject_id' => 'AttendanceMarkedRecords.subject_id',
                'comment' => 'Absences.comment',
                'absence_type_id' => 'Absences.absence_type_id',
                'student_absence_reason_id' => 'Absences.student_absence_reason_id',
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id')
            ])
            ->innerJoin(
                ['InstitutionStudents' => 'institution_students'],
                [
                    'InstitutionStudents.student_id = ' . $this->aliasField('student_id'),
                    'InstitutionStudents.institution_id = ' . $this->aliasField('institution_id'),
                    'InstitutionStudents.student_status_id = ' . $this->aliasField('student_status_id'),
                    'InstitutionStudents.education_grade_id = ' . $this->aliasField('education_grade_id'),

                ]
            )
            ->innerJoin(
                ['AttendanceMarkedRecords' => 'student_attendance_marked_records'],
                [
                    'AttendanceMarkedRecords.institution_class_id = ' . $this->aliasField('institution_class_id'),
                    'AttendanceMarkedRecords.date BETWEEN InstitutionStudents.start_date AND IFNULL(InstitutionStudents.end_date, AttendanceMarkedRecords.date)'
                ]
            )
            ->leftJoin(
                ['Absences' => 'institution_student_absence_details'],
                [
                    'Absences.student_id = ' . $this->aliasField('student_id'),
                    'Absences.date = AttendanceMarkedRecords.date'
                ]
            );

//        dd($query);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra){
        $this->setupFields();
    }
    public function setupFields()
    {
        $this->fields['next_institution_class_id']['visible'] = false;
        $this->fields['institution_student_absence_day_id']['visible'] = false;
        $this->fields['education_grade_id']['visible'] = false;
        $this->field('comment', ['visible' => true, 'attr' => ['label' => __('Comate')]]);
        $this->fields['student_absence_reason_id']['visible'] = true;
        $this->field('institution_class_id', ['visible' => true, 'type' => 'text']);
        $this->field('date', ['visible' => true, 'attr' => ['label' => __('Date')]]);
        $this->field('periods', ['visible' => true]);
        $this->field('subjects', ['visible' => true]);
        $this->setFieldOrder(['date', 'periods', 'subjects', 'institution_class_id', 'absence_type_id']);

    }



    public function onGetInstitutionClassId(Event $event, $entity)
    {
//        return 'a';
        return $entity->institution_class->name;

//        return $result->name;
    }
    public function onGetComment(Event $event, Entity $entity)
    {

        return $entity->comment;
    }





}
