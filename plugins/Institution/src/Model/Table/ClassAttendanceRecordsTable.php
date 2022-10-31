<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class ClassAttendanceRecordsTable extends AppTable
{
    const NOT_VALID = -1;
    const NOT_MARKED = 0;
    const MARKED = 1;
    const PARTIAL_MARKED = 2;
    const DAY_COLUMN_PREFIX = 'day_';

    public function initialize(array $config)
    {
        $this->table('institution_class_attendance_records');
        parent::initialize($config);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.StudentAttendances.afterSaveCommit'] = 'markedRecordAfterSave';
        return $events;
    }

    public function markedRecordAfterSave(Event $event, Entity $entity)
    {
        $institutionClassId = $entity->institution_class_id;
        $educationGradeId = $entity->education_grade_id;
        $institutionId = $entity->institution_id;
        $academicPeriodId = $entity->academic_period_id;
        $date = $entity->date;

        $year = $date->format('Y');
        $month = $date->format('n');
        $day = $date->format('j');

        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
        $totalMarkedCount = $StudentAttendanceMarkedRecords
            ->find()
            ->where([
                $StudentAttendanceMarkedRecords->aliasField('institution_id') => $institutionId,
                $StudentAttendanceMarkedRecords->aliasField('academic_period_id') => $academicPeriodId,
                $StudentAttendanceMarkedRecords->aliasField('institution_class_id') => $institutionClassId,
                $StudentAttendanceMarkedRecords->aliasField('education_grade_id') => $educationGradeId,
                $StudentAttendanceMarkedRecords->aliasField('date') => $date 
            ])
            ->count();

        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
        $attendancePerDay = $StudentAttendanceMarkTypes->getAttendancePerDayByClass($institutionClassId, $academicPeriodId);

        if ($totalMarkedCount >= $attendancePerDay) {
            $markedType = self::MARKED;
        } else {
            $markedType = self::PARTIAL_MARKED;
        }

        $entityData = [
            'institution_class_id' => $institutionClassId,
            'academic_period_id' => $academicPeriodId,
            'year' => $year,
            'month' => $month,
            self::DAY_COLUMN_PREFIX . $day => $markedType
        ];

        $entity = $this->newEntity($entityData);
        $this->save($entity);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $invalidDays = $this->getInvalidDaysForMonth($entity->month, $entity->year);

            foreach ($invalidDays as $day) {
                $dayColumn = self::DAY_COLUMN_PREFIX . $day;
                $entity->{$dayColumn} = self::NOT_VALID;
            }
        }
    }

    public function isDateMarked($institutionClassId, $academicPeriodId, $date)
    {
        $year = date('Y', strtotime($date));
        $month = date('n', strtotime($date));

        $classAttendanceResults = $this
            ->find()
            ->where([
                $this->aliasField('institution_class_id') => $institutionClassId,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('year') => $year,
                $this->aliasField('month') => $month
            ])
            ->all();

        if ($classAttendanceResults->isEmpty()) {
            return false;
        } else {
            $classAttendanceRecord = $classAttendanceResults->first();

            $day = date('j', strtotime($date));
            $dayColumn = self::DAY_COLUMN_PREFIX . $day;

            return $classAttendanceRecord->{$dayColumn} == self::MARKED;
        }
    }
    
    private function getInvalidDaysForMonth($month, $year)
    {
        $invalidDays = [];
        
        if (in_array($month, [4, 6, 9, 11])) {
            $invalidDays = [31];
        } elseif ($month == 2) {
            $invalidDays = [30, 31];
            // check if the date is not a leap year
            if (date('L', mktime(0, 0, 0, 1, 1, $year)) != '1') {
                $invalidDays[] = 29;
            }
        }

        return $invalidDays;
    }
}
