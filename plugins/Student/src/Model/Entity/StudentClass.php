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
    	if ($this->has('user') && $this->user->has('name')) {
    		$name = $this->user->name;
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

	// protected function _getEducationGrade() {
 //    	$name = '';
 //    	// pr($this);

 //    	if ($this->has('institution_site_section_id')) {
 //    		$InstitutionSiteSectionGrades = TableRegistry::get('Institution.InstitutionSiteSectionGrades');
 //    		$data = $InstitutionSiteSectionGrades
 //    			->find()
 //    			->where([$InstitutionSiteSectionGrades->aliasField('institution_site_section_id') => $this->institution_site_section_id, $InstitutionSiteSectionGrades->aliasField('institution_site_section_id') => 1])
 //    			->contain(['EducationGrades'=>['EducationProgrammes'=>['EducationCycles']]])
 //    		;
 //    		$result = '';
 //    		foreach ($data->toArray() as $key => $value) {
 //    			$cycleName = $value->education_grade->education_programme->education_cycle->name;
	//     		$programmeName = $value->education_grade->education_programme->name;
	//     		$gradeName = $value->education_grade->name;
	//     		$result .= sprintf('%s - %s - %s', $cycleName, $programmeName, $gradeName).'<br>';
 //    		}
 //    		return $result;
 //    	}



 //    	return $name;
	// }

}
