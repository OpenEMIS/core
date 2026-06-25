<?php
namespace User\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class Contact extends Entity
{
	protected $_virtual = ['description'];
	
    protected function _getDescription() {
    	$name = '';
    	if ($this->has('contact_type') && $this->contact_type->has('full_contact_type_name')) {
    		$name = $this->contact_type->full_contact_type_name;
    	}
    	return $name;
	}
}
