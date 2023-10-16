<?php
namespace Examination\Model\Entity;

use Cake\ORM\Entity;

class Examination extends Entity
{
	protected $_virtual = ['code_name'];

    protected function _getCodeName() {
    	return $this->code . ' - ' . $this->name;
	}
}
