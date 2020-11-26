<?php
namespace Attendance\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;

class StudentAttendanceTypesTable extends AppTable
{
    const DAY = 1;
    const SUBJECT = 2;

    public function initialize(array $config)
    {
        $this->table('student_attendance_types');
        parent::initialize($config);

        $this->hasMany('StudentAttendanceMarkTypes', ['className' => 'Attendance.StudentAttendanceMarkTypes', 'dependent' => true, 'cascadeCallbacks' => true]);
    }

    public function findAttendanceTypeCode(Query $query, array $options)
    {
		$institution_id = $options['institution_id'];
		$academic_period_id = $options['academic_period_id'];
       $institution_class_id = $options['institution_class_id'];
       $day_id = $options['day_id'];
       $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
       $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
       //$StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
       $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
       $StudentMarkTypeStatuses = TableRegistry::get('Attendance.StudentMarkTypeStatuses');
       $StudentMarkTypeStatusGrades = TableRegistry::get('Attendance.StudentMarkTypeStatusGrades');
       $studentAttendanceMarkTypesData = $StudentAttendanceMarkTypes
       									 ->find()
                                         ->leftJoin(
                                        [$StudentMarkTypeStatuses->alias() => $StudentMarkTypeStatuses->table()],
                                        [
                                         $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id = ') . $StudentAttendanceMarkTypes->aliasField('id')
                                        ]
                                        )
                                        ->leftJoin(
                                        [$StudentMarkTypeStatusGrades->alias() => $StudentMarkTypeStatusGrades->table()],
                                        [
                                         $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id = ') . $StudentMarkTypeStatuses->aliasField('id')
                                        ]
                                        )
       									 ->leftJoin(
		                				[$InstitutionClassGrades->alias() => $InstitutionClassGrades->table()],
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
            'code' => $this->aliasField('code')
        ])
        ->leftJoin(
                [$StudentAttendanceMarkTypes->alias() => $StudentAttendanceMarkTypes->table()],
                [
                    $StudentAttendanceMarkTypes->aliasField('student_attendance_type_id = ') . $this->aliasField('id')
                ]
            )
        ->leftJoin(
        [$StudentMarkTypeStatuses->alias() => $StudentMarkTypeStatuses->table()],
        [
         $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id = ') . $StudentAttendanceMarkTypes->aliasField('id') 
        ]
        )
        ->leftJoin(
        [$StudentMarkTypeStatusGrades->alias() => $StudentMarkTypeStatusGrades->table()],
        [
         $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id = ') . $StudentMarkTypeStatuses->aliasField('id')
        ]
        )
        ->leftJoin(
                [$InstitutionClassGrades->alias() => $InstitutionClassGrades->table()],
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
