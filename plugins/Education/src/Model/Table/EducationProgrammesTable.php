<?php
namespace Education\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

class EducationProgrammesTable extends AppTable {
	private $_contain = ['EducationNextProgrammes._joinData'];
	private $_fieldOrder = ['code', 'name', 'duration', 'visible', 'education_field_of_study_id', 'education_cycle_id', 'education_certification_id'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationCycles', ['className' => 'Education.EducationCycles']);
		$this->belongsTo('EducationCertifications', ['className' => 'Education.EducationCertifications']);
		$this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies']);
		$this->hasMany('EducationGrades', ['className' => 'Education.EducationGrades', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionSiteProgrammes', ['className' => 'Institution.InstitutionSiteProgrammes', 'dependent' => true, 'cascadeCallbacks' => true]);
	
		$this->belongsToMany('EducationNextProgrammes', [
			'className' => 'Education.EducationNextProgrammes',
			'joinTable' => 'education_programmes_next_programmes',
			'foreignKey' => 'education_programme_id',
			'targetForeignKey' => 'next_programme_id',
			'through' => 'Education.EducationProgrammesNextProgrammes',
			'dependent' => true,
			// 'saveStrategy' => 'append'
		]);

		// $this->belongsToMany('EducationProgrammeNextProgrammes', [
		// 	'className' => 'Education.EducationProgrammeNextProgrammes',
		// 	'joinTable' => 'education_programme_next',
		// 	'foreignKey' => 'education_programme_id',
		// 	'targetForeignKey' => 'next_programme_id',
		// 	'through' => 'Education.EducationProgrammeNext',
		// 	'dependent' => true,
		// 	// 'saveStrategy' => 'append'
		// ]);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('next_programmes', ['type' => 'custom_next_programme', 'valueClass' => 'table-full-width']);
		$this->_fieldOrder[] = 'next_programmes';
	}

	public function afterAction(Event $event) {
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
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
			$EducationProgrammesNextProgrammes = TableRegistry::get('Education.EducationProgrammesNextProgrammes');
			$value = $EducationProgrammesNextProgrammes
				->findByEducationProgrammeId($entity->id)
				// ->where([$EducationProgrammesNextProgrammes->aliasField('visible') => 1])
				->count();
			$attr['value'] = $value;
		} else if ($action == 'view') {
			$tableHeaders = [__('Cycle - (Programme)')];
			$tableCells = [];

			$educationNextProgrammes = $entity->extractOriginal(['education_next_programmes']);
			foreach ($educationNextProgrammes['education_next_programmes'] as $key => $obj) {
				// if ($obj->_joinData->visible == 1) {
					$programe = $this->findById($nextProgramme->next_programme_id)->first();
					$rowData = [];
					$rowData[] = $programe->cycle_programme_name;
					$tableCells[] = $rowData;
				// }
			}

			$attr['tableHeaders'] = $tableHeaders;
	  		  	$attr['tableCells'] = $tableCells;
		} else if ($action == 'edit') {
			if (isset($entity->id)) {
				$nextProgrammeslist = TableRegistry::get('Education.EducationProgrammesNextProgrammes')->findByEducationProgrammeId($entity->id);
				$nextProgrammesArray = [];

				foreach($nextProgrammeslist as $nextProgramme){
					$nextProgrammesArray[] = $nextProgramme->next_programme_id;
				}

				$form = $event->subject()->Form;
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
										if(($programme->id != $entity->id) && !in_array($programme->id, $nextProgrammesArray)){
											$nextProgrammeOptions[$programme->id] = $cycle->name.' - ('.$programme->name.')';
										}
									}
								}
							}
						}
					}
				}			

				$tableHeaders = [__('Cycle - (Programme)')];
				$tableCells = [];
				$cellCount = 0;

				$arrayNextProgrammes = [];
				if ($this->request->is(['get'])) {
					$educationProgramme = TableRegistry::get('Education.EducationProgrammes');
					foreach($nextProgrammeslist as $nextProgramme){
						$programe = $educationProgramme->findById($nextProgramme->next_programme_id)->first();

						$arrayNextProgrammes[] = [
							'id' => $nextProgramme->id,
							'education_programme_id' => $nextProgramme->education_programme_id,
							'next_programme_id' => $nextProgramme->next_programme_id,
							'name' => $programe->cycle_programme_name
						];
					}
				} else if ($this->request->is(['post', 'put'])) {
					$requestData = $this->request->data;
					if (array_key_exists('education_programmes_next_programmes', $requestData[$this->alias()])) {
						foreach ($requestData[$this->alias()]['education_programmes_next_programmes'] as $key => $obj) {
							$arrayNextProgrammes[] = $obj['_joinData'];
						}
					}
					if (array_key_exists('next_programme_id', $requestData[$this->alias()])) {
						$nextProgrammeId = $requestData[$this->alias()]['next_programme_id'];
						$programmeObj = $this
										->findById($nextProgrammeId)
										->first();

						$arrayNextProgrammes[] = [
							'id' => '',
							'education_programme_id' => $entity->id,
							'next_programme_id' => $programmeObj->id,
							'name' => $programmeObj->cycle_programme_name,
						];
					}
				}
				
				foreach ($arrayNextProgrammes as $key => $obj) {
					$fieldPrefix = $attr['model'] . '.education_programmes_next_programmes.' . $cellCount++;
					$joinDataPrefix = $fieldPrefix . '._joinData';

					$educationProgrammeId = $obj['id'];
					$nextProgrammeName = $obj['name'];

					$cellData = "";
					// $cellData .= $form->hidden($fieldPrefix.".id", ['value' => $educationProgrammeId]);
					$cellData .= $form->hidden($joinDataPrefix.".name", ['value' => $nextProgrammeName]);
					$cellData .= $form->hidden($joinDataPrefix.".education_programme_id", ['value' => $obj['education_programme_id']]);
					$cellData .= $form->hidden($joinDataPrefix.".next_programme_id", ['value' => $obj['next_programme_id']]);
					if (isset($obj['id'])) {
						$cellData .= $form->hidden($joinDataPrefix.".id", ['value' => $obj['id']]);
					}

					$rowData = [];
					$rowData[] = $nextProgrammeName;
					$rowData[] = $cellData;
					$rowData[] = '<button onclick="jsTable.doRemove(this)" aria-expanded="true" type="button" class="btn btn-dropdown action-toggle btn-single-action"><i class="fa fa-trash"></i>&nbsp;<span>'.__('Delete').'</span></button>';

					$tableCells[] = $rowData;
					unset($nextProgrammeOptions[$obj['next_programme_id']]);
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

	public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		pr($options);
		pr($entity);
		die;
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// to be revisit
		// $data[$this->alias()]['setVisible'] = true;

		// pr($data); die;

		// To handle when delete all subjects
		if (!array_key_exists('education_next_programmes', $data[$this->alias()])) {
			$data[$this->alias()]['education_next_programmes'] = [];
		}

		// Required by patchEntity for associated data
		$newOptions = [];
		$newOptions['associated'] = $this->_contain;

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain(['EducationNextProgrammes']);
	}

}
