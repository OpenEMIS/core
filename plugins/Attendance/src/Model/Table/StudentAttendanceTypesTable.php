<?php

namespace Attendance\Model\Table;

use App\Model\Table\AppTable;
use DateTime;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\ControllerActionTable;

class StudentAttendanceTypesTable extends ControllerActionTable
{
    const DAY = 1;
    const SUBJECT = 2;

    public function initialize(array $config): void
    {
        $this->setTable('student_attendance_types');
        parent::initialize($config);

        $this->hasMany('StudentAttendanceMarkTypes', ['className' => 'Attendance.StudentAttendanceMarkTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index'],
            'Staff' => ['index'],
            'Results' => ['index'],
            'StudentExaminationResults' => ['index'],
            'OpenEMIS_Classroom' => ['index', 'view'],
            'InstitutionStaffAttendances' => ['index', 'view'],
            'StudentAttendances' => ['index', 'view'],
            'ScheduleTimetable' => ['index']
        ]);
    }

    public function findAttendanceTypeCode(Query $query, array $options)
    {
        $institution_id = $options['institution_id'];
        $academic_period_id = $options['academic_period_id'];
        $institution_class_id = $options['institution_class_id'];
        //POCOR-7474-HINDOL OPTIONAL START - if day_id = -1, that is no start day in this week
        $day_id = strval($options['day_id']);
        $date = new DateTime($day_id);
        $day_id = $date->format('Y-m-d'); // Format the date as desired
        //POCOR-7474-HINDOL OPTIONAL END
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        //$StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
        $StudentMarkTypeStatuses = TableRegistry::get('Attendance.StudentMarkTypeStatuses');
        $StudentMarkTypeStatusGrades = TableRegistry::get('Attendance.StudentMarkTypeStatusGrades');
        $studentAttendanceMarkTypesData = $StudentAttendanceMarkTypes
            ->find()
            ->leftJoin(
                [$StudentMarkTypeStatuses->getAlias() => $StudentMarkTypeStatuses->getTable()],
                [
                    $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id = ') . $StudentAttendanceMarkTypes->aliasField('id')
                ]
            )
            ->leftJoin(
                [$StudentMarkTypeStatusGrades->getAlias() => $StudentMarkTypeStatusGrades->getTable()],
                [
                    $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id = ') . $StudentMarkTypeStatuses->aliasField('id')
                ]
            )
            ->leftJoin(
                [$InstitutionClassGrades->getAlias() => $InstitutionClassGrades->getTable()],
                [
                    $InstitutionClassGrades->aliasField('education_grade_id = ') . $StudentMarkTypeStatusGrades->aliasField('education_grade_id')
                ]
            )
            ->where([
                $InstitutionClassGrades->aliasField('institution_class_id') => $institution_class_id,
                $StudentMarkTypeStatuses->aliasField('academic_period_id') => $academic_period_id,
                $StudentMarkTypeStatuses->aliasField('date_enabled <= ') => $day_id,
                $StudentMarkTypeStatuses->aliasField('date_disabled >= ') => $day_id
            ])
            ->toArray();


        if (count($studentAttendanceMarkTypesData) > 0) {

            $query
                ->select([
                    'id' => $this->aliasField('id'),
                    'code' => $this->aliasField('code'),
                    $StudentAttendanceMarkTypes->aliasField('code'), //POCOR-8874
                ])
                ->leftJoin(
                    [$StudentAttendanceMarkTypes->getAlias() => $StudentAttendanceMarkTypes->getTable()],
                    [
                        $StudentAttendanceMarkTypes->aliasField('student_attendance_type_id = ') . $this->aliasField('id')
                    ]
                )
                ->leftJoin(
                    [$StudentMarkTypeStatuses->getAlias() => $StudentMarkTypeStatuses->getTable()],
                    [
                        $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id = ') . $StudentAttendanceMarkTypes->aliasField('id')
                    ]
                )
                ->leftJoin(
                    [$StudentMarkTypeStatusGrades->getAlias() => $StudentMarkTypeStatusGrades->getTable()],
                    [
                        $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id = ') . $StudentMarkTypeStatuses->aliasField('id')
                    ]
                )
                ->leftJoin(
                    [$InstitutionClassGrades->getAlias() => $InstitutionClassGrades->getTable()],
                    [
                        $InstitutionClassGrades->aliasField('education_grade_id = ') . $StudentMarkTypeStatusGrades->aliasField('education_grade_id')
                    ]
                )
                ->where([
                    $InstitutionClassGrades->aliasField('institution_class_id') => $institution_class_id,
                    $StudentMarkTypeStatuses->aliasField('academic_period_id') => $academic_period_id,
                    $StudentMarkTypeStatuses->aliasField('date_enabled <= ') => $day_id,
                    $StudentMarkTypeStatuses->aliasField('date_disabled >= ') => $day_id

                ])
                ->group([$InstitutionClassGrades->aliasField('institution_class_id')]);
                
            return $query;
        } else {
            $query
                ->select([
                    'id' => $this->aliasField('id'),
                    'code' => $this->aliasField('code')
                ])
                ->where([
                    $this->aliasField('code') => 'DAY'
                ]);
            return $query;
        }
    }
}
