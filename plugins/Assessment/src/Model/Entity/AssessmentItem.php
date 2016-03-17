<?php
namespace Assessment\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class AssessmentItem extends Entity
{
	protected $_virtual = [];

 //    protected function _getCodeName() {
 //    	return $this->code . ' - ' . $this->name;
	// }

	protected function _getEducationSubjectId($educationSubjectId) {
		if (!empty($educationSubjectId) && !is_null($educationSubjectId)) {
			$EducationSubjects = TableRegistry::get('Education.EducationSubjects');
			$subject = $EducationSubjects->get($educationSubjectId);
			// pr('chak: '.$educationSubjectId);pr($subject);pr($this);die;
			// return 'chak';
			return $subject->code . ' - ' . $subject->name;
		} else {
			return '';
		}
	}
}
