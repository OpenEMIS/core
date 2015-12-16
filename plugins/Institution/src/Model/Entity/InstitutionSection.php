<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class InstitutionSection extends Entity
{
	protected $_virtual = ['male_students', 'female_students', 'classes'];
	
    protected function _getMaleStudents() {
        $gender_id = 1; // male
        $table = TableRegistry::get('Institution.InstitutionSectionStudents');
        $count = $table
                    ->find()
                    ->contain('Users')
                    ->where(['Users.gender_id' => $gender_id])
                    ->where([$table->aliasField('institution_section_id') => $this->id])
                    ->where([$table->aliasField('status') => 1])
                    ->count()
        ;
        return $count;
	}

    protected function _getFemaleStudents() {
        $gender_id = 2; // female
        $table = TableRegistry::get('Institution.InstitutionSectionStudents');
        $count = $table
                    ->find()
                    ->contain('Users')
                    ->where(['Users.gender_id' => $gender_id])
                    ->where([$table->aliasField('institution_section_id') => $this->id])
                    ->where([$table->aliasField('status') => 1])
                    ->count();
        return $count;
    }

    protected function _getClasses() {
        $value = 0;
        if ($this->has('institution_section_classes')) {
            $value = count($this->institution_section_classes);
        } else {
            $table = TableRegistry::get('Institution.InstitutionSectionClasses');
            $value = $table
                    ->find()
                    ->where([$table->aliasField('institution_section_id') => $this->id])
                    ->count();
        }
        return $value;
        // return '<a href="/#">'. $value .'</a>';
    }

    protected function _getStaffName() {
       return (!empty($this->staff) || (!is_null($this->staff))) ? $this->staff->name_with_id : ''; 
    }
}
