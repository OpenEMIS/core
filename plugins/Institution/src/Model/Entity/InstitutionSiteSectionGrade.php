<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class InstitutionSiteSectionGrade extends Entity
{
	protected $_virtual = ['name'];
	
    protected function _getName() {
        $value = '';
        if ($this->has('education_grade')) {
            $value = $this->education_grade->name;
        } else {
            $table = TableRegistry::get('Education.EducationGrades');
            $id = $this->education_grade_id;
            $value = $table->get($id)->name;            
        }
    	return $value;
	}
}
