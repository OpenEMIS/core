<?php
namespace Assessment\Model\Entity;

use DateTimeInterface;

use Cake\I18n\Date;
use Cake\ORM\Entity;
use Cake\Log\Log;

class AssessmentPeriod extends Entity
{
    protected $_virtual = ['editable', 'code_name'];//POCOR-6513 added code_name virtual

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

    /**
     * concatenate Assessment Period's code and name
     * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
     * Ticket No - POCOR-6513 
     */
    protected function _getCodeName() {
        return $this->code . ' - ' . $this->name;
    }
}
