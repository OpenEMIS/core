<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class InstitutionProgrammesTable extends AppTable  {
	use OptionsTrait;

	public function initialize(array $config) {
		$this->table('institution_grades');
		parent::initialize($config);

		$this->belongsTo('EducationGrades', 	['className' => 'Education.EducationGrades']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions']);
		
		$this->addBehavior('Excel', ['excludes' => ['start_year', 'end_year', 'institution_programme_id']]);
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

	public function onExcelGetStartDate(Event $event, Entity $entity) {
		return $this->formatDate($entity->start_date);
	}

	public function onExcelGetEndDate(Event $event, Entity $entity) {
		if (!empty($entity->end_date)) {
			return $this->formatDate($entity->end_date);
		}
	}
}
