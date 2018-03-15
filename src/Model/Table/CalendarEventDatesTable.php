<?php
namespace App\Model\Table;

use Cake\Utility\Hash;

class CalendarEventDatesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('calendar_event_dates');
        parent::initialize($config);

        $this->belongsTo('Calendars', ['className' => 'Calendars', 'foreignKey' => 'calendar_event_id']);
    }

    public function isSchoolClosed($date)
    {
        $dateEvents = $this->find()
            ->contain(['Calendars', 'Calendars.CalendarTypes'])
            ->where([
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
        //// REFACTOR
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
                'institution_id' => 'Calendars.institution_id',
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

            if (!isset($defaultDateList[$dateFormat])) {
                $defaultDateList[$dateFormat] = $dateEventObj->is_attendance_required;
            } else {
                if ($dateEventObj->is_attendance_required == 1) {
                    $defaultDateList[$dateFormat] = $dateEventObj->is_attendance_required;
                }
            }
        }

        $schoolClosedList = [];
        foreach ($institutionList as $institutionId) {
            $schoolClosedList[$institutionId] = [];
            $schoolClosedList[$institutionId] += $defaultDateList;
        }

        foreach ($institutionDateEvents as $dateEventObj) {
            $institutionId = $dateEventObj->institution_id;
            $dateFormat = $dateEventObj->date->format('Y-m-d');

            if (!isset($schoolClosedList[$institutionId][$dateFormat])) {
                $schoolClosedList[$institutionId][$dateFormat] = $dateEventObj->is_attendance_required;
            } else {
                if ($dateEventObj->is_attendance_required == 1) {
                    $schoolClosedList[$institutionId][$dateFormat] = $dateEventObj->is_attendance_required;
                }
            }
        }

        // foreach ($schoolClosedList as $schoolList) {
        //     foreach ($schoolList as $date => $required) {

        //     }
        // }

        // $schoolClosedList = Hash::remove($schoolClosedList, '{n}[{*}=1]');

        // pr('jaja');
        // pr($schoolClosedList);
        // die;
        
        return $schoolClosedList;
    }
}
