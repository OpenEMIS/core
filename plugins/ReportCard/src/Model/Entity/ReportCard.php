<?php
namespace ReportCard\Model\Entity;

use Cake\ORM\Entity;

class ReportCard extends Entity
{
    protected $_virtual = ['code_name'];

    protected function _getCodeName() {
        return $this->code . ' - ' . $this->name;
    }
}
