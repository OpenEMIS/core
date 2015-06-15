<?php
namespace Education\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class EducationGrade extends Entity
{
    protected $_virtual = ['programme_grade_name', 'programme_order'];

    protected function _getProgrammeGradeName() {
        $name = '';
        if ($this->has('education_programme')) {
            $name = $this->education_programme->name . ' - ' . $this->name;
        } else {
            $table = TableRegistry::get('Education.EducationProgrammes');
            $id = $this->education_programme_id;
            $name = $table->get($id)->name . ' - ' . $this->name;            
        }
        return $name;
    }

    protected function _getProgrammeOrder() {
        $name = '';
        if ($this->has('education_programme')) {
            $name = $this->education_programme->order;
        } else {
            $table = TableRegistry::get('Education.EducationProgrammes');
            $id = $this->education_programme_id;
            $name = $table->get($id)->order . ' - ' . $this->name;            
        }
        return $name;
    }

}
