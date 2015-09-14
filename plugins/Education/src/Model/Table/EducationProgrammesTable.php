<?php
namespace Education\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Traits\HtmlTrait;

class EducationProgrammesTable extends AppTable {
	use HtmlTrait;

	private $_contain = ['EducationNextProgrammes._joinData'];
	private $_fieldOrder = ['code', 'name', 'duration', 'visible', 'education_field_of_study_id', 'education_cycle_id', 'education_certification_id'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('EducationCycles', ['className' => 'Education.EducationCycles']);
		$this->belongsTo('EducationCertifications', ['className' => 'Education.EducationCertifications']);
		$this->belongsTo('EducationFieldOfStudies', ['className' => 'Education.EducationFieldOfStudies']);
		$this->hasMany('EducationGrades', ['className' => 'Education.EducationGrades', 'cascadeCallbacks' => true]);
		$this->hasMany('InstitutionSiteProgrammes', ['className' => 'Institution.InstitutionSiteProgrammes', 'cascadeCallbacks' => true]);
	
		$this->belongsToMany('EducationNextProgrammes', [
			'className' => 'Education.EducationNextProgrammes',
			'joinTable' => 'education_programmes_next_programmes',
			'foreignKey' => 'education_programme_id',
			'targetForeignKey' => 'next_programme_id',
			'through' => 'Education.EducationProgrammesNextProgrammes',
			'dependent' => false,
		]);
	}

	public function beforeAction(Event $event) {
		if ($this->action != 'index') {
			$this->ControllerAction->field('next_programmes', ['type' => 'custom_next_programme', 'valueClass' => 'table-full-width']);
			$this->_fieldOrder[] = 'next_programmes';
		}
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


	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		if (empty($this->request->data['transfer_to'])) {
			$this->Alert->error('general.deleteTransfer.restrictDelete');
			$event->stopPropagation();
			return $this->controller->redirect($this->ControllerAction->url('remove'));
		}
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

	public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $options) {
		$query->where([$this->aliasField('education_cycle_id') => $entity->education_cycle_id]);
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
		$EducationProgrammesNextProgrammes = TableRegistry::get('Education.EducationProgrammesNextProgrammes');
		if ($action == 'index') {
			$value = $EducationProgrammesNextProgrammes
				->find()
				->where([$EducationProgrammesNextProgrammes->aliasField('education_programme_id') => $entity->id])
				->count();
			$attr['value'] = $value;
		} else if ($action == 'view') {
			$tableHeaders = [__('Cycle - (Programme)')];
			$tableCells = [];

			$educationNextProgrammes = $entity->extractOriginal(['education_next_programmes']);
			foreach ($educationNextProgrammes['education_next_programmes'] as $key => $obj) {
				if (!is_null($obj->_joinData)) {
					$programe = $this->find()->where([$this->aliasField('id') => $obj->_joinData->next_programme_id])->contain(['EducationCycles'])->first();
					$rowData = [];
					$rowData[] = $programe->cycle_programme_name;
					$tableCells[] = $rowData;
				}
			}

			$attr['tableHeaders'] = $tableHeaders;
	  		$attr['tableCells'] = $tableCells;
		} else if ($action == 'edit') {
			if (isset($entity->id)) {
				$nextProgrammeslist = $EducationProgrammesNextProgrammes
													->find('list', ['keyField' => 'id', 'valueField' => 'next_programme_id'])
													->where([$EducationProgrammesNextProgrammes->aliasField('education_programme_id') => $entity->id])
													->toArray()
													;						
				$form = $event->subject()->Form;
				$nextProgrammeOptions = [];

				$currentProgrammSystem = $this->find()->contain(['EducationCycles.EducationLevels.EducationSystems'])->where([$this->aliasField('id') => $entity->id])->first();
				$systemId = $currentProgrammSystem->education_cycle->education_level->education_system->id;
				$currentCycleOrder = $currentProgrammSystem->education_cycle->order;
				$currentLevelOrder = $currentProgrammSystem->education_cycle->education_level->order;

				$EducationSystems = TableRegistry::get('Education.EducationSystems');
				$systems = $EducationSystems
							->find()
							->where([$EducationSystems->aliasField('id') => $systemId])
							->contain(['EducationLevels.EducationCycles.EducationProgrammes']);

				//retrieving all programmes belonging to current level's cycle's or next level's cycle's			
				foreach($systems as $system) {
					foreach($system->education_levels as $level){
						if($level->order >= $currentLevelOrder) {
							foreach($level->education_cycles as $cycle){
								if($cycle->order >= $currentCycleOrder) {
									foreach($cycle->education_programmes as $programme) {
										if(($programme->id != $entity->id) && !in_array($programme->id, $nextProgrammeslist)){
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
					foreach($nextProgrammeslist as $next_programme_id){
						$programme = $educationProgramme->find()->where([$educationProgramme->aliasField('id') => $next_programme_id])->contain(['EducationCycles'])->first();
						$arrayNextProgrammes[] = [
							'id' => $programme->id,
							'education_programme_id' => $programme->education_programme_id,
							'next_programme_id' => $next_programme_id,
							'name' => $programme->cycle_programme_name
						];
					}
				} else if ($this->request->is(['post', 'put'])) {
					$requestData = $this->request->data;
					if (array_key_exists('education_next_programmes', $requestData[$this->alias()])) {
						foreach ($requestData[$this->alias()]['education_next_programmes'] as $key => $obj) {
							$arrayNextProgrammes[] = $obj['_joinData'];
						}
					}
					if (array_key_exists('next_programme_id', $requestData[$this->alias()])) {
						$nextProgrammeId = $requestData[$this->alias()]['next_programme_id'];
						$programmeObj = $this
										->find()
										->where([$this->aliasField('id') => $nextProgrammeId])
										->first();

						$arrayNextProgrammes[] = [
							'education_programme_id' => $entity->id,
							'next_programme_id' => $programmeObj->id,
							'name' => $programmeObj->cycle_programme_name,
						];
					}
				}

				foreach ($arrayNextProgrammes as $key => $obj) {
					$fieldPrefix = $attr['model'] . '.education_next_programmes.' . $cellCount++;
					$joinDataPrefix = $fieldPrefix . '._joinData';

					$educationProgrammeId = $obj['next_programme_id'];
					$nextProgrammeName = $obj['name'];

					$cellData = "";
					$cellData .= $form->hidden($fieldPrefix.".id", ['value' => $educationProgrammeId]);
					$cellData .= $form->hidden($joinDataPrefix.".name", ['value' => $nextProgrammeName]);
					$cellData .= $form->hidden($joinDataPrefix.".education_programme_id", ['value' => $obj['education_programme_id']]);
					$cellData .= $form->hidden($joinDataPrefix.".next_programme_id", ['value' => $obj['next_programme_id']]);
					if (isset($obj['id'])) {
						$cellData .= $form->hidden($joinDataPrefix.".id", ['value' => $obj['id']]);
					}

					$rowData = [];
					$rowData[] = $nextProgrammeName;
					$rowData[] = $cellData;
					$rowData[] = $this->getDeleteButton();

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

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		// to be revisit
		// $data[$this->alias()]['setVisible'] = true;

		// To handle when delete all programmes
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
