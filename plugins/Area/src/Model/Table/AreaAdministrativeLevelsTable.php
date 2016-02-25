<?php
namespace Area\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Query;
use App\Model\Table\ControllerActionTable;

class AreaAdministrativeLevelsTable extends ControllerActionTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Countries', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'area_administrative_id']);
		$this->hasMany('Areas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'area_administrative_level_id']);
		$this->addBehavior('RestrictAssociatedDelete');
		// $this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('level', ['before' => 'name']);
		$this->field('area_administrative_id', ['type' => 'hidden', 'visible' => ['index' => false, 'view' => false, 'edit' => true, 'add' => true]]);
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		//Add controls filter to index page
		list($countryOptions, $selectedCountry) = array_values($this->getSelectOptions());
		$extra['elements']['controls'] = ['name' => 'Area.controls', 'data' => [], 'options' => [], 'order' => 1];
		$this->controller->set(compact('countryOptions', 'selectedCountry'));
		$query->where([$this->aliasField('area_administrative_id') => $selectedCountry]);
	}

	public function addEditBeforeAction(Event $event, ArrayObject $extra) {
		$this->fields['level']['type'] = 'hidden';
	}

	public function onUpdateFieldLevel(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			list(, $selectedCountry) = array_values($this->getSelectOptions());

			$query = $this->find();
			$results = $query
				->select(['level' => $query->func()->max('level')])
				->where([$this->aliasField('area_administrative_id') => $selectedCountry])
				->all();

			$maxLevel = 0;
			if (!$results->isEmpty()) {
				$data = $results->first();
				$maxLevel = $data->level;
			}

			$attr['attr']['value'] = ++$maxLevel;
		}

		return $attr;
	}

	public function onUpdateFieldAreaAdministrativeId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			list(, $selectedCountry) = array_values($this->getSelectOptions());
			$attr['attr']['value'] = $selectedCountry;
		}

		return $attr;
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$levelId = $this
			->find('all')
			->select([$this->aliasField('id')])
			->where([$this->aliasField('level') => 0])
			->first()
			->id;

		$countryOptions = $this->Countries
			->find('list')
			->where([$this->Countries->aliasField('area_administrative_level_id') => $levelId])
			->order([$this->Countries->aliasField('name')])
			->toArray();
		$selectedCountry = !is_null($this->request->query('country')) ? $this->request->query('country') : key($countryOptions);

		return compact('countryOptions', 'selectedCountry');
	}
}
