<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;

use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class StudentSubjectsTable extends ControllerActionTable {
	use MessagesTrait;

	public function initialize(array $config) {
		$this->table('institution_subject_students');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionSubjects', ['className' => 'Institution.InstitutionSubjects']);
		$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);

		if ($this->hasBehavior('ControllerAction')) {
			$this->toggle('add', false);
			$this->toggle('edit', false);
			$this->toggle('remove', false);
			$this->toggle('search', false);
			$this->toggle('reorder', false);
		}
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['status']['visible'] = false;

		$this->field('academic_period', []);
		$this->field('institution', []);
		$this->field('educationSubject', []);
		
		$order = 0;
		$this->setFieldOrder('academic_period', $order++);
		$this->setFieldOrder('institution', $order++);
		$this->setFieldOrder('institution_class_id', $order++);
		$this->setFieldOrder('institution_subject_id', $order++);
		$this->setFieldOrder('educationSubject', $order++);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain([
			'InstitutionClasses',
			'InstitutionSubjects'
		]);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		if (array_key_exists('view', $buttons)) {
			$institutionId = $entity->institution_class->institution_id;
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
		$options = ['type' => 'student'];
		$tabElements = $this->controller->getAcademicTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$alias = 'Subjects';
		$this->controller->set('selectedAction', $alias);
	}

}
