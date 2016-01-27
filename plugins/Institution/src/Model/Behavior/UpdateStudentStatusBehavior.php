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
		$originalEntity = $entity->extractOriginal($entity->visibleProperties());
		$InstitutionSectionStudentsTable = TableRegistry::get('Institution.InstitutionSectionStudents');
		
		$institutionSectionStudentRecord = $InstitutionSectionStudentsTable->find()
			->matching('InstitutionSections')
			->where([
				'InstitutionSections.institution_id' => $originalEntity['institution_id'],
				'InstitutionSections.academic_period_id' => $originalEntity['academic_period_id'],
				$InstitutionSectionStudentsTable->aliasField('education_grade_id') => $originalEntity['education_grade_id'],
				$InstitutionSectionStudentsTable->aliasField('student_id') => $originalEntity['student_id'],
			])->first();

		if (!empty($institutionSectionStudentRecord)) {
			$patchData = [
				'student_status_id' => $entity->student_status_id
			];
			$institutionSectionStudentRecord = $InstitutionSectionStudentsTable->patchEntity($institutionSectionStudentRecord, $patchData, ['validation' => false]);
			$InstitutionSectionStudentsTable->save($institutionSectionStudentRecord);
		}
	}
}
