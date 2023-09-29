<?php
namespace StaffAppraisal\Model\Entity;

use Cake\ORM\Entity;

class AppraisalForm extends Entity
{

    protected $_virtual = ['code_name'];

    protected function _getCodeName()
    {
        return $this->code . ' - ' . $this->name;
    }
}
