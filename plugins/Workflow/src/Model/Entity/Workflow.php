<?php
namespace Workflow\Model\Entity;

use Cake\ORM\Entity;

class Workflow extends Entity
{
	protected $_virtual = ['code_name'];

    protected function _getCodeName() {
    	return $this->code . ' - ' . $this->name;
	}
}
