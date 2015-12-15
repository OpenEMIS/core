<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;

class StaffSectionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_sections');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);
	}

	// Academic Period	Institution	Grade	Section	Male Students	Female Students
	public function indexBeforeAction(Event $event) {
		$this->fields['section_number']['visible'] = false;
		$this->fields['institution_shift_id']['visible'] = false;

		$this->ControllerAction->addField('male_students', []);
		$this->ControllerAction->addField('female_students', []);
		
		$order = 0;
		$this->ControllerAction->setFieldOrder('academic_period_id', $order++);
		$this->ControllerAction->setFieldOrder('institution_id', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
		$this->ControllerAction->setFieldOrder('male_students', $order++);
		$this->ControllerAction->setFieldOrder('female_students', $order++);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);
		
		if (array_key_exists('view', $buttons)) {
			$institutionId = $entity->institution->id;
			$url = [
				'plugin' => 'Institution', 
				'controller' => 'Institutions', 
				'action' => 'Sections',
				'view', $entity->id,
				'institution_id' => $institutionId,
			];
			$buttons['view']['url'] = $url;
		}
		return $buttons;
	}
	
	private function setupTabElements() {
		$tabElements = $this->controller->getCareerTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', 'Sections');
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
