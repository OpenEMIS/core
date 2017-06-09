<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class InstitutionPositionsTable extends AppTable  {
	use OptionsTrait;

	public function initialize(array $config) {
		$this->table('institution_positions');
		parent::initialize($config);

		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
        $this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
		
		$this->addBehavior('Excel');
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Report.InstitutionSecurity');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
	{
		$query
			->contain(['Institutions.Areas', 'Institutions.AreaAdministratives'])
			->select(['area_code' => 'Areas.code', 'area_name' => 'Areas.name', 'area_administrative_code' => 'AreaAdministratives.code', 'area_administrative_name' => 'AreaAdministratives.name']);
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions('Institutions');
		return $attr;
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) 
	{
		$requestData = json_decode($settings['process']['params']);

		$cloneFields = $fields->getArrayCopy();
		$newFields = [];
		foreach ($cloneFields as $key => $value) {
			$newFields[] = $value;
			if ($value['field'] == 'institution_id') {
				$newFields[] = [
					'key' => 'Areas.code',
					'field' => 'area_code',
					'type' => 'string',
					'label' => __('Area Code')
				];

				$newFields[] = [
					'key' => 'Areas.name',
					'field' => 'area_name',
					'type' => 'string',
					'label' => __('Area')
				];

				$newFields[] = [
					'key' => 'AreaAdministratives.code',
					'field' => 'area_administrative_code',
					'type' => 'string',
					'label' => __('Area Administrative Code')
				];

				$newFields[] = [
					'key' => 'AreaAdministratives.name',
					'field' => 'area_administrative_name',
					'type' => 'string',
					'label' => __('Area Administrative')
				];
			}
		}

		$fields->exchangeArray($newFields);

	}

	public function onExcelGetStatus(Event $event, Entity $entity) {
		$options = $this->getSelectOptions('general.active');
		return $options[$entity->status];
	}

	public function onExcelGetStaffPositionTitleId(Event $event, Entity $entity) {
   		$options = $this->getSelectOptions('Staff.position_types');
		if ($entity->has('staff_position_title')) {
	        $type = array_key_exists($entity->staff_position_title->type, $options) ? $options[$entity->staff_position_title->type] : '';
	        if (empty($type)) {
	    		return $entity->staff_position_title->name;
   		    } else {
				return $entity->staff_position_title->name .' - '. $type;
   			}
   		} else {
   			$this->log($entity->name . ' has no staff_position_title...', 'debug');
   		}
	}

	public function onExcelGetInstitutionId(Event $event, Entity $entity) {
		return $entity->institution->code_name;
	}

	public function onExcelGetIsHomeroom(Event $event, Entity $entity) {
		$options = $this->getSelectOptions('general.yesno');
		return $options[$entity->is_homeroom];
	}
}
