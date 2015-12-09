<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class ResultsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('assessment_item_results');
		$config['Modified'] = false;
		$config['Created'] = false;
		parent::initialize($config);

		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AssessmentItems', ['className' => 'Assessment.AssessmentItems']);
		$this->belongsTo('AssessmentGradingOptions', ['className' => 'Assessment.AssessmentGradingOptions']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}
	public function onGetInstitutionId(Event $event, Entity $entity) {
		return $this->Institutions->get($entity->institution_id)->name;
	}

	public function onGetAcademicPeriodId(Event $event, Entity $entity) {
		return $this->AcademicPeriods->get($entity->academic_period_id)->name;
	}

	public function onGetAssessmentItemId(Event $event, Entity $entity) {
		return $entity->assessment_item->education_subject->name;
	}

	public function onGetAssessment(Event $event, Entity $entity) {
		return $entity->assessment_item->assessment->name;
	}

	public function beforeAction() {
		$this->ControllerAction->field('assessment');
		$this->ControllerAction->field('assessment_result_id', ['visible' => false]);
		$this->ControllerAction->field('assessment_result_type_id', ['visible' => false]);
		$this->ControllerAction->field('assessment_grading_option_id', ['visible' => false]);

		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'institution_id', 'assessment', 'assessment_item_id'
		]);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$options['auto_contain'] = false;
		$query->contain([
			'AssessmentItems.EducationSubjects',
			'AssessmentItems.Assessments'
		])
		->join(
			[	'table'=> 'institution_site_assessments',
				'alias' => 'InstitutionSiteAssessments',
				'conditions' => [
					'InstitutionSiteAssessments.academic_period_id = '. $this->aliasField('academic_period_id'),
					'InstitutionSiteAssessments.institution_site_id = '. $this->aliasField('institution_id')
				]
			]
		)
		->where(['InstitutionSiteAssessments.status' => 2])
		;
	}

	private function setupTabElements() {
		$tabElements = $this->controller->getAcademicTabElements();
		$this->controller->set('tabElements', $tabElements);
		$alias = $this->alias();
		$this->controller->set('selectedAction', $alias);
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
