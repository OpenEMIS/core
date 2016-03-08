<?php
namespace Student\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class StudentSubject extends Entity
{
	protected $_virtual = ['academic_period', 'institution', 'education_subject', 'homeroom_teacher_name'];

    protected function _getAcademicPeriod() {
    	$name = '';
    	if ($this->has('institution_class') && $this->institution_class->has('academic_period_id')) {
    		$data = TableRegistry::get('AcademicPeriod.AcademicPeriods')->get($this->institution_class->academic_period_id)->toArray();
    		if (!empty($data)) {
    			$name = $data['name'];
    		}
    	}
    	return $name;
	}

	protected function _getInstitution() {
    	$name = '';
    	if ($this->has('institution_class') && $this->institution_class->has('institution_id')) {
    		$data = TableRegistry::get('Institution.Institutions')->get($this->institution_class->institution_id)->toArray();
    		if (!empty($data)) {
    			$name = $data['name'];
    		}
    	}
    	return $name;
	}

	protected function _getHomeroomTeacherName() {
    	$name = '';
        $institution_class_id = $this->institution_class_id;
        if($this->has('institution_class')) { // && $this->institution_class->has('staff')){
            $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
            $InstitutionClass = $InstitutionClasses
                    ->find()
                    ->contain(['Staff'])
                    ->where(['InstitutionClasses.id' => $institution_class_id])
                    ->first();  
   
             if(!empty($InstitutionClass->staff))
                $name = $InstitutionClass->staff->name;
        }
    	return $name;
	}
	protected function _getEducationSubject() {
		$name = '';
		if ($this->has('institution_subject') && $this->institution_subject->has('education_subject_id')) {
			$data = TableRegistry::get('Education.EducationSubjects')->get($this->institution_subject->education_subject_id)->toArray();
			if (!empty($data)) {
    			$name = $data['name'];
    		}
		}
		return $name;
	}

}
