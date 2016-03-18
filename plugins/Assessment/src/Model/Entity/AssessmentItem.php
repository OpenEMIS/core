<?php
namespace Assessment\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class AssessmentItem extends Entity
{
	protected $_virtual = [];

	public function getEducationSubjectIdName($educationSubjectId) {
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
