<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;

use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class PositionsTable extends ControllerActionTable {
	use MessagesTrait;

	public function initialize(array $config) {
		$this->table('institution_staff');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('StaffTypes', ['className' => 'FieldOption.StaffTypes']);
		$this->belongsTo('StaffStatuses', ['className' => 'FieldOption.StaffStatuses']);
		$this->belongsTo('InstitutionPositions', ['className' => 'Institution.InstitutionPositions']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
<<<<<<< HEAD

		if ($this->hasBehavior('ControllerAction')) {
			$this->toggle('add', false);
			$this->toggle('edit', false);
			$this->toggle('remove', false);
			$this->toggle('search', false);
			$this->toggle('reorder', false);
		}
=======
		$this->belongsTo('SecurityGroupUsers', ['className' => 'Security.SecurityGroupUsers']);
>>>>>>> bfbe8ced58205497c300c05a25ba9c3aca0c4732
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		$this->fields['FTE']['visible'] = false;
		$this->fields['staff_type_id']['visible'] = false;
		$this->fields['security_group_user_id']['visible'] = false;

		$this->setFieldOrder([
			'institution_id', 
			'institution_position_id', 
			'start_date',
			'end_date',
			'staff_status_id'
		]);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$query->contain([
			'Institutions',
			'InstitutionPositions',
			'StaffStatuses'
		]);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		$buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
		if (array_key_exists('view', $buttons)) {
			$institutionId = $entity->institution->id;
			$url = [
				'plugin' => 'Institution', 
				'controller' => 'Institutions', 
				'action' => 'Staff',
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
		$this->controller->set('selectedAction', $this->alias());
	}

}