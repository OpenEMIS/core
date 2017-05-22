<?php
namespace Competency\Model\Entity;

use Cake\ORM\Entity;

class CompetencyPeriod extends Entity
{
    protected $_virtual = ['code_name', 'editable'];

    protected function _getCodeName()
    {
        return $this->code . ' - ' . $this->name;
    }

    protected function _getEditable()
    {
        $dateToday = date('Y-m-d');
        $dateEnabled = $this->date_enabled;
        $dateDisabled = $this->date_disabled;
        if ($dateEnabled instanceof DateTimeInterface && $dateDisabled instanceof DateTimeInterface) {
            return ($dateToday >= $dateEnabled->format('Y-m-d') && $dateToday <= $dateDisabled->format('Y-m-d')) ? 1 : 0;
        } else {
            $today = strtotime($dateToday);
            $dateEnabled = strtotime($this->date_enabled);
            $dateDisabled = strtotime($this->date_disabled);

            return ($today >= $dateEnabled && $today <= $dateDisabled) ? 1 : 0;
        }
    }
}
