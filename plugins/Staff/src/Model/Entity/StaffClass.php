<?php
namespace Staff\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class StaffClass extends Entity
{
	protected $_virtual = ['academic_period', 'institution', 'institution_section', 'education_subject', 'homeroom_teacher_name', 'male_students', 'female_students'];

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

	protected function _getInstitutionSection() {
		$name = '';
		if ($this->has('institution_class') && $this->institution_class->has('id')) {
			$InstitutionSectionClasses = TableRegistry::get('Institution.InstitutionSectionClasses');
			$data = $InstitutionSectionClasses
			->find()
			->contain('InstitutionSections')
			->where([$InstitutionSectionClasses->aliasField('institution_class_id') => $this->institution_class->id])
			->first();

			if (!empty($data)) {
				if ($data->has('institution_section')) {
					$name = $data->institution_section->name;
				}
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
		if ($this->has('institution_class') && $this->institution_class->has('education_subject_id')) {
			$data = TableRegistry::get('Education.EducationSubjects')->get($this->institution_class->education_subject_id)->toArray();
			if (!empty($data)) {
				$name = $data['name'];
			}
		}
		return $name;
	}

	protected function _getMaleStudents() {
		if ($this->has('id')) {
			$count = TableRegistry::get('Institution.InstitutionSectionStudents')->getMaleCountBySection($this->id);
		}
		return $count;
	}

	protected function _getFemaleStudents() {
		if ($this->has('id')) {
			$count = TableRegistry::get('Institution.InstitutionSectionStudents')->getFemaleCountBySection($this->id);
		}
		return $count;
	}
}
