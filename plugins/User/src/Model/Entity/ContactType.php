<?php
namespace User\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class ContactType extends Entity
{
	protected $_virtual = ['full_contact_type_name'];
	
    protected function _getFullContactTypeName() {
    	$name = $this->name;

    	if ($this->has('contact_option') && $this->contact_option->has('name')) {
    		$name = __($this->contact_option->name) . ' - ' . __($name);
    	} else {
    		$table = TableRegistry::get('User.ContactOptions');
    		$contactOptionId = $this->contact_option_id;
    		$name = __($table->get($contactOptionId)->name) . ' - ' . __($name);
    	}
    	return $name;
	}
}
