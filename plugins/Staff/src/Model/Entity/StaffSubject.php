<?php
namespace Staff\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class StaffSubject extends Entity
{
	protected $_virtual = ['academic_period', 'institution_class', 'education_subject', 'homeroom_teacher_name', 'male_students', 'female_students'];

	protected function _getAcademicPeriod() {
		$name = '';
		if ($this->has('institution_subject') && $this->institution_subject->has('academic_period_id')) {
			$data = TableRegistry::get('AcademicPeriod.AcademicPeriods')->get($this->institution_subject->academic_period_id)->toArray();
			if (!empty($data)) {
				$name = $data['name'];
			}
		}
		return $name;
	}

	protected function _getInstitutionClass() {
		$name = '';
		if ($this->has('institution_subject') && $this->institution_subject->has('id')) {
			$InstitutionClassSubjects = TableRegistry::get('Institution.InstitutionClassSubjects');
			$data = $InstitutionClassSubjects
			->find()
			->contain('InstitutionClasses')
			->where([$InstitutionClassSubjects->aliasField('institution_subject_id') => $this->institution_subject->id])
			->first();

			if (!empty($data)) {
				if ($data->has('institution_class')) {
					$name = $data->institution_class->name;
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
		if ($this->has('institution_subject') && $this->institution_subject->has('education_subject_id')) {
			$data = TableRegistry::get('Education.EducationSubjects')->get($this->institution_subject->education_subject_id)->toArray();
			if (!empty($data)) {
				$name = $data['name'];
			}
		}
		return $name;
	}

	protected function _getMaleStudents() {
		$count = 0;
		if ($this->has('institution_subject_id')) {
			$count = TableRegistry::get('Institution.InstitutionSubjectStudents')->getMaleCountBySubject($this->institution_subject_id);
		} else if ($this->has('id')) {
			$count = TableRegistry::get('Institution.InstitutionSubjectStudents')->getMaleCountBySubject($this->id);
		}
		return $count;
	}

	protected function _getFemaleStudents() {
		$count = 0;
		if ($this->has('institution_subject_id')) {
			$count = TableRegistry::get('Institution.InstitutionSubjectStudents')->getFemaleCountBySubject($this->institution_subject_id);
		} else if ($this->has('id')) {
			$count = TableRegistry::get('Institution.InstitutionSubjectStudents')->getFemaleCountBySubject($this->id);
		}
		return $count;
	}
}
