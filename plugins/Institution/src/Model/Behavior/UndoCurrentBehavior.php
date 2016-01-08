<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Institution\Model\Behavior\UndoBehavior;

class UndoCurrentBehavior extends UndoBehavior {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Undo.'.'get'.$this->undoAction.'Students'] = 'onGet'.$this->undoAction.'Students';
		return $events;
	}

	public function onGetCurrentStudents(Event $event, ArrayObject $settings, ArrayObject $students) {
		$students = $this->Students
			->find()
    		->matching('Users')
    		->matching('EducationGrades')
    		->where([
    			$this->Students->aliasField('institution_id') => $settings['institution_id'],
    			$this->Students->aliasField('academic_period_id') =>  $settings['academic_period_id'],
    			$this->Students->aliasField('education_grade_id') => $settings['education_grade_id'],
    			$this->Students->aliasField('student_status_id') => $settings['student_status_id']
    		])
    		->toArray();
	}
}
