<?php
namespace Staff\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;

class StaffClass extends Entity
{
	protected $_virtual = ['academic_period', 'institution', 'institution_site_section', 'education_subject', 'homeroom_teacher_name', 'male_students', 'female_students'];

	protected function _getAcademicPeriod() {
		$name = '';
		if ($this->has('institution_site_class') && $this->institution_site_class->has('academic_period_id')) {
			$data = TableRegistry::get('AcademicPeriod.AcademicPeriods')->get($this->institution_site_class->academic_period_id)->toArray();
			if (!empty($data)) {
				$name = $data['name'];
			}
		}
		return $name;
	}

	protected function _getInstitution() {
		$name = '';
		if ($this->has('institution_site_class') && $this->institution_site_class->has('institution_site_id')) {
			$data = TableRegistry::get('Institution.Institutions')->get($this->institution_site_class->institution_site_id)->toArray();
			if (!empty($data)) {
				$name = $data['name'];
			}
		}
		return $name;
	}

	protected function _getInstitutionSiteSection() {
		$name = '';
		if ($this->has('institution_site_class') && $this->institution_site_class->has('id')) {
			$InstitutionSiteSectionClasses = TableRegistry::get('Institution.InstitutionSiteSectionClasses');
			$data = $InstitutionSiteSectionClasses
			->find()
			->contain('InstitutionSiteSections')
			->where([$InstitutionSiteSectionClasses->aliasField('institution_site_class_id') => $this->institution_site_class->id])
			->first();

			if (!empty($data)) {
				if ($data->has('institution_site_section')) {
					$name = $data->institution_site_section->name;//
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
		if ($this->has('institution_site_class') && $this->institution_site_class->has('education_subject_id')) {
			$data = TableRegistry::get('Education.EducationSubjects')->get($this->institution_site_class->education_subject_id)->toArray();
			if (!empty($data)) {
				$name = $data['name'];
			}
		}
		return $name;
	}

	protected function _getMaleStudents() {
		if ($this->has('id')) {
			$count = TableRegistry::get('Institution.InstitutionSiteSectionStudents')->getMaleCountBySection($this->id);
		}
		return $count;
	}

	protected function _getFemaleStudents() {
		if ($this->has('id')) {
			$count = TableRegistry::get('Institution.InstitutionSiteSectionStudents')->getFemaleCountBySection($this->id);
		}
		return $count;
	}
}
