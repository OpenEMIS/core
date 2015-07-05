<?php
namespace Student\Model\Table;

use ArrayObject;
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
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
		$this->belongsTo('AssessmentItems', ['className' => 'Assessment.AssessmentItems']);
		$this->belongsTo('AssessmentGradingOptions', ['className' => 'Assessment.AssessmentGradingOptions']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
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
			'academic_period_id', 'institution_site_id', 'assessment', 'assessment_item_id'
		]);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		$contain = $options['contain'];
		foreach ($contain as $i => $association) {
			if ($association == 'AssessmentItems') {
				$contain[$i] = 'AssessmentItems.EducationSubjects';
				$contain[] = 'AssessmentItems.Assessments';
			}
		}
		$options['contain'] = $contain;
	}
}
