<?php
namespace Attendance\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Attendance\Model\Table\StudentAttendanceTypesTable as AttendanceTypes;

class StudentAttendanceMarkTypesTable extends AppTable
{
    const MAX_MARK_DAYS = 5;
    const DEFAULT_ATTENDANCE_PER_DAY = 1;

    public function initialize(array $config)
    {
        $this->table('student_attendance_mark_types');
        parent::initialize($config);

        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('StudentAttendanceTypes', ['className' => 'Attendance.StudentAttendanceTypes', 'foreignKey' => 'attendance_type_id']);
    }

    public function getDefaultMarkType()
    {
        $defaultTypes = [
            'student_attendance_type_id' => AttendanceTypes::DAY,
            'attendance_per_day' => self::DEFAULT_ATTENDANCE_PER_DAY
        ];

        return $defaultTypes;
    }

    public function getAttendancePerDayOptions()
    {
        $options = [];

        for ($i = 1; $i <= self::MAX_MARK_DAYS; ++$i) {
            $options[$i] = $i;
        }

        return $options;
    }

    public function isDefaultType($attendancePerDay, $attendanceTypeId)
    {
        return ($attendancePerDay == self::DEFAULT_ATTENDANCE_PER_DAY && $attendanceTypeId == AttendanceTypes::DAY);
    }

    public function getAttendancePerDayOptionsByClass($classId, $academicPeriodId)
    {
        $prefix = 'Period ';
        $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $gradesResultSet = $InstitutionClassGrades
            ->find('list', [
                'keyField' => 'education_grade_id',
                'valueField' => 'education_grade_id'
            ])
            ->where([$InstitutionClassGrades->aliasField('institution_class_id') => $classId])
            ->all();

        if (!$gradesResultSet->isEmpty()) {
            $gradeList = $gradesResultSet->toArray();
            $attendencePerDay = self::DEFAULT_ATTENDANCE_PER_DAY;

            $markResultSet = $this
                ->find()
                ->where([
                    $this->aliasField('education_grade_id IN ') => $gradeList,
                    $this->aliasField('academic_period_id') => $academicPeriodId
                ])
                ->all();

            if (!$markResultSet->isEmpty()) {
                $marksEntity = $markResultSet->first();
                $attendencePerDay = $marksEntity->attendance_per_day;
            }

            $options = [];
            for ($i = 1; $i <= $attendencePerDay; ++$i) {
                $options[$i] = $prefix . $i;
            }

            return $options;
        }
    }
}
