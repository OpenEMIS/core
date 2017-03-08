<?php
namespace Education\Model\Entity;

use Cake\ORM\Entity;

class EducationSubject extends Entity
{
    protected $_virtual = ['code_name'];

    protected function _getCodeName() {
        return $this->code . ' - ' . $this->name;
    }
}