<?php
namespace Student\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class StudentClass extends Entity
{
	protected $_virtual = ['academic_period', 'institution', 'education_subject', 'homeroom_teacher_name'];

    protected function _getAcademicPeriod() {
    	$name = '';
    	if ($this->has('institution_section') && $this->institution_section->has('academic_period_id')) {
    		$data = TableRegistry::get('AcademicPeriod.AcademicPeriods')->get($this->institution_section->academic_period_id)->toArray();
    		if (!empty($data)) {
    			$name = $data['name'];
    		}
    	}
    	return $name;
	}

	protected function _getInstitution() {
    	$name = '';
    	if ($this->has('institution_section') && $this->institution_section->has('institution_id')) {
    		$data = TableRegistry::get('Institution.Institutions')->get($this->institution_section->institution_id)->toArray();
    		if (!empty($data)) {
    			$name = $data['name'];
    		}
    	}
    	return $name;
	}

	protected function _getHomeroomTeacherName() {
    	$name = '';
        $institution_section_id = $this->institution_section_id;
        if($this->has('institution_section')) { // && $this->institution_section->has('staff')){
            $InstitutionSections = TableRegistry::get('Institution.InstitutionSections');
            $InstitutionSection = $InstitutionSections
                    ->find()
                    ->contain(['Staff'])
                    ->where(['InstitutionSections.id' => $institution_section_id])
                    ->first();  
   
             if(!empty($InstitutionSection->staff))
                $name = $InstitutionSection->staff->name;
        }
    	return $name;
	}
	protected function _getEducationSubject() {
		$name = '';
		if ($this->has('institution_class') && $this->institution_class->has('education_subject_id')) {
			$data = TableRegistry::get('Education.EducationSubjects')->get($this->institution_class->education_subject_id)->toArray();
			if (!empty($data)) {
    			$name = $data['name'];
    		}
		}
		return $name;
	}

}
