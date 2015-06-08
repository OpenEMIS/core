<?php
namespace Education\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class EducationLevel extends Entity
{
	protected $_virtual = ['system_level_name'];

    protected function _getSystemLevelName() {
    	$name = $this->name;
    	if ($this->has('education_system') && $this->education_system->has('name')) {
    		$name = $this->education_system->name . ' - ' . $name;
    	} else {
    		$table = TableRegistry::get('Education.EducationSystems');
    		$systemId = $this->education_system_id;
    		$name = $table->get($systemId)->name . ' - ' . $name;
    	}

    	return $name;
	}
}