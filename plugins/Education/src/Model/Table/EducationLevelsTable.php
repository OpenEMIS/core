<?php
namespace Education\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

class EducationLevelsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationLevelIsced', ['className' => 'Education.EducationLevelIsced']);
		$this->belongsTo('EducationSystems', ['className' => 'Education.EducationSystems']);
		$this->hasMany('EducationCycles', ['className' => 'Education.EducationCycles']);

		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'education_system_id',
			]);
		}
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Education.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $options) {
		$query->where([$this->aliasField('education_system_id') => $entity->education_system_id]);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		list($systemOptions, $selectedSystem) = array_values($this->getSelectOptions());
        $this->controller->set(compact('systemOptions', 'selectedSystem'));

		$query->where([$this->aliasField('education_system_id') => $selectedSystem]);
	}

	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->field('education_system_id');
		$this->fields['education_level_isced_id']['type'] = 'select';
	}

	public function onUpdateFieldEducationSystemId(Event $event, array $attr, $action, Request $request) {
		list($systemOptions, $selectedSystem) = array_values($this->getSelectOptions());
		$attr['options'] = $systemOptions;
		if ($action == 'add') {
			$attr['default'] = $selectedSystem;
		}

		return $attr;
	}

	public function findWithSystem(Query $query, array $options) {
		return $query
			->contain(['EducationSystems'])
			->order(['EducationSystems.order' => 'ASC', $this->aliasField('order') => 'ASC']);
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$systemOptions = $this->EducationSystems
			->find('list')
			->find('visible')
			->find('order')
			->toArray();
		$selectedSystem = !is_null($this->request->query('system')) ? $this->request->query('system') : key($systemOptions);

		return compact('systemOptions', 'selectedSystem');
	}

	public function getLevelOptions() {
		$list = $this
			->find('list', ['keyField' => 'id', 'valueField' => 'system_level_name'])
			->find('visible')
			->contain(['EducationSystems'])
			->order([
				$this->EducationSystems->aliasField('order'),
				$this->aliasField('order')
			])
			->toArray();

		return $list;
	}
}
