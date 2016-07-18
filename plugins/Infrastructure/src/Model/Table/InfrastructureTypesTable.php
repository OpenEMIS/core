<?php
namespace Infrastructure\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use FieldOption\Model\Traits\FieldOptionsTrait;

class InfrastructureTypesTable extends ControllerActionTable {
	use FieldOptionsTrait;

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Levels', ['className' => 'Infrastructure.InfrastructureLevels', 'foreignKey' => 'infrastructure_level_id']);
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'infrastructure_level_id'
			]);
		}

		$this->addBehavior('Infrastructure.Types');
		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		if ($extra->offsetExists('params') && array_key_exists('selectedLevel', $extra['params'])) {
			$selectedLevel = $extra['params']['selectedLevel'];

			if ($selectedLevel != '-1') {
				$query->where([$this->aliasField('infrastructure_level_id') => $selectedLevel]);
			}
		}
	}

	public function viewBeforeAction(Event $event, ArrayObject $extra) {
		$this->setupFields();
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra) {
		$this->setupFields();
	}

	public function onUpdateFieldInfrastructureLevelId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$levelOptions = $this->Levels->getOptions();
			$selectedLevel = !is_null($request->query('level')) ? $request->query('level') : '';

			$attr['options'] = $levelOptions;
			$attr['default'] = $selectedLevel;
		}

		return $attr;
	}

	private function setupFields() {
		$this->field('infrastructure_level_id', ['type' => 'select']);
	}
}
