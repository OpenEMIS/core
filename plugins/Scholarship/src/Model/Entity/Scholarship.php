<?php
namespace Scholarship\Model\Entity;

use Cake\ORM\Entity;

class Scholarship extends Entity
{
    protected $_virtual = ['code_name'];

    protected function _getCodeName() {
        return $this->code . ' - ' . $this->name;
    }
}
