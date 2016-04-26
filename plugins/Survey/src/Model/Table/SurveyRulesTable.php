<?php
namespace Survey\Model\Table;

use Cake\ORM\Entity;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Validation\Validator;
use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

use App\Model\Table\ControllerActionTable;

class SurveyRulesTable extends ControllerActionTable 
{
	public function initialize(array $config) 
	{
		$this->table('survey_rules');
		parent::initialize($config);
		$this->belongsTo('SurveyForms', 					['className' => 'Survey.SurveyForms', 'foreignKey' => 'survey_form_id']);
		$this->belongsTo('SurveyQuestions', 				['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
		$this->belongsTo('DependentQuestions',				['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'dependent_question_id']);
	}

	private function addSetupControl(ArrayObject $extra, $data = []) 
	{
		// $extra['elements']['controls'] = ['name' => 'Education.controls', 'data' => $data, 'order' => 2];
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) 
	{
		$surveyFormOptions = $this->SurveyForms
			->find('list')
			->toArray();
		$extra['elements']['controls'] = ['name' => 'Survey.survey_rules_controls', 'data' => $surveyFormOptions, 'order' => 2];
	}
}
