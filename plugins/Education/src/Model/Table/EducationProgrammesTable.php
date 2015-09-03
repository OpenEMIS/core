<?php
namespace Education\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class EducationProgrammesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationCycles', ['className' => 'Education.EducationCycles']);
		$this->belongsTo('EducationCertifications', ['className' => 'Education.EducationCertifications']);
		$this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies']);
		$this->hasMany('EducationGrades', ['className' => 'Education.EducationGrades', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionSiteProgrammes', ['className' => 'Institution.InstitutionSiteProgrammes', 'dependent' => true, 'cascadeCallbacks' => true]);
	

	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('next_programmes', ['type' => 'custom_next_programme', 'valueClass' => 'table-full-width']);
		$this->ControllerAction->setFieldOrder('next_programmes');
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

	public function onGetCustomNextProgrammeElement(Event $event, $action, $entity, $attr, $options=[]) {
		if ($action == 'index') {
			// $EducationGradesSubjects = TableRegistry::get('EducationGradesSubjects');
			// $value = $EducationGradesSubjects
			// 	->findByEducationGradeId($entity->id)
			// 	->where([$EducationGradesSubjects->aliasField('visible') => 1])
			// 	->count();
			// $attr['value'] = $value;
		} else if ($action == 'view') {
			// $tableHeaders = [__('Cycle'), __('Next Programme')];
			// $tableCells = [];

			// $educationSubjects = $entity->extractOriginal(['education_subjects']);
			// foreach ($educationSubjects['education_subjects'] as $key => $obj) {
			// 	if ($obj->_joinData->visible == 1) {
			// 		$rowData = [];
			// 		$rowData[] = $obj->name;
			// 		$rowData[] = $obj->code;
			// 		$rowData[] = $obj->_joinData->hours_required;
			// 		$tableCells[] = $rowData;
			// 	}
			// }

			// $attr['tableHeaders'] = $tableHeaders;
	  //   	$attr['tableCells'] = $tableCells;
		} else if ($action == 'edit') {
			if (isset($entity->id)) {
				
				$nextProgrammeOptions = [];

				$currentProgrammSystem = $this->findById($entity->id)->contain(['EducationCycles.EducationLevels.EducationSystems'])->first();
				$systemId = $currentProgrammSystem->education_cycle->education_level->education_system->id;
				$currentCycleOrder = $currentProgrammSystem->education_cycle->order;
				$currentLevelOrder = $currentProgrammSystem->education_cycle->education_level->order;

				$systems = TableRegistry::get('Education.EducationSystems')
							->findById($systemId)
							->contain(['EducationLevels.EducationCycles.EducationProgrammes']);

				foreach($systems as $system) {
					foreach($system->education_levels as $level){
						if($level->order > $currentLevelOrder) {
							foreach($level->education_cycles as $cycle){
								if($cycle->order >= $currentCycleOrder) {
									foreach($cycle->education_programmes as $programme) {
										if($programme->id != $entity->id){
											$nextProgrammeOptions[$programme->id] = $cycle->name.' - ('.$programme->name.')';
										}
									}
								}
							}
						}
					}
				}			

				$form = $event->subject()->Form;

				$tableHeaders = [__('Cycle - (Programme)')];
				$tableCells = [];
				$cellCount = 0;

				$arrayNextProgrammes = [];
				if ($this->request->is(['get'])) {

					$nextProgrammeslist = TableRegistry::get('Education.EducationProgrammeNext')->findByEducationProgrammeId($entity->id);
					foreach($nextProgrammeslist as $nextProgramme){
						$arrayNextProgrammes[] = [
							'id' => $nextProgramme->id,
							'next_programme' => $nextProgramme->NextEducationProgrammes->name
						];
					}
				} else if ($this->request->is(['post', 'put'])) {
					// $requestData = $this->request->data;
					// if (array_key_exists('education_subjects', $requestData[$this->alias()])) {
					// 	foreach ($requestData[$this->alias()]['education_subjects'] as $key => $obj) {
					// 		$arraySubjects[] = $obj['_joinData'];
					// 	}
					// }

					// if (array_key_exists('education_subject_id', $requestData[$this->alias()])) {
					// 	$subjectId = $requestData[$this->alias()]['education_subject_id'];
					// 	$subjectObj = $this->EducationSubjects
					// 		->findById($subjectId)
					// 		->first();

					// 	$arraySubjects[] = [
					// 		'name' => $subjectObj->name,
					// 		'code' => $subjectObj->code,
					// 		'hours_required' => 0,
					// 		'education_grade_id' => $entity->id,
					// 		'education_subject_id' => $subjectObj->id,
					// 		'visible' => 1
					// 	];
					// }
				}

				foreach ($arrayNextProgrammes as $key => $obj) {
					$nextProgrammeId = $key;
					$nextProgrammeName = $obj;

					$rowData = [];
					$rowData[] = $nextProgrammeName;
					$rowData[] = '<button onclick="jsTable.doRemove(this)" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';

					$tableCells[] = $rowData;
					//unset($nextProgrammeOptions[$obj['education_subject_id']]);
				}

				$attr['tableHeaders'] = $tableHeaders;
	    		$attr['tableCells'] = $tableCells;

	    		$nextProgrammeOptions[0] = "-- ".__('Add Next Programme') ." --";
	    		ksort($nextProgrammeOptions);
	    		$attr['options'] = $nextProgrammeOptions;
			}
		}

		return $event->subject()->renderElement('Education.next_programmes', ['attr' => $attr]);
	}

}
