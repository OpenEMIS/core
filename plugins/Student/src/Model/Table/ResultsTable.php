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
		$this->belongsTo('AssessmentPeriods', ['className' => 'Assessment.AssessmentPeriods']);
		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('AssessmentItems', ['className' => 'Assessment.AssessmentItems']);
		$this->belongsTo('Assessments', ['className' => 'Assessment.Assessments']);
		$this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
		$this->belongsTo('AssessmentGradingOptions', ['className' => 'Assessment.AssessmentGradingOptions']);
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
