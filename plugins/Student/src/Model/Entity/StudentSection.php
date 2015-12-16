<?php
namespace Student\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;
use Cake\Log\Log;

class StudentSection extends Entity
{
	protected $_virtual = ['academic_period', 'institution', 'homeroom_teacher_name', 'education_grade'];
	
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
        $teacherId = $this->institution_section->security_user_id;
        if (!empty($teacherId)) {
            $Users = TableRegistry::get('Security.Users');
            try {
                $user = $Users->get($teacherId);
                $name = $user->name;
            } catch (InvalidPrimaryKeyException $ex) {
                Log::write('error', $ex->getMessage());
            }
        }
    	return $name;
	}

	protected function _getEducationGrade() {
    	$name = '';

    	if ($this->has('institution_section_id')) {
    		$InstitutionSectionGrades = TableRegistry::get('Institution.InstitutionSectionGrades');
    		$data = $InstitutionSectionGrades
    			->find()
    			->where([$InstitutionSectionGrades->aliasField('institution_section_id') => $this->institution_section_id, $InstitutionSectionGrades->aliasField('institution_section_id') => 1])
    			->contain(['EducationGrades'=>['EducationProgrammes'=>['EducationCycles']]])
    		;
    		$result = '';
    		foreach ($data->toArray() as $key => $value) {
    			$cycleName = $value->education_grade->education_programme->education_cycle->name;
	    		$programmeName = $value->education_grade->education_programme->name;
	    		$gradeName = $value->education_grade->name;
	    		$result .= sprintf('%s - %s - %s', $cycleName, $programmeName, $gradeName).'<br>';
    		}
    		return $result;
    	}

    	return $name;
	}

}
