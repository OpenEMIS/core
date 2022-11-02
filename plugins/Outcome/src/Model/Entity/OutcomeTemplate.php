<?php
namespace Outcome\Model\Entity;

use Cake\ORM\Entity;

class OutcomeTemplate extends Entity
{
    protected $_virtual = ['code_name'];

    protected function _getCodeName()
    {
        return $this->code . ' - ' . $this->name;
    }
}
