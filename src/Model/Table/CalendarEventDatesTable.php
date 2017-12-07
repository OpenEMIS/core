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
}
