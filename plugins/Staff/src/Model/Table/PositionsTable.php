<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class PositionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_staff');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('StaffTypes', ['className' => 'FieldOption.StaffTypes']);
		$this->belongsTo('StaffStatuses', ['className' => 'FieldOption.StaffStatuses']);
		$this->belongsTo('InstitutionSitePositions', ['className' => 'Institution.InstitutionSitePositions']);
		$this->belongsTo('InstitutionSites', ['className' => 'Institution.InstitutionSites']);
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['start_year']['visible'] = false;
		$this->fields['end_year']['visible'] = false;
		$this->fields['FTE']['visible'] = false;
		$this->fields['staff_type_id']['visible'] = false;

		$order = 0;
		$this->ControllerAction->setFieldOrder('institution_site_id', $order++);
		$this->ControllerAction->setFieldOrder('institution_site_position_id', $order++);
		$this->ControllerAction->setFieldOrder('start_date', $order++);
		$this->ControllerAction->setFieldOrder('end_date', $order++);
		$this->ControllerAction->setFieldOrder('staff_status_id', $order++);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);
		
		if (array_key_exists('view', $buttons)) {
			$institutionId = $entity->institution_site->id;
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
}