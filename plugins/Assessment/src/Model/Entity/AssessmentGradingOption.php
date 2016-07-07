<?php
namespace Assessment\Model\Entity;

use Cake\ORM\Entity;

class AssessmentGradingOption extends Entity
{
	protected $_virtual = ['code_name'];

    protected function _getCodeName() {
        if (!empty($this->code)) {
            return $this->code . ' - ' . $this->name;
        } else {
            return $this->name;
        }
	}
}
