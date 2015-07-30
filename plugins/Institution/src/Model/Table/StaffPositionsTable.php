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
        $this->entityClass('Institution.InstitutionSiteStaff');
      	$this->addBehavior('Year', ['end_date' => 'end_year']);
        $this->belongsTo('Users', 		 ['className' => 'User.Users', 							'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 			'foreignKey' => 'institution_site_id']);
		$this->belongsTo('Positions', 	 ['className' => 'Institution.InstitutionSitePositions','foreignKey' => 'institution_site_position_id']);
		$this->belongsTo('StaffTypes', 	 ['className' => 'FieldOption.StaffTypes', 				'foreignKey' => 'staff_type_id']);
		$this->belongsTo('StaffStatuses',['className' => 'FieldOption.StaffStatuses', 			'foreignKey' => 'staff_status_id']);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('position', ['type' => 'readonly', 'attr' => ['value' => $entity->position->staff_position_title->name]]);
		$this->ControllerAction->field('security_user_id', ['type' => 'readonly', 'attr' => ['value' => $entity->user->name_with_id]]);
		$start_date = (is_a($entity->start_date, 'Cake\I18n\Time')) ? $this->formatDate($entity->start_date) : date('F d, Y', strtotime($entity->start_date));
		$this->ControllerAction->field('start_date', ['type' => 'readonly', 'attr' => ['value' => $start_date]]);
	}

	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain(['Users','Positions.StaffPositionTitles']);
	}

	public function editBeforePatch(Event $event, Entity $entity, ArrayObject $options) {
		unset($options[$this->alias()]['position']);
	}	

	public function editBeforeAction(Event $event) {
		$session = $this->request->session();
		$institutionSiteId = $session->read('Institutions.id');
		foreach ($this->fields as $key => $value) {
			$this->fields[$key]['visible'] = false;
		}	

		//$this->fields['staff_name']['visible'] = true;
		$this->ControllerAction->field('position', ['visible' => true]);
		$this->ControllerAction->field('FTE', ['visible' => true]);
		$this->ControllerAction->field('start_date', ['visible' => true]);
		$this->ControllerAction->field('end_date', ['visible' => true]);
		$this->ControllerAction->field('staff_type_id', ['visible' => true]);
		$this->ControllerAction->field('staff_status_id', ['visible' => true]);
		
		//make some visible
		$this->ControllerAction->field('security_user_id', ['visible' => true]);
		$this->ControllerAction->field('institution_site_id', ['visible' => true]);
		$this->ControllerAction->field('institution_site_position_id', ['visible' => true]);

		$this->ControllerAction->field('institution_site_id', ['type' => 'hidden']);
		$this->ControllerAction->field('institution_site_position_id', ['type' => 'hidden']);

		$this->ControllerAction->field('FTE', ['type' => 'readonly']);
		$this->ControllerAction->field('end_date', ['type' => 'date']);
		$this->ControllerAction->field('staff_type_id', ['type' => 'select']);
		$this->ControllerAction->field('staff_status_id', ['type' => 'select']);
		
		$this->ControllerAction->setFieldOrder([
			'security_user_id', 'institution_site_id', 'institution_site_position_id', 'security_user_id', 'position', 'FTE', 'start_date', 'end_date', 'staff_type_id', 'staff_status_id'
			
			]);
	}

	public function viewBeforeAction(Event $event) {
		$this->ControllerAction->field('FTE', ['type' => 'string']);
		$this->ControllerAction->field('end_date', ['visible' => true]);
		$this->ControllerAction->field('end_year', ['visible' => true]);
		$this->ControllerAction->field('FTE', ['visible' => true]);
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

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {   
    	if($action == 'view') {
    		$staffPositionId = $toolbarButtons['edit']['url']['1'];
    		$staffPosition = TableRegistry::get('Institution.InstitutionSiteStaff')->get($staffPositionId);
    		if(!empty($staffPosition) && !empty($toolbarButtons['back'])) {
    			$toolbarButtons['back']['url']['action'] = 'Positions';
	    		$toolbarButtons['back']['url']['0'] = 'view';
	    		$toolbarButtons['back']['url']['1'] = $staffPosition->institution_site_position_id;
    		}
    	} else if($action == 'edit') {
    		$staffPositionId = $toolbarButtons['back']['url']['1'];
    		$staffPosition = TableRegistry::get('Institution.InstitutionSiteStaff')->get($staffPositionId);
    		if(!empty($staffPosition) && !empty($toolbarButtons['list'])) {
    			$toolbarButtons['list']['url']['action'] = 'Positions';
	    		$toolbarButtons['list']['url']['0'] = 'view';
	    		$toolbarButtons['list']['url']['1'] = $staffPosition->institution_site_position_id;
    		}
    	} 
	}

	public function validationDefault(Validator $validator) {
		return $validator
			->allowEmpty('end_date')
	 	        ->add('end_date', 'ruleCompareDateReverse', [
			            'rule' => ['compareDateReverse', 'start_date', false]
		    	    ])
	        ;
		return $validator;
	}
}
