<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;

class StudentClassesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_class_students');

		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionSiteClasses', ['className' => 'Institution.InstitutionSiteClasses']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['status']['visible'] = false;

		$this->ControllerAction->addField('academic_period', []);
		$this->ControllerAction->addField('institution', []);
		$this->ControllerAction->addField('educationSubject', []);
		
		$order = 0;
		$this->ControllerAction->setFieldOrder('academic_period', $order++);
		$this->ControllerAction->setFieldOrder('institution', $order++);
		$this->ControllerAction->setFieldOrder('institution_site_section_id', $order++);
		$this->ControllerAction->setFieldOrder('institution_site_class_id', $order++);
		$this->ControllerAction->setFieldOrder('educationSubject', $order++);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);
		
		if (array_key_exists('view', $buttons)) {
			$institutionId = $entity->institution_site_section->institution_site_id;
			$url = [
				'plugin' => 'Institution', 
				'controller' => 'Institutions', 
				'action' => 'Classes',
				'view', $entity->institution_site_section->id,
				'institution_id' => $institutionId,
			];
			$buttons['view']['url'] = $url;
		}
		return $buttons;
	}

	private function setupTabElements() {
		$tabElements = $this->controller->getAcademicTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Classes');
	}

	public function indexAfterAction(Event $event, $data) {
		if ($this->controller->name == 'Students') {
			$this->setupTabElements();
		}
	}
}
