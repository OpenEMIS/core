<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class InstitutionSubject extends Entity
{
    protected $_virtual = ['teachers',
    'education_subject_code', 'class_name'];
    
    // protected function _getTeachers() {
    //     pr($this);die;
    // $value = '';
    // $table = TableRegistry::get('Institution.InstitutionSubjectStaff');
    // $rawList = $table
    //             ->find()
    //             ->contain('Users')
    //             ->where([$table->aliasField('institution_subject_id') => $this->id])
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

    protected function _getEducationSubjectCode()
    {
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

    protected function _getClassName()
    {
        if ($this->has('classes')) {
            foreach ($this->classes as $class) {
                $className[] = $class['name'];
            }
            sort($className);
            return implode(', ', $className);
        }
        return  '-';
    }

    // protected function _getClassName() {
    //     $value = 'mmm';
    //     if ($this->has('institution_class_subjects')) {
    //         if ($this->has('institution_class')) {
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
    //     return $value;
    // }
}
