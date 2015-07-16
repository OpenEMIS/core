<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Network\Request;


class StaffPositionsTable extends AppTable {
	//public $useTable = false;
	public function initialize(array $config) {
		$this->table('institution_site_staff');
        parent::initialize($config);

        $this->belongsTo('Users', 		 ['className' => 'User.Users', 							'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 			'foreignKey' => 'institution_site_id']);
		$this->belongsTo('Positions', 	 ['className' => 'Institution.InstitutionSitePositions','foreignKey' => 'institution_site_position_id']);
		$this->belongsTo('StaffTypes', 	 ['className' => 'FieldOption.StaffTypes', 				'foreignKey' => 'staff_type_id']);
		$this->belongsTo('StaffStatuses',['className' => 'FieldOption.StaffStatuses', 			'foreignKey' => 'staff_status_id']);
	}

	// public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
	// 	parent::beforeSave($event, $entity, $options);
	// }	

	public function editBeforeAction(Event $event) {
		$session = $this->request->session();
		$institutionSiteId = $session->read('Institutions.id');
		foreach ($this->fields as $key => $value) {
			$this->fields[$key]['visible'] = false;
		}	

		$this->fields['staff_name']['visible'] = true;
		$this->fields['position']['visible'] = true;
		$this->fields['FTE']['visible'] = true;
		$this->fields['start_date_formatted']['visible'] = true;
		$this->fields['end_date']['visible'] = true;
		$this->fields['staff_type_id']['visible'] = true;
		$this->fields['staff_status_id']['visible'] = true;

		$this->ControllerAction->field('staff_name', ['type' => 'readonly']);
		$this->ControllerAction->field('position', ['type' => 'readonly']);
		$this->ControllerAction->field('FTE', ['type' => 'readonly']);
		$this->ControllerAction->field('start_date_formatted', ['type' => 'readonly']);
		$this->ControllerAction->field('end_date', ['type' => 'date']);
		$this->ControllerAction->field('staff_type_id', ['type' => 'select']);
		$this->ControllerAction->field('staff_status_id', ['type' => 'select']);
		
		$this->ControllerAction->setFieldOrder([
			'staff_name', 'position', 'FTE', 'start_date_formatted', 'end_date', 'staff_type_id', 'staff_status_id'
			
			]);
	}

	public function onUpdateFieldStaffTypeId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->StaffTypes->getList();
		if (empty($attr['options'])){
			$this->_table->ControllerAction->Alert->warning('Institution.StaffPositions.staffTypeId');
		}
		
		return $attr;
	}

	public function onUpdateFieldStaffStatusId(Event $event, array $attr, $action, $request) {
		$attr['type'] = 'select';
		$attr['options'] = $this->StaffStatuses->getList();
		if (empty($attr['options'])){
			$this->_table->ControllerAction->Alert->warning('Institution.StaffPositions.staffStatusId');
		}
		
		return $attr;
	}
}
