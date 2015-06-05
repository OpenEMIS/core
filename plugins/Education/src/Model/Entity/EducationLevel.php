<?php
namespace Education\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class EducationLevel extends Entity
{
	protected $_virtual = ['system_level_name'];

	// public function __construct() {
	// 	parent::__construct([],[]);
	// }

    protected function _getSystemLevelName() {

    	$class_methods = get_class_methods($this);

		foreach ($class_methods as $method_name) {
		    echo "$method_name<br>";
		}


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
