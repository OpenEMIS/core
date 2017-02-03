<?php
namespace Competency\Model\Entity;

use Cake\ORM\Entity;

class CompetencyGradingOption extends Entity
{
    protected $_virtual = ['code_name'];

    protected function _getCodeName() {
        return $this->code . ' - ' . $this->name;
    }
}
