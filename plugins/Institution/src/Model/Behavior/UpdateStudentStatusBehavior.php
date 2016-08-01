<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;

class UpdateStudentStatusBehavior extends Behavior {

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
			'Model.afterSave' => ['callable' => 'afterSave', 'priority' => 20],
		];
		$events = array_merge($events, $newEvent);
		return $events;
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$InstitutionClassStudentsTable = TableRegistry::get('Institution.InstitutionClassStudents');
		$institutionClassStudentRecord = $InstitutionClassStudentsTable->find()
			->matching('InstitutionClasses')
			->where([
				'InstitutionClasses.institution_id' => $entity->institution_id,
				'InstitutionClasses.academic_period_id' => $entity->academic_period_id,
				$InstitutionClassStudentsTable->aliasField('education_grade_id') => $entity->education_grade_id,
				$InstitutionClassStudentsTable->aliasField('student_id') => $entity->student_id,
			])->first();

		if (!empty($institutionClassStudentRecord)) {
			$institutionClassStudentRecord->student_status_id = $entity->student_status_id;
			$InstitutionClassStudentsTable->save($institutionClassStudentRecord);
		}
	}
}
