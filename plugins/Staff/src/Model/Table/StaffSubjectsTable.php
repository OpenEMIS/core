<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;

use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class StaffSubjectsTable extends ControllerActionTable {
	use MessagesTrait;

	public function initialize(array $config) {
		$this->table('institution_subject_staff');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
		$this->hasMany('InstitutionSubjectStudents', ['className' => 'Institution.InstitutionSubjectStudents', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->toggle('add', false);
		$this->toggle('edit', false);
		$this->toggle('remove', false);
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$this->fields['status']['visible'] = false;

		$this->field('academic_period', []);
		$this->field('institution', []);
		$this->field('institution_class', []);
		$this->field('educationSubject', []);
		$this->field('male_students', []);
		$this->field('female_students', []);
		
		$this->setFieldOrder([
			'academic_period',
			'institution',
			'institution_class',
			'institution_subject_id',
			'educationSubject',
			'male_students',
			'female_students'
		]);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain([
			'InstitutionSubjects'
		]);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		if (array_key_exists('view', $buttons)) {
			$institutionId = $entity->institution_subject->institution_id;
			$url = [
				'plugin' => 'Institution', 
				'controller' => 'Institutions', 
				'action' => 'Subjects',
				'view', $entity->institution_subject->id,
				'institution_id' => $institutionId,
			];
			$buttons['view']['url'] = $url;
		}
		return $buttons;
	}

	public function indexAfterAction(Event $event, ResultSet $data, ArrayObject $extra) {
		$options = ['type' => 'staff'];
		$tabElements = $this->controller->getCareerTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Subjects');
	
	}
}
