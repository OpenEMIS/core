<?php
namespace Attendance\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Attendance\Model\Table\StudentAttendanceTypesTable as AttendanceTypes;
use Cake\Log\Log;

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
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view']
        ]);
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

    public function getAttendancePerDayByClass($classId, $academicPeriodId)
    {
        $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $gradeId = $InstitutionClassGrades
            ->find()
            ->where([$InstitutionClassGrades->aliasField('institution_class_id') => $classId])
            ->extract('education_grade_id')
            ->first();

        if (!is_null($gradeId)) {
            $attendancePerDay = $this
                ->find()
                ->where([
                    $this->aliasField('education_grade_id') => $gradeId,
                    $this->aliasField('academic_period_id') => $academicPeriodId
                ])
                ->extract('attendance_per_day')
                ->first();

            if (!is_null($attendancePerDay)) {
                return $attendancePerDay;
            } else {
                return self::DEFAULT_ATTENDANCE_PER_DAY;
            }
        } else {
            Log::write('error', 'Error extracting education_grade_id for class_id ' . $classId);
        }
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
                $options[] = [
                    'id' => $i,
                    'name' => __($prefix . $i)
                ];
            }

            return $options;
        }
    }

    public function findPeriodByClass(Query $query, array $options)
    {
        $institionClassId = $options['institution_class_id'];
        $academicPeriodId = $options['academic_period_id'];

        $attendanceOptions = $this->getAttendancePerDayOptionsByClass($institionClassId, $academicPeriodId);

        return $query
            ->formatResults(function (ResultSetInterface $results) use ($attendanceOptions) {
                return $attendanceOptions;
            });
    }
}
