<?php
namespace Schedule\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class ScheduleTimeslots extends Entity
{
    protected $_virtual = ['start_time', 'end_time'];

    protected function _getStartTime()
    {
        return $this->_calculateTime('start');
    }

    protected function _getEndTime()
    {
        return $this->_calculateTime('end');
    }

    private function _calculateTime($column)
    {
        // only for timeslot add only
        if ($column == 'start' && $this->has('start_time_add')) {
            return $this->start_time_add;
        } 
        if ($column == 'end' && $this->has('end_time_add')) {
            return $this->end_time_add;
        }

        if ($this->has('schedule_interval')) {
            if ($this->schedule_interval->has('shift')) {
                $startTime = $this->schedule_interval->shift->start_time;
            } else {
                $InstitutionShiftsTable = TableRegistry::get('Institution.InstitutionShifts');
                $shiftId = $this->schedule_interval->institution_shift_id;
                $startTime = $InstitutionShiftsTable->get($shiftId)->start_time;
            }
        } else {
            $ScheduleIntervalsTable = TableRegistry::get('Schedule.ScheduleIntervals');
            $startTime = $ScheduleIntervalsTable
                ->find()
                ->select(['start_time' => 'Shifts.start_time'])
                ->contain(['Shifts'])
                ->where([
                    $ScheduleIntervalsTable->aliasField('id') => $this->institution_schedule_interval_id
                ])
                ->extract('start_time')
                ->first();
        }

        // only for timeslot add only
        if (is_null($startTime)) {
            return '-';
        }

        if ($column == 'start') {
            $operator = ' < ';
        } else {
            $operator = ' <= ';
        }

        $ScheduleTimeslotsTable = TableRegistry::get($this->source());
        $totalIntervalQuery = $ScheduleTimeslotsTable->find();

        $totalInterval = $totalIntervalQuery
            ->select([
                'total' => $totalIntervalQuery->func()->sum($ScheduleTimeslotsTable->aliasField('interval'))
            ])
            ->where([
                $ScheduleTimeslotsTable->aliasField('institution_schedule_interval_id') => $this->institution_schedule_interval_id,
                $ScheduleTimeslotsTable->aliasField('order') . $operator => $this->order
            ])
            ->extract('total')
            ->first();

        if (is_null($totalInterval)) {
            $totalInterval = 0;
        }

        $modifyTimeString = '+' . $totalInterval . ' minutes';
        $timeObj = $startTime->modify($modifyTimeString);
        return $ScheduleTimeslotsTable->formatTime($timeObj);
    }
}
