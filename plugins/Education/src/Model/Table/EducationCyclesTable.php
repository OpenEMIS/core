<?php
namespace Education\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;

class EducationCyclesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationLevels', ['className' => 'Education.EducationLevels']);
		$this->hasMany('EducationProgrammes', ['className' => 'Education.EducationProgrammes', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Education.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function indexBeforePaginate(Event $event, Request $request, ArrayObject $options) {
		list($levelOptions, $selectedLevel) = array_values($this->getSelectOptions());

        $this->controller->set(compact('levelOptions', 'selectedLevel'));

		$options['conditions'][] = [
        	$this->aliasField('education_level_id') => $selectedLevel
        ];
	}

	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->field('education_level_id');
	}

	public function onUpdateFieldEducationLevelId(Event $event, array $attr, $action, Request $request) {
		list($levelOptions, $selectedLevel) = array_values($this->getSelectOptions());
		$attr['options'] = $levelOptions;
		if ($action == 'add') {
			$attr['default'] = $selectedLevel;
		}

		return $attr;
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$levelOptions = $this->EducationLevels->getLevelOptions();
		$selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

		return compact('levelOptions', 'selectedLevel');
	}
}
