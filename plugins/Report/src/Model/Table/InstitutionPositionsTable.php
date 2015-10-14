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
		$this->table('institution_site_positions');
		parent::initialize($config);

		$this->belongsTo('StaffPositionTitles', ['className' => 'Institution.StaffPositionTitles']);
		$this->belongsTo('StaffPositionGrades', ['className' => 'Institution.StaffPositionGrades']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		
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

	public function onExcelGetType(Event $event, Entity $entity) {
		$options = $this->getSelectOptions('Staff.position_types');
		return $options[$entity->type];
	}
}
