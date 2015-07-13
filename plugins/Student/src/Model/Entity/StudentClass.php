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
    	if ($this->has('institution_site_section') && $this->institution_site_section->has('academic_period_id')) {
    		$data = TableRegistry::get('AcademicPeriod.AcademicPeriods')->get($this->institution_site_section->academic_period_id)->toArray();
    		if (!empty($data)) {
    			$name = $data['name'];
    		}
    	}
    	return $name;
	}

	protected function _getInstitution() {
    	$name = '';
    	if ($this->has('institution_site_section') && $this->institution_site_section->has('institution_site_id')) {
    		$data = TableRegistry::get('Institution.Institutions')->get($this->institution_site_section->institution_site_id)->toArray();
    		if (!empty($data)) {
    			$name = $data['name'];
    		}
    	}
    	return $name;
	}

	protected function _getHomeroomTeacherName() {
    	$name = '';
        $institution_site_section_id = $this->institution_site_section_id;
        if($this->has('institution_site_section')) { // && $this->institution_site_section->has('staff')){
            $InstitutionSiteSections = TableRegistry::get('Institution.InstitutionSiteSections');
            $InstitutionSiteSection = $InstitutionSiteSections
                    ->find()
                    ->contain(['Staff'])
                    ->where(['InstitutionSiteSections.id' => $institution_site_section_id])
                    ->first();  
   
             if(!empty($InstitutionSiteSection->staff))
                $name = $InstitutionSiteSection->staff->name;
        }
    	return $name;
	}
	protected function _getEducationSubject() {
		$name = '';
		if ($this->has('institution_site_class') && $this->institution_site_class->has('education_subject_id')) {
			$data = TableRegistry::get('Education.EducationSubjects')->get($this->institution_site_class->education_subject_id)->toArray();
			if (!empty($data)) {
    			$name = $data['name'];
    		}
		}
		return $name;
	}

}
