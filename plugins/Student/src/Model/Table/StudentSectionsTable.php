<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;

class StudentSectionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_section_students');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionSections', ['className' => 'Institution.InstitutionSections']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

		$this->hasMany('InstitutionSectionGrade', ['className' => 'Institution.InstitutionSectionGrade', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['education_grade_id']['visible'] = false;
		$this->fields['status']['visible'] = false;

		$this->ControllerAction->addField('academic_period', []);
		$this->ControllerAction->addField('institution', []);
		$this->ControllerAction->addField('education_grade', []);
		$this->ControllerAction->addField('homeroom_teacher_name', []);

		$order = 0;
		$this->ControllerAction->setFieldOrder('academic_period', $order++);
		$this->ControllerAction->setFieldOrder('institution', $order++);
		$this->ControllerAction->setFieldOrder('education_grade', $order++);
		$this->ControllerAction->setFieldOrder('institution_section_id', $order++);
		$this->ControllerAction->setFieldOrder('homeroom_teacher_name', $order++);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);

		if (array_key_exists('view', $buttons)) {
			$institutionId = $entity->institution_section->institution_id;
			$url = [
				'plugin' => 'Institution', 
				'controller' => 'Institutions', 
				'action' => 'Sections',
				'view', $entity->institution_section->id,
				'institution_id' => $institutionId,
			];
			$buttons['view']['url'] = $url;
		}
		return $buttons;
	}

	private function setupTabElements() {
		$options['type'] = 'student';
		$tabElements = $this->controller->getAcademicTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$alias = 'Sections';
		if ($this->controller->name == 'Directories') {
			$alias = 'Classes';	
		}
		$this->controller->set('selectedAction', $alias);
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
