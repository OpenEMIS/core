<?php
namespace Schedule\Model\Entity;

use Cake\ORM\Entity;

class ScheduleTimeslots extends Entity
{
    protected $_virtual = ['start_time', 'end_time'];

    protected function _getStartTime()
    {
        return 'Start Time field';
    }

    protected function _getEndTime()
    {
        return 'End Time field';
    }
}
