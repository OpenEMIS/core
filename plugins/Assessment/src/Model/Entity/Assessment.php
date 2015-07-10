<?php
namespace Assessment\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class Assessment extends Entity
{
	protected $_virtual = ['code_name'];

    protected function _getCodeName() {
    	return $this->code . ' - ' . $this->name;
	}
}
