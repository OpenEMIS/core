<?php
namespace App\Model\Table;

class CalendarEventDatesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('calendar_event_dates');
        parent::initialize($config);

        $this->belongsTo('Calendars', ['className' => 'Calendars', 'foreignKey' => 'calendar_event_id']);
    }

    public function isSchoolClosed($date, $institutionId = null) 
    {
        $findInstitutions = [-1];
        if (!is_null($institutionId)) {
            $findInstitutions[] = $institutionId;
        }

        $dateEvents = $this
            ->find()
            ->contain(['Calendars.CalendarTypes'])
            ->where([
                ['Calendars.institution_id IN ' => $findInstitutions],
                $this->aliasField('date') => $date
            ])
            ->toArray();

        if (!empty($dateEvents)) {
            $isAttendanceRequired = [];
            foreach ($dateEvents as $event) {
                $isAttendanceRequired[] = $event->calendar->calendar_type->is_attendance_required;
            }

            // if in $isAttendanceRequired got 1 means school is open
            if (in_array('1', $isAttendanceRequired)) {
                return false;
            } else {
                return true;
            }
        }

        // false = school is open, true = school is closed
        return false;
    }

    public function getInstitutionClosedDates($startDate, $endDate, $institutionList)
    {
        $startDate = $startDate->format('Y-m-d');
        $endDate = $endDate->format('Y-m-d');

        $institutionDateEvents = $this
            ->find()
            ->select([
                'date' => $this->aliasField('date'),
                'institution_id' => 'Calendars.institution_id',
                'is_attendance_required' => 'CalendarTypes.is_attendance_required'
            ])
            ->contain(['Calendars', 'Calendars.CalendarTypes'])
            ->where([
                $this->aliasField('date') . ' >= ' => $startDate,
                $this->aliasField('date') . ' <= ' => $endDate,
                'Calendars.institution_id IN' => $institutionList
            ])
            ->toArray();

        $allInstitutionDateEvents = $this
            ->find()
            ->select([
                'date' => $this->aliasField('date'),
                'is_attendance_required' => 'CalendarTypes.is_attendance_required'
            ])
            ->contain(['Calendars', 'Calendars.CalendarTypes'])
            ->where([
                $this->aliasField('date') . ' >= ' => $startDate,
                $this->aliasField('date') . ' <= ' => $endDate,
                'Calendars.institution_id' => '-1'
            ])
            ->toArray();

        // dates for all institutions (-1)
        $defaultDateList = [];
        foreach ($allInstitutionDateEvents as $dateEventObj) {
            $dateFormat = $dateEventObj->date->format('Y-m-d');

            // if is not set, or if is set, and the entity attendance is required
            if (!isset($defaultDateList[$dateFormat]) || $dateEventObj->is_attendance_required == 1) {
                $defaultDateList[$dateFormat] = $dateEventObj->is_attendance_required;
            }
        }

        // set the all institutions date list to all institutions
        $schoolClosedList = [];
        foreach ($institutionList as $institutionId) {
            $schoolClosedList[$institutionId] = [];
            $schoolClosedList[$institutionId] += $defaultDateList;
        }

        // dates merged with individual institutions event dates
        foreach ($institutionDateEvents as $dateEventObj) {
            $institutionId = $dateEventObj->institution_id;
            $dateFormat = $dateEventObj->date->format('Y-m-d');

            if (!isset($schoolClosedList[$institutionId][$dateFormat]) || $dateEventObj->is_attendance_required == 1) {
                $schoolClosedList[$institutionId][$dateFormat] = $dateEventObj->is_attendance_required;
            }
        }
        
        return $schoolClosedList;
    }
}
