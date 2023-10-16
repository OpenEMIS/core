<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class InstitutionAssessment extends Entity
{
	protected $_virtual = ['subjects'];

    protected function _getSubjects() {
        $value = 0;
        if ($this->has('institution_class_subjects')) {
            $value = count($this->institution_class_subjects);
        } else {
            $grade = $this->education_grade_id;
            $class = $this->institution_class_id;
            $table = TableRegistry::get('Education.EducationGradesSubjects');
            $value = $table
                       ->find()
                        ->where([$table->aliasField('education_grade_id') => $grade])
                        ->count();
        }
        return $value;
    }
}
