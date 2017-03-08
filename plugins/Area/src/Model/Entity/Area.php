<?php
namespace Area\Model\Entity;

use Cake\ORM\Entity;

class Area extends Entity
{
    protected $_virtual = ['code_name'];

    protected function _getCodeName() {
        return $this->code . ' - ' . $this->name;
    }
}