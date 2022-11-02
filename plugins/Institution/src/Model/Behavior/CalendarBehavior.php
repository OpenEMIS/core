<?php
namespace Institution\Model\Behavior;

use ArrayObject;
use DatePeriod;
use DateInterval;
use Cake\ORM\Behavior;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class CalendarBehavior extends Behavior
{
    public function isSchoolClosed($date, $institutionId = null)
    {
        $CalendarEventDates = TableRegistry::get('CalendarEventDates');

        return $CalendarEventDates->isSchoolClosed($date, $institutionId);
    }

    public function getInstitutionClosedDates($startDate, $endDate, $institutionList)
    {
        $CalendarEventDates = TableRegistry::get('CalendarEventDates');
        return $CalendarEventDates->getInstitutionClosedDates($startDate, $endDate, $institutionList);
    }

    public function getDateFromPeriodWeekDay($selectedPeriod, $selectedWeek, $day)
    {
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $weeks = $AcademicPeriod->getAttendanceWeeks($selectedPeriod);
        $workingDaysOfWeek = $AcademicPeriod->getWorkingDaysOfWeek();

        $workingDaysOfWeekId = array_search($day, $workingDaysOfWeek);

        // get date period between 2 date
        $weekStartDate = $weeks[$selectedWeek][0];
        $weekEndDate = $weeks[$selectedWeek][1];
        $weekEndDate = $weekEndDate->modify('+1 day');
        $interval = new DateInterval('P1D');
        $datePeriod = new DatePeriod($weekStartDate, $interval, $weekEndDate);
        // end of get date period

        foreach ($datePeriod as $key => $date) {
            if ($key == $workingDaysOfWeekId) {
                return $date;
            }
        }
    }
}
