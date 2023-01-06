<?php
namespace Assessment\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class AssessmentItem extends Entity
{
	protected $_virtual = ['education_subject_name'];

    protected function _getEducationSubjectName() {
		$educationSubjectId = $this->education_subject_id;
		if (!empty($educationSubjectId) && !is_null($educationSubjectId)) {
			$EducationSubjects = TableRegistry::get('Education.EducationSubjects');
			if ($EducationSubjects->exists([$EducationSubjects->primaryKey() => $educationSubjectId])) {
				$subject = $EducationSubjects->get($educationSubjectId);
				return $subject->code . ' - ' . $subject->name;
			}
		}
		return '';
	}
}
