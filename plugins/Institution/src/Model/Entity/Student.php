<?php
namespace Institution\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class Student extends Entity
{
	// protected $_virtual = ['male_students', 'female_students', 'teachers', 'education_subject_code', 'class_name'];
    protected $_virtual = ['institution_student_status', 'education_grade_student_status'];

    protected function _getInstitutionStudentStatus() {
        $institutionName = '';
        if ($this->has('institution')) {
            $institutionName = $this->institution->name;
        }

        $studentStatus = '';
        if ($this->has('student_status')) {
            $studentStatus = $this->student_status->name;
        }

        return $institutionName . ' - ' . __($studentStatus);
    }

    protected function _getEducationGradeStudentStatus() {
        $gradeName = '';
        if ($this->has('education_grade')) {
            $gradeName = $this->education_grade->name;
        }

        $studentStatus = '';
        if ($this->has('student_status')) {
            $studentStatus = $this->student_status->name;
        }

        return $gradeName . ' - ' . __($studentStatus);
    }
}
