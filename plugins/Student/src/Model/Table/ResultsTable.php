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
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
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
		$this->ControllerAction->field('assessment_grading_option_id');
		$this->ControllerAction->field('student_id', ['visible' => false]);

		$this->ControllerAction->setFieldOrder([
			'academic_period_id', 'institution_id', 'assessment', 'assessment_item_id'
		]);
	}

	public function onGetMarks(Event $event, Entity $entity) {
		return $entity->marks;
	}

	public function onGetAssessmentGradingOptionId(Event $event, Entity $entity) {
		$returnValue = '';
		if (!empty($entity['assessment_grading_option_id'])) {
			$gradingOptionValue = $this->AssessmentGradingOptions->find()
				->where([$this->AssessmentGradingOptions->aliasField('id') => $entity['assessment_grading_option_id']])
				->first();
			if (!empty($gradingOptionValue)) {
				if (!empty($gradingOptionValue['code'])) {
					$returnValue = $gradingOptionValue['code'];
				}
				if (!empty($gradingOptionValue['name'])) {
					if (!empty($returnValue)) {
						$returnValue .= ' - '.$gradingOptionValue['name'];
					} else {
						$returnValue = $gradingOptionValue['name'];
					}
				} 
			}
		}
		return $returnValue;
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$options['auto_contain'] = false;
		$query->contain([
			'AssessmentItems.EducationSubjects',
			'AssessmentItems.Assessments'
		])
		->join(
			[	'table'=> 'institution_assessments',
				'alias' => 'InstitutionAssessments',
				'conditions' => [
					'InstitutionAssessments.academic_period_id = '. $this->aliasField('academic_period_id'),
					'InstitutionAssessments.institution_id = '. $this->aliasField('institution_id')
				]
			]
		)
		->where(['InstitutionAssessments.status' => 2])
		;
	}

	private function setupTabElements() {
		$options['type'] = 'student';
		$tabElements = $this->controller->getAcademicTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$alias = $this->alias();
		$this->controller->set('selectedAction', $alias);
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
