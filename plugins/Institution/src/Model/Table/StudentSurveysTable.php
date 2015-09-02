<?php
namespace Institution\Model\Table;

// use ArrayObject;
use Cake\ORM\Entity;
// use Cake\ORM\Query;
// use Cake\ORM\TableRegistry;
// use Cake\Network\Request;
// use Cake\Utility\Inflector;
// use Cake\Validation\Validator;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class StudentSurveysTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_student_surveys');
		parent::initialize($config);
		
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
		// $this->addBehavior('CustomField.Record', [
		// 	'moduleKey' => null,
		// 	'fieldKey' => 'survey_question_id',
		// 	'tableColumnKey' => 'survey_table_column_id',
		// 	'tableRowKey' => 'survey_table_row_id',
		// 	'formKey' => 'survey_form_id',
		// 	// 'filterKey' => 'custom_filter_id',
		// 	'formFieldClass' => ['className' => 'Survey.SurveyFormsQuestions'],
		// 	// 'formFilterClass' => ['className' => 'InstitutionCustomField.InstitutionCustomFormsFilters'],
		// 	'recordKey' => 'institution_site_survey_id',
		// 	'fieldValueClass' => ['className' => 'Institution.InstitutionSurveyAnswers', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true],
		// 	'tableCellClass' => ['className' => 'Institution.InstitutionSurveyTableCells', 'foreignKey' => 'institution_site_survey_id', 'dependent' => true, 'cascadeCallbacks' => true]
		// ]);
	}

	public function indexBeforeAction(Event $event) {
		$this->setupTabElements();
	}

	public function afterAction(Event $event) {
		// $indexElements = $this->ControllerAction->getVar('indexElements');
		$indexElements = [];
		$this->controller->set('indexElements', $indexElements);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	private function setupTabElements($entity=null) {
		$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];
		$id = $this->request->query['id'];
		$student_id = $this->request->query['student_id'];
		
		$tabElements = [
			'Students' => [
				'text' => __('Academic'),
				'url' => array_merge($url, ['action' => 'Students', 'view', $id])
			],
			'StudentUser' => [
				'text' => __('General'),
				'url' => array_merge($url, ['action' => 'StudentUser', 'view', $student_id, 'id' => $id])
			],
			'StudentSurveys' => ['text' => __('Survey')]
		];

		if ($this->action == 'index') {
			$tabElements['StudentSurveys']['url'] = array_merge($url, ['action' => $this->alias(), 'index', 'id' => $id, 'student_id' => $student_id]);
		} else {
			// $tabElements['Students']['url'] = array_merge($url, ['action' => 'Students', 'view', $id]);
			// $tabElements['StudentUser']['url'] = array_merge($url, ['action' => 'StudentUser', 'view', $student_id, 'id' => $id]);
			// $tabElements = [];
			// $tabElements['Students'] = [
			// 	'text' => __('Academic'),
			// 	'url' => array_merge($url, ['action' => 'Students', 'view', $id])
			// ];

			// $tabElements['StudentUser'] = [
			// 	'text' => __('General'),
			// 	'url' => array_merge($url, ['action' => 'StudentUser', 'view', $student_id, 'id' => $id])
			// ];
			$tabElements['StudentSurveys']['url'] = array_merge($url, ['action' => $this->alias(), 'view', $entity->id, 'id' => $id, 'student_id' => $student_id]);
		}

		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}
}
