<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;

use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class StaffClassesTable extends ControllerActionTable {
	use MessagesTrait;

	public function initialize(array $config) {
		$this->table('institution_classes');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts']);

		$this->toggle('add', false);
		$this->toggle('edit', false);
		$this->toggle('remove', false);
	}

	// Academic Period	Institution	Grade	Class	Male Students	Female Students
	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		$this->fields['class_number']['visible'] = false;
		$this->fields['institution_shift_id']['visible'] = false;

		$this->field('male_students', []);
		$this->field('female_students', []);
		
		$this->setFieldOrder([
			'academic_period_id',
			'institution_id',
			'name',
			'male_students',
			'female_students'
		]);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain([
			'AcademicPeriods',
			'Institutions',
		]);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		if (array_key_exists('view', $buttons)) {
			$institutionId = $entity->institution->id;
			$url = [
				'plugin' => 'Institution', 
				'controller' => 'Institutions', 
				'action' => 'Classes',
				'view', $entity->id,
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
		$this->controller->set('selectedAction', 'Classes');
	}

}
