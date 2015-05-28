<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SurveyTableColumnsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('SurveyQuestions', ['className' => 'Survey.SurveyQuestions']);
	}
}
