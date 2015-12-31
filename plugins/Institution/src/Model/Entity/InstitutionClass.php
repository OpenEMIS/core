<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class InstitutionClass extends Entity
{
	protected $_virtual = ['male_students', 'female_students', 
    'teachers', 
    'education_subject_code', 'section_name'];
	
    protected function _getMaleStudents() {
        $gender_id = 1; // male
        $table = TableRegistry::get('Institution.InstitutionClassStudents');
        $count = $table
                    ->find()
                    ->contain('Users')
                    ->where(['Users.gender_id' => $gender_id])
                    ->where([$table->aliasField('institution_class_id') => $this->id])
                    ->where([$table->aliasField('status') .' > 0'])
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
                    ->where([$table->aliasField('status') .' > 0'])
                    ->count();
        return $count;
    }

    // protected function _getTeachers() {
    //     pr($this);die;
        // $value = '';
        // $table = TableRegistry::get('Institution.InstitutionClassStaff');
        // $rawList = $table
        //             ->find()
        //             ->contain('Users')
        //             ->where([$table->aliasField('institution_class_id') => $this->id])
        //             ->where([$table->aliasField('status') .' > 0'])
        //             ->toArray();
        // $list = [];
        // foreach ($rawList as $staff) {
        //     $list[$staff->user->id] = $staff->user->name;
        // }
        // if (!empty($list)) {
        //     $value = implode(', ', $list);
        // }
        // return $value;
    // }

    protected function _getEducationSubjectCode() {
        $value = '';
        if ($this->has('education_subject')) {
            $value = $this->education_subject->code;
        } else {
            $table = TableRegistry::get('Education.EducationSubjects');
            $id = $this->education_subject_id;
            $value = $table->get($id)->code;            
        }
        return $value;
    }

    // protected function _getSectionName() {
        // $value = 'mmm';
    //     if ($this->has('institution_section_classes')) {
    //         if ($this->has('institution_section')) {
    //             $value = $this->education_subject->code;
    //         } else {
    //             $table = TableRegistry::get('Education.EducationSubjects');
    //             $id = $this->education_subject_id;
    //             $value = $table->get($id)->code;            
    //         }
    //     } else {
    //         $table = TableRegistry::get('Education.EducationSubjects');
    //         $id = $this->education_subject_id;
    //         $value = $table->get($id)->code;            
    //     }
        // return $value;
    // }
}
