<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;

class StaffClassesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_class_staff');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('InstitutionSiteClasses', ['className' => 'Institution.InstitutionSiteClasses']);
		$this->hasMany('InstitutionSiteClassStudents', ['className' => 'Institution.InstitutionSiteClassStudents', 'dependent' => true, 'cascadeCallbacks' => true]);
	}


	public function indexBeforeAction(Event $event) {
		$this->fields['status']['visible'] = false;

		$this->ControllerAction->addField('academic_period', []);
		$this->ControllerAction->addField('institution', []);
		$this->ControllerAction->addField('institution_site_section', []);
		$this->ControllerAction->addField('educationSubject', []);
		$this->ControllerAction->addField('male_students', []);
		$this->ControllerAction->addField('female_students', []);
		
		$order = 0;
		$this->ControllerAction->setFieldOrder('academic_period', $order++);
		$this->ControllerAction->setFieldOrder('institution', $order++);
		$this->ControllerAction->setFieldOrder('institution_site_section', $order++);
		$this->ControllerAction->setFieldOrder('institution_site_class_id', $order++);
		$this->ControllerAction->setFieldOrder('educationSubject', $order++);
		$this->ControllerAction->setFieldOrder('male_students', $order++);
		$this->ControllerAction->setFieldOrder('female_students', $order++);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);
		
		if (array_key_exists('view', $buttons)) {
			$institutionId = $entity->institution_site_class->institution_site->id;
			$url = [
				'plugin' => 'Institution', 
				'controller' => 'Institutions', 
				'action' => 'Classes',
				'view', $entity->institution_site_class->id,
				'institution_id' => $institutionId,
			];
			$buttons['view']['url'] = $url;
		}
		return $buttons;
	}

	private function setupTabElements() {
		$tabElements = $this->controller->getCareerTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Classes');
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
