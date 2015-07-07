<?php
namespace Area\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;

class AreaAdministrativeLevelsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Countries', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'area_administrative_id']);
		$this->hasMany('Areas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'area_administrative_id']);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('level');
		$this->ControllerAction->field('area_administrative_id');
		$this->ControllerAction->setFieldOrder('level', 'name');

		$this->fields['area_administrative_id']['visible']['index'] = false;
		$this->fields['area_administrative_id']['visible']['view'] = false;
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Area.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		list($countryOptions, $selectedCountry) = array_values($this->getSelectOptions());

        $this->controller->set(compact('countryOptions', 'selectedCountry'));

		$options['conditions'][] = [
        	$this->aliasField('area_administrative_id') => $selectedCountry
        ];
	}

	public function addEditBeforeAction(Event $event) {
		$this->fields['level']['type'] = 'hidden';
		$this->fields['area_administrative_id']['type'] = 'hidden';
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
