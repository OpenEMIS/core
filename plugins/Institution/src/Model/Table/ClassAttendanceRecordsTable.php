<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;

class ClassAttendanceRecordsTable extends AppTable
{
    const NOT_VALID = -1;
    const NOT_MARKED = 0;
    const MARKED = 1;
    const DAY_COLUMN_PREFIX = 'day_';

    public function initialize(array $config)
    {
        $this->table('institution_class_attendance_records');
        parent::initialize($config);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
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
