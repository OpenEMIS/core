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

		$this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
		$this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions']);
		
		$this->addBehavior('Excel');
		$this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions('Institutions');
		return $attr;
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
}
