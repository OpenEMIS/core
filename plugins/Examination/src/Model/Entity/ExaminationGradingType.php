<?php
namespace Examination\Model\Entity;

use Cake\ORM\Entity;

class ExaminationGradingType extends Entity
{
	protected $_virtual = ['code_name'];

    protected function _getCodeName() {
    	return $this->code . ' - ' . $this->name;
	}
}
