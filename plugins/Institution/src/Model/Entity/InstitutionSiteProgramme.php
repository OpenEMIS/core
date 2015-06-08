<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class InstitutionSiteProgramme extends Entity {
	protected $_virtual = ['education_level'];

    protected function _getEducationLevel() {
        $name = '';
    	if ($this->has('education_programme') && $this->education_programme->has('education_cycle_id')) {
            $cycleId = $this->education_programme->education_cycle_id;
            $table = TableRegistry::get('Education.EducationCycles');
            $data = $table->findById($cycleId)
                ->contain(['EducationLevels' => ['EducationSystems']])
                ->first();
            $name = $data->education_level->education_system->name . ' - ' . $data->education_level->name;
    	}

    	return $name;
	}
}
