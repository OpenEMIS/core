<?php
namespace Assessment\Model\Entity;

use DateTimeInterface;

use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\Log\Log;

class AssessmentPeriod extends Entity
{
    protected $_virtual = ['editable'];

    protected function _getEditable()
    {
        $today = new Date();
        $dateEnabled = $this->getOriginal('date_enabled');
        $dateDisabled = $this->getOriginal('date_disabled');

        if ($dateEnabled instanceof DateTimeInterface && $dateDisabled instanceof DateTimeInterface) {
            return $today->between($dateEnabled, $dateDisabled);
        }
        return false;
    }
}
