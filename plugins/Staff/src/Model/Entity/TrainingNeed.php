<?php
namespace Staff\Model\Entity;

use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

class TrainingNeed extends Entity
{
	protected $_virtual = ['code_name'];

	protected function _getCodeName() {
		$codeName = '';
		if ($this->course_id == 0) {
			$codeName = $this->course_code.' - '.$this->course_name;
		} else {
			if ($this->has('course')) {
				$codeName = $this->course->code.' - '.$this->course->name;
			} else if ($this->has('_matchingData')) {
				if (array_key_exists('Courses', $this->_matchingData)) {
					$codeName = $this->_matchingData['Courses']->code.' - '.$this->_matchingData['Courses']->name;
				}
			} else {
				$TrainingCourses = TableRegistry::get('Training.TrainingCourses');
				$courseEntity = $TrainingCourses
					->find()
					->where([$TrainingCourses->aliasField('id') => $this->course_id])
					->first();
				$codeName = $courseEntity->code.' - '.$courseEntity->name;
			}
		}

		return $codeName;
	}
}
