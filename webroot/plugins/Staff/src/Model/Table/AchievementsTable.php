<?php
namespace Staff\Model\Table;

use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use App\Model\Table\AppTable;

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
			'allowable_file_types' => 'all',
			'useDefaultName' => true
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

	public function beforeAction(Event $event) {
		$visible = ['index' => false, 'view' => true, 'edit' => true];
		$this->ControllerAction->field('training_achievement_type_id', ['type' => 'select']);
		$this->ControllerAction->field('description', ['visible' => $visible]);
		$this->ControllerAction->field('objective', ['visible' => $visible]);
		$this->ControllerAction->field('end_date', ['visible' => $visible]);
		$this->ControllerAction->field('duration', ['visible' => $visible]);
		$this->ControllerAction->field('file_name', [
			'type' => 'hidden',
			'visible' => $visible
		]);
		$this->ControllerAction->field('file_content', ['visible' => $visible]);
		$this->ControllerAction->field('staff_id', ['type' => 'hidden']);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'training_achievement_type_id', 'code', 'name', 'start_date', 'credit_hours'
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$session = $request->session();
			$sessionKey = 'Staff.Staff.id';

			if ($session->check($sessionKey)) {
				$attr['attr']['value'] = $session->read($sessionKey);
			}
		}

		return $attr;
	}

	public function setupFields(Entity $entity) {
		$this->ControllerAction->setFieldOrder([
			'training_achievement_type_id', 'code', 'name', 'description', 'objective',
			'start_date', 'end_date', 'credit_hours', 'duration',
			'file_name', 'file_content'
		]);
	}
}
