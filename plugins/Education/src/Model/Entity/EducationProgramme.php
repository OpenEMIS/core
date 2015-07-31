<?php
namespace Education\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class EducationProgramme extends Entity
{
    protected $_virtual = ['cycle_programme_name'];

    protected function _getCycleProgrammeName() {
    	$name = $this->name;
    	if ($this->has('education_cycle') && $this->education_cycle->has('name')) {
    		$name = $this->education_cycle->name . ' - ' . $name;
    	} else {
    		$table = TableRegistry::get('Education.EducationCycles');
    		$cycleId = $this->education_cycle_id;
    		$name = $table->get($cycleId)->name . ' - ' . $name;
    	}

    	return $name;
	}
}
