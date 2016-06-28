<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

use Cake\Event\Event;
use Cake\I18n\Time;

class ShiftOptionsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('shift_options');
		parent::initialize($config);
		//$this->hasMany('SpecialNeeds', ['className' => 'User.SpecialNeeds', 'foreignKey' => 'special_need_difficulty_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', false);
		$this->behaviors()->get('ControllerAction')->config('actions.add', false);
	}

	public function beforeAction(Event $event) {
		$this->field('default', ['visible' => false]);
		$this->field('editable', ['visible' => false]);
		$this->field('international_code', ['visible' => false]);
		$this->field('national_code', ['visible' => false]);
		$this->field('start_time', ['after' => 'name']);
		$this->field('end_time', ['after' => 'start_time']);
	}

	public function indexBeforeAction(Event $event) {
		$this->field('default', ['visible' => false]);
		$this->field('editable', ['visible' => false]);
		$this->field('international_code', ['visible' => false]);
		$this->field('national_code', ['visible' => false]);
	}

	public function getStartEndTime($shiftOptionId, $select) {
		$data = $this->find()->where(['id' => $shiftOptionId])->toArray();
		return $data[0][$select.'_time'];
	}
}
?>