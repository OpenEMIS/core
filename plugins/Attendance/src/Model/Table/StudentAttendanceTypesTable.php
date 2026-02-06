<?php

namespace Attendance\Model\Table;

use App\Model\Table\AppTable;
use DateTime;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
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
        $educationGradeId = $options['education_grade_id'];
        //POCOR-7474-HINDOL OPTIONAL START - if day_id = -1, that is no start day in this week
        $day_id = strval($options['day_id']);
        $date = new DateTime($day_id);
        $day_id = $date->format('Y-m-d'); // Format the date as desired
        //POCOR-7474-HINDOL OPTIONAL END
        $InstitutionClasses = TableRegistry::getTableLocator()->get('Institution.InstitutionClasses');
        $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
        //$StudentAttendanceTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceTypes');
        $StudentAttendanceMarkTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceMarkTypes');
        $StudentMarkTypeStatuses = TableRegistry::getTableLocator()->get('Attendance.StudentMarkTypeStatuses');
        $StudentMarkTypeStatusGrades = TableRegistry::getTableLocator()->get('Attendance.StudentMarkTypeStatusGrades');
        $InstitutionClassGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionClassGrades');
        $StudentAttendanceTypes = TableRegistry::getTableLocator()->get('Attendance.StudentAttendanceTypes');
        $StudentMarkTypeStatuses = TableRegistry::getTableLocator()->get('Attendance.StudentMarkTypeStatuses');
        $StudentMarkTypeStatusGrades = TableRegistry::getTableLocator()->get('Attendance.StudentMarkTypeStatusGrades');
        $StudentAttendancePerDayPeriods = TableRegistry::getTableLocator()->get('Attendance.StudentAttendancePerDayPeriods'); 
        // --- POCOR-9353 First try (DAY_AND_SUBJECT condition) ---
        $studentAttendanceMarkTypesData = $StudentAttendanceMarkTypes
            ->find()
            ->innerJoin(
                [$StudentMarkTypeStatuses->getAlias() => $StudentMarkTypeStatuses->getTable()],
                [$StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id') . ' = ' . $StudentAttendanceMarkTypes->aliasField('id')]
            )
            ->innerJoin(
                [$StudentAttendanceTypes->getAlias() => $StudentAttendanceTypes->getTable()],
                [$StudentAttendanceTypes->aliasField('id') . ' = ' . $StudentAttendanceMarkTypes->aliasField('student_attendance_type_id')]
            )
            ->innerJoin(
                [$StudentMarkTypeStatusGrades->getAlias() => $StudentMarkTypeStatusGrades->getTable()],
                [$StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id') . ' = ' . $StudentMarkTypeStatuses->aliasField('id')]
            )
            ->leftJoin(
                [$InstitutionClassGrades->getAlias() => $InstitutionClassGrades->getTable()],
                [
                    $InstitutionClassGrades->aliasField('education_grade_id') . ' = ' . $StudentMarkTypeStatusGrades->aliasField('education_grade_id')
                ]
            )
            ->where([
                $InstitutionClassGrades->aliasField('institution_class_id') => $institution_class_id,
                $StudentMarkTypeStatuses->aliasField('academic_period_id') => $academic_period_id,
                $StudentMarkTypeStatuses->aliasField('date_enabled <=') => $day_id,
                $StudentMarkTypeStatuses->aliasField('date_disabled >=') => $day_id,
                $StudentAttendanceTypes->aliasField('code') => 'DAY_AND_SUBJECT',
                $StudentMarkTypeStatusGrades->aliasField('education_grade_id IS') => $educationGradeId
            ])
            ->toArray();
            
        // --- If first is empty, run the fallback query ---
        if (empty($studentAttendanceMarkTypesData)) {
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
        }
        if (count($studentAttendanceMarkTypesData) > 0) {

            $query
                ->select([
                    'id' => $this->aliasField('id'),
                    'code' => $this->aliasField('code'),
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
               // POCOR-9353  start
            $check  = $query->toArray();
            if (!empty($check) && $check[0]['code'] == 'DAY_AND_SUBJECT') {
                // only select id & code, no nested StudentAttendanceMarkTypes
                $query->select([
                    'id'   => $this->aliasField('id'),
                    'code' => $this->aliasField('code')
                ]);
            } else {
                // keep your original select with 
                $query->select([
                    'id'   => $this->aliasField('id'),
                    'code' => $this->aliasField('code'),
                    $StudentAttendanceMarkTypes->aliasField('code'), // POCOR-8874
                ]);
            } // POCOR-9353  end
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
