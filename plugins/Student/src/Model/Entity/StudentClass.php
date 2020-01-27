<?php
namespace Student\Model\Entity;

use Cake\Log\Log;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Datasource\Exception\InvalidPrimaryKeyException;

class StudentClass extends Entity
{
	protected $_virtual = ['academic_period', 'institution', 'homeroom_teacher_name', 'education_grade'];
	
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
        $teacherId = $this->institution_class->staff_id;
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

    	if ($this->has('institution_class_id')) {
    		$InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
    		$data = $InstitutionClassGrades
    			->find()
    			->where([
                    $InstitutionClassGrades->aliasField('institution_class_id') => $this->institution_class_id
                ])
    			->contain(['EducationGrades'=>['EducationProgrammes'=>['EducationCycles']]])
    		;
    		$result = '';
    		foreach ($data->toArray() as $key => $value) {
                    $cycleName = $value->education_grade->education_programme->education_cycle->name;
                    $programmeName = $value->education_grade->education_programme->name;
                    $gradeName = $value->education_grade->name;
                    //$result .= sprintf('%s - %s - %s', $cycleName, $programmeName, $gradeName).'<br>';
                    $result .= $gradeName.'<br>';
    		}
    		return $result;
    	}

    	return $name;
	}
        
        protected function _getCurrentClass() {
    	$name = '';

    	if ($this->has('institution_class_id')) {
    		$InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
    		$data = $InstitutionClassGrades
    			->find()
    			->where([
                    $InstitutionClassGrades->aliasField('institution_class_id') => $this->institution_class_id
                ])
    		->contain(['InstitutionClasses','EducationGrades'=>['EducationProgrammes'=>['EducationCycles']]])->toArray()
    		;
                
    		$result = '';
    		foreach ($data as $key => $value) {
                    $currentClass = $value->institution_class->name;
                    $result .= $currentClass;
    		}
    		return $result;
    	}

    	return $name;
	}

}
