<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class InstitutionSitePosition extends Entity
{
	protected $_virtual = ['name'];
	
    protected function _getName() {
    	return $this->position_no;
	}
}
