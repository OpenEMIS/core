<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;

class Institution extends Entity
{
	protected $_virtual = ['code_name'];

    protected function _getCodeName() {
    	return $this->code . ' - ' . $this->name;
	}
}
