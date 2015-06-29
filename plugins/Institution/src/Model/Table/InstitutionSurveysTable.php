<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Event\Event;

class InstitutionSurveysTable extends AppTable {
	private $status = [
		0 => 'New',
		1 => 'Draft',
		2 => 'Completed',
	];

	public function initialize(array $config) {
		$this->table('institution_site_surveys');
		parent::initialize($config);
		
		$this->belongsTo('Periods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
		$this->belongsTo('Forms', ['className' => 'Survey.SurveyForms', 'foreignKey' => 'survey_form_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);

		$this->hasMany('CustomFieldValues', ['className' => 'Institution.InstitutionSurveyAnswers', 'foreignKey' => 'institution_survey_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'Institution.InstitutionSurveyTableCells', 'foreignKey' => 'institution_survey_id', 'dependent' => true, 'cascadeCallbacks' => true]);

		// $this->addBehavior('CustomField.Record', [
		// 	'recordKey' => 'institution_survey_id',
		// 	'moduleKey' => null,
		// 	'fieldKey' => 'survey_question_id',
		// 	'formKey' => 'survey_form_id'
		// ]);
	}

	public function indexBeforeAction(Event $event) {
		$query = $this->request->query;
		$selectedAction = isset($query['status']) ? $query['status'] : 0;

		$tabElements = [
			'New' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Surveys?status=0'],
				'text' => __('New')
			],
			'Draft' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Surveys?status=1'],
				'text' => __('Draft')
			],
			'Completed' => [
				'url' => ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Surveys?status=2'],
				'text' => __('Completed')
			]
		];

        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->status[$selectedAction]);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		if ($this->behaviors()->hasMethod('addEditAfterAction')) {
			list($entity) = array_values($this->behaviors()->call('addEditAfterAction', [$event, $entity]));
		}
		return $entity;
	}
}
