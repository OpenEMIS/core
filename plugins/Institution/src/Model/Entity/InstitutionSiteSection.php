<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class InstitutionSiteSection extends Entity
{
	protected $_virtual = ['male_students', 'female_students', 'classes'];
	
    protected function _getMaleStudents() {
        $gender_id = 1; // male
        $table = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
        $count = $table
                    ->find()
                    ->contain('Users')
                    ->where(['Users.gender_id' => $gender_id])
                    ->where([$table->aliasField('institution_site_section_id') => $this->id])
                    ->count()
        ;
        return $count;
	}

    protected function _getFemaleStudents() {
        $gender_id = 2; // female
        $table = TableRegistry::get('Institution.InstitutionSiteSectionStudents');
        $count = $table
                    ->find()
                    ->contain('Users')
                    ->where(['Users.gender_id' => $gender_id])
                    ->where([$table->aliasField('institution_site_section_id') => $this->id])
                    ->count()
        ;
        return $count;
    }

    protected function _getClasses() {
        $value = 0;
        if ($this->has('institution_site_section_classes')) {
            $value = count($this->institution_site_section_classes);
        } else {
            $table = TableRegistry::get('Institution.InstitutionSiteSectionClasses');
            $value = $table
                    ->find()
                    ->where([$table->aliasField('institution_site_section_id') => $this->id])
                    ->count();
        }
        return $value;
        // return '<a href="/#">'. $value .'</a>';
    }
}
