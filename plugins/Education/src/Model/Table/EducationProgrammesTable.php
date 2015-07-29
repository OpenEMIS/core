<?php
namespace Education\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;

class EducationProgrammesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationCycles', ['className' => 'Education.EducationCycles']);
		$this->belongsTo('EducationCertifications', ['className' => 'Education.EducationCertifications']);
		$this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies']);
		$this->hasMany('EducationGrades', ['className' => 'Education.EducationGrades', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionSiteProgrammes', ['className' => 'Institution.InstitutionSiteProgrammes', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function indexBeforeAction(Event $event) {
		//Add controls filter to index page
		$toolbarElements = [
            ['name' => 'Education.controls', 'data' => [], 'options' => []]
        ];

		$this->controller->set('toolbarElements', $toolbarElements);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		list($levelOptions, $selectedLevel, $cycleOptions, $selectedCycle) = array_values($this->getSelectOptions());
        $this->controller->set(compact('levelOptions', 'selectedLevel', 'cycleOptions', 'selectedCycle'));

		$query->where([$this->aliasField('education_cycle_id') => $selectedCycle]);
	}

	public function addEditBeforeAction(Event $event) {
		$this->ControllerAction->field('education_cycle_id');
		$this->fields['education_field_of_study_id']['type'] = 'select';
		$this->fields['education_certification_id']['type'] = 'select';
	}

	public function onUpdateFieldEducationCycleId(Event $event, array $attr, $action, Request $request) {
		list(, , $cycleOptions, $selectedCycle) = array_values($this->getSelectOptions());
		$attr['options'] = $cycleOptions;
		if ($action == 'add') {
			$attr['default'] = $selectedCycle;
		}

		return $attr;
	}

	public function findWithCycle(Query $query, array $options) {
		return $query
			->contain(['EducationCycles'])
			->order(['EducationCycles.order' => 'ASC', $this->aliasField('order') => 'ASC']);
	}

	public function getSelectOptions() {
		//Return all required options and their key
		$levelOptions = $this->EducationCycles->EducationLevels->getLevelOptions();
		$selectedLevel = !is_null($this->request->query('level')) ? $this->request->query('level') : key($levelOptions);

		$cycleOptions = $this->EducationCycles
			->find('list')
			->find('visible')
			->find('order')
			->where([$this->EducationCycles->aliasField('education_level_id') => $selectedLevel])
			->toArray();
		$selectedCycle = !is_null($this->request->query('cycle')) ? $this->request->query('cycle') : key($cycleOptions);

		return compact('levelOptions', 'selectedLevel', 'cycleOptions', 'selectedCycle');
	}
}
