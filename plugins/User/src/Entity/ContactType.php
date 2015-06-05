<?php
namespace User\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class ContactType extends Entity
{
	protected $_virtual = ['full_contact_type_name'];
	
    protected function _getFullContactTypeName() {
    	$name = $this->name;

    	// if ($this->has('education_system') && $this->education_system->has('name')) {
    	// 	$name = $this->education_system->name . ' - ' . $name;
    	// } else {
    	// 	$table = TableRegistry::get('Education.EducationSystems');
    	// 	$systemId = $this->education_system_id;
    	// 	$name = $table->get($systemId)->name . ' - ' . $name;
    	// }

    	$name = 'entity working';
    	return $name;
	}
}
