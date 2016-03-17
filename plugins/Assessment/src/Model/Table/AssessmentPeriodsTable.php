<?php
namespace Assessment\Model\Table;

use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\MessagesTrait;
use App\Model\Table\ControllerActionTable;

class AssessmentPeriodsTable extends ControllerActionTable {
	use MessagesTrait;
	use OptionsTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);

		$this->fields['id']['type'] = 'hidden';
		$this->fields['assessment_id']['type'] = 'hidden';
		$this->fields['weights']['type'] = 'string';
		$this->fields['start_date']['label'] = false;
		$this->fields['end_date']['label'] = false;
		$this->fields['date_enabled']['label'] = false;
		$this->fields['date_disabled']['label'] = false;
	}

	public function getFormFields($action = 'edit') {
		if ($action=='edit' || $action=='add') {
			return ['code'=>'', 'name'=>'', 'start_date'=>'', 'end_date'=>'', 'date_enabled'=>'', 'date_disabled'=>'', 'weights'=>'', 'assessment_id'=>''];
		} else {
			return ['code'=>'', 'name'=>'', 'start_date'=>'', 'end_date'=>'', 'date_enabled'=>'', 'date_disabled'=>'', 'weights'=>'', 'assessment_id'=>''];
		}
	}

}