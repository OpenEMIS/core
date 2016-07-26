<?php
namespace Infrastructure\Model\Entity;

use Cake\ORM\Entity;

class InfrastructureLevel extends Entity
{
	protected $_virtual = ['code_name'];

    protected function _getCodeName() {
    	return $this->code . ' - ' . $this->name;
	}
}
