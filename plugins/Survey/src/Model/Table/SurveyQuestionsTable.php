<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SurveyQuestionsTable extends AppTable {
	public function initialize(array $config) {
		$this->belongsTo('SurveyTemplates', ['className' => 'Survey.SurveyTemplates']);
		$this->belongsTo('ModifiedUser', [
			'className' => 'SecurityUsers',
			'fields' => array('ModifiedUser.first_name', 'ModifiedUser.last_name'),
			'foreignKey' => 'modified_user_id'
		]);
		$this->belongsTo('CreatedUser', [
			'className' => 'SecurityUsers',
			'fields' => array('CreatedUser.first_name', 'CreatedUser.last_name'),
			'foreignKey' => 'created_user_id'
		]);
		$this->hasMany('SurveyQuestionChoices', ['className' => 'Survey.SurveyQuestionChoices']);
		$this->hasMany('SurveyTableColumns', ['className' => 'Survey.SurveyTableColumns']);
		$this->hasMany('SurveyTableRows', ['className' => 'Survey.SurveyTableRows']);
	}
}
