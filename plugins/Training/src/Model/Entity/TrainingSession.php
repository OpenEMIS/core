<?php
namespace Training\Model\Entity;

use Cake\ORM\Entity;

class TrainingSession extends Entity
{
	protected $_virtual = ['code_name'];

    protected function _getCodeName() {
    	return $this->code . ' - ' . $this->name;
	}
}
