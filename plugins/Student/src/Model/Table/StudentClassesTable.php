<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;

use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class StudentClassesTable extends ControllerActionTable {
	use MessagesTrait;

	public function initialize(array $config) {
		$this->table('institution_class_students');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('StudentStatuses',	['className' => 'Student.StudentStatuses']);

		$this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades', 'dependent' => true, 'cascadeCallbacks' => true]);

		if ($this->hasBehavior('ControllerAction')) {
			$this->toggle('add', false);
			$this->toggle('edit', false);
			$this->toggle('remove', false);
			$this->toggle('search', false);
			$this->toggle('reorder', false);
		}
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$this->fields['education_grade_id']['visible'] = false;

		$this->field('academic_period', []);
		$this->field('institution', []);
		$this->field('education_grade', []);
		$this->field('homeroom_teacher_name', []);

		$order = 0;
		$this->setFieldOrder('academic_period', $order++);
		$this->setFieldOrder('institution', $order++);
		$this->setFieldOrder('education_grade', $order++);
		$this->setFieldOrder('institution_class_id', $order++);
		$this->setFieldOrder('homeroom_teacher_name', $order++);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain([
			'InstitutionClasses',
			'StudentStatuses'
		]);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		if (array_key_exists('view', $buttons)) {
			$institutionId = $entity->institution_class->institution_id;
			$url = [
				'plugin' => 'Institution', 
				'controller' => 'Institutions', 
				'action' => 'Classes',
				'view', $entity->institution_class->id,
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
		$this->controller->set('selectedAction', 'Classes');
	}

}
