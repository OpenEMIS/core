<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SurveyQuestionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('SurveyTemplates', ['className' => 'Survey.SurveyTemplates']);
		$this->hasMany('SurveyQuestionChoices', ['className' => 'Survey.SurveyQuestionChoices']);
		$this->hasMany('SurveyTableColumns', ['className' => 'Survey.SurveyTableColumns']);
		$this->hasMany('SurveyTableRows', ['className' => 'Survey.SurveyTableRows']);
	}
}
