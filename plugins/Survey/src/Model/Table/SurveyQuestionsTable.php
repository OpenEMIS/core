<?php
namespace Survey\Model\Table;

use ArrayObject;
use CustomField\Model\Table\CustomFieldsTable;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Utility\Text;

class SurveyQuestionsTable extends CustomFieldsTable {
	protected $fieldTypeFormat = ['OpenEMIS', 'OpenEMIS_Institution'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('CustomFieldOptions', ['className' => 'Survey.SurveyQuestionChoices', 'foreignKey' => 'survey_question_id', 'dependent' => true]);
		$this->hasMany('CustomTableColumns', ['className' => 'Survey.SurveyTableColumns', 'foreignKey' => 'survey_question_id', 'dependent' => true]);
		$this->hasMany('CustomTableRows', ['className' => 'Survey.SurveyTableRows', 'foreignKey' => 'survey_question_id', 'dependent' => true]);
		$this->hasMany('CustomFieldValues', ['className' => 'Institution.InstitutionSurveyAnswers', 'dependent' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'Institution.InstitutionSurveyTableCells', 'dependent' => true]);
		$this->hasMany('SurveyQuestionParams', ['className' => 'Survey.SurveyQuestionParams', 'foreignKey' => 'survey_question_id', 'dependent' => true]);
		$this->belongsToMany('CustomForms', [
			'className' => 'Survey.SurveyForms',
			'joinTable' => 'survey_forms_questions',
			'foreignKey' => 'survey_question_id',
			'targetForeignKey' => 'survey_form_id',
			'through' => 'Survey.SurveyFormsQuestions',
			'dependent' => true
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator
	    	->add('code', [
	    		'unique' => [
			        'rule' => ['validateUnique'],
			        'provider' => 'table',
			        'message' => 'This code already exists in the system'
			    ]
		    ]);

		return $validator;
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder(['code', 'name', 'field_type', 'is_mandatory', 'is_unique']);
	}

	public function addBeforeAction(Event $event) {
		$this->ControllerAction->field('code');
	}

	public function onUpdateFieldCode(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			if (!$request->is('post')) {
				$textValue = substr(Text::uuid(), 0, 8);
				$attr['attr']['value'] = $textValue;
			}
			return $attr;
		}
	}

}
