<?php
namespace Assessment\Model\Entity;

use Cake\I18n\Date;
use Cake\ORM\Entity;
use DateTimeInterface;

class AssessmentPeriod extends Entity
{
    protected $_virtual = ['editable'];

    protected function _getEditable()
    {
        $dateToday = date('Y-m-d');
        $dateEnabled = $this->date_enabled;
        $dateDisabled = $this->date_disabled;

        if ($dateEnabled instanceof DateTimeInterface && $dateDisabled instanceof DateTimeInterface) {
            return ($dateToday >= $dateEnabled->format('Y-m-d') && $dateToday <= $dateDisabled->format('Y-m-d')) ? 1 : 0;
        } else {
            $today = new Date();
            $dateEnabled = date('d/m/Y', strtotime($this->date_enabled));
            $dateDisabled = date('d/m/Y', strtotime($this->date_disabled));

            $dateEnabled = Date::createFromFormat("d/m/Y", $dateEnabled);
            $dateDisabled = Date::createFromFormat("d/m/Y", $dateDisabled);

            return $today->between($dateEnabled, $dateDisabled);
        }
    }
}
