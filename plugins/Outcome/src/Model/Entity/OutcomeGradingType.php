<?php
namespace Outcome\Model\Entity;

use Cake\ORM\Entity;

class OutcomeGradingType extends Entity
{
    protected $_virtual = ['code_name'];

    protected function _getCodeName()
    {
        return !empty($this->code) ? $this->code . ' - ' . $this->name : $this->name;
    }
}
