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
            $table = TableRegistry::get('Institution.InstitutionClassSubjects');
            $value = $table
                        ->find()
                        ->where([$table->aliasField('institution_class_id') => $this->id])
                        ->count();
        }
        return $value;
    }
}
