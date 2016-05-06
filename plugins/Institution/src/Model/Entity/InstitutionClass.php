<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class InstitutionClass extends Entity
{
	protected $_virtual = ['male_students', 'female_students', 'subjects'];
	
    protected function _getMaleStudents() {
        $gender_id = 1; // male
        $table = TableRegistry::get('Institution.InstitutionClassStudents');
        $count = $table
                    ->find()
                    ->contain('Users')
                    ->where(['Users.gender_id' => $gender_id])
                    ->where([$table->aliasField('institution_class_id') => $this->id])
                    ->count()
        ;
        return $count;
	}

    protected function _getFemaleStudents() {
        $gender_id = 2; // female
        $table = TableRegistry::get('Institution.InstitutionClassStudents');
        $count = $table
                    ->find()
                    ->contain('Users')
                    ->where(['Users.gender_id' => $gender_id])
                    ->where([$table->aliasField('institution_class_id') => $this->id])
                    ->count();
        return $count;
    }

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
        // return '<a href="/#">'. $value .'</a>';
    }

    protected function _getStaffName() {
       return (!empty($this->staff) || (!is_null($this->staff))) ? $this->staff->name_with_id : ''; 
    }
}
