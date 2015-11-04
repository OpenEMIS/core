<?php
namespace Staff\Model\Table;

// use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
// use Cake\Network\Request;
use App\Model\Table\AppTable;
// use App\Model\Traits\OptionsTrait;

class AchievementsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_training_self_studies');
		parent::initialize($config);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('TrainingAchievementTypes', ['className' => 'Training.TrainingAchievementTypes', 'foreignKey' => 'training_achievement_type_id']);
		$this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);

		$this->addBehavior('ControllerAction.FileUpload', [
			// 'name' => 'file_name',
			// 'content' => 'file_content',
			'size' => '10MB',
			'contentEditable' => true,
			'allowable_file_types' => 'all'
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('code', [
				'ruleUnique' => [
					'rule' => ['validateUnique'],
					'provider' => 'table'
				]
			])
			->add('end_date', 'ruleCompareDateReverse', [
				'rule' => ['compareDateReverse', 'start_date', true]
			])
			->allowEmpty('file_content');
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function setupFields(Entity $entity) {
		$this->ControllerAction->field('training_achievement_type_id', ['type' => 'select']);
		$this->ControllerAction->field('staff_id', ['type' => 'hidden']);
		$this->ControllerAction->setFieldOrder([
			'code', 'name', 'description', 'objective',
			'start_date', 'end_date', 'credit_hours', 'duration',
			'training_achievement_type_id', 'file_name', 'file_content'
		]);
	}
}
