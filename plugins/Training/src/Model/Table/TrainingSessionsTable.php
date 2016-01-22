<?php
namespace Training\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Event\Event;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use Cake\Collection\Collection;
use Import\Model\Traits\ImportExcelTrait;

class TrainingSessionsTable extends AppTable {
	use OptionsTrait;
	use HtmlTrait;
	use ImportExcelTrait;

	private $_contain = ['Trainers.Users', 'Trainees'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_course_id']);
		$this->belongsTo('TrainingProviders', ['className' => 'Training.TrainingProviders', 'foreignKey' => 'training_provider_id']);
		$this->hasMany('Trainers', ['className' => 'Training.TrainingSessionTrainers', 'foreignKey' => 'training_session_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('SessionResults', ['className' => 'Training.TrainingSessionResults', 'foreignKey' => 'training_session_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('TraineeResults', ['className' => 'Training.TrainingSessionTraineeResults', 'foreignKey' => 'training_session_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('Trainees', [
			'className' => 'User.Users',
			'joinTable' => 'training_sessions_trainees',
			'foreignKey' => 'training_session_id',
			'targetForeignKey' => 'trainee_id',
			'through' => 'Training.TrainingSessionsTrainees',
			'dependent' => true
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		
		return $validator
			->add('code', [
				'ruleUnique' => [
					'rule' => ['validateUnique', ['scope' => 'training_course_id']],
					'provider' => 'table'
				]
			])
			->add('end_date', 'ruleCompareDateReverse', [
				'rule' => ['compareDateReverse', 'start_date', true]
			]);
	}

	public function onGetTraineeTableElement(Event $event, $action, $entity, $attr, $options=[]) {
		$tableHeaders = [__('OpenEMIS No'), __('Name')];
		$tableCells = [];
		$alias = $this->alias();
		$key = 'trainees';

		if ($action == 'index') {
			// not showing
		} else if ($action == 'view') {
			$associated = $entity->extractOriginal([$key]);
			if (!empty($associated[$key])) {
				foreach ($associated[$key] as $i => $obj) {
					$rowData = [];
					$rowData[] = $obj->openemis_no;
					$rowData[] = $obj->name;

					$tableCells[] = $rowData;
				}
			}
		} else if ($action == 'edit') {
			$tableHeaders[] = ''; // for delete column
			$Form = $event->subject()->Form;

			if ($this->request->is(['get'])) {
				if (!array_key_exists($alias, $this->request->data)) {
					$this->request->data[$alias] = [$key => []];
				} else {
					$this->request->data[$alias][$key] = [];
				}

				$associated = $entity->extractOriginal([$key]);
				if (!empty($associated[$key])) {
					foreach ($associated[$key] as $i => $obj) {
						$this->request->data[$alias][$key][] = [
							'id' => $obj->id,
							'_joinData' => ['openemis_no' => $obj->openemis_no, 'trainee_id' => $obj->id, 'name' => $obj->name]
						];
					}
				}
			}
			// refer to addEditOnAddTrainee for http post
			if ($this->request->data("$alias.$key")) {
				$associated = $this->request->data("$alias.$key");

				foreach ($associated as $i => $obj) {
					$joinData = $obj['_joinData'];
					$rowData = [];
					$name = $joinData['name'];
					$name .= $Form->hidden("$alias.$key.$i.id", ['value' => $joinData['trainee_id']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.openemis_no", ['value' => $joinData['openemis_no']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.trainee_id", ['value' => $joinData['trainee_id']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.name", ['value' => $joinData['name']]);
					$rowData[] = [$joinData['openemis_no'], ['autocomplete-exclude' => $joinData['trainee_id']]];
					$rowData[] = $name;
					$rowData[] = $this->getDeleteButton();
					$tableCells[] = $rowData;
				}
			}
		}

		$attr['tableHeaders'] = $tableHeaders;
    	$attr['tableCells'] = $tableCells;

		return $event->subject()->renderElement('Training.Sessions/' . $key, ['attr' => $attr]);
	}

	public function beforeAction(Event $event) {
		// Type / Visible
		$visible = ['index' => false, 'view' => true, 'edit' => true, 'add' => true];
		$this->ControllerAction->field('end_date', ['visible' => $visible]);
		$this->ControllerAction->field('comment', ['visible' => $visible]);

		$trainerTypeOptions = $this->getSelectOptions($this->aliasField('trainer_types'));
		$this->controller->set('trainerTypeOptions', $trainerTypeOptions);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'code', 'name', 'start_date', 'end_date', 'training_course_id', 'training_provider_id'
		]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain($this->_contain);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($event, $entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($event, $entity);
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		//Required by patchEntity for associated data
		$newOptions = [];
		// _joinData is required for 'saveStrategy' => 'replace' to work
		$newOptions['associated'] = [
			'Trainers', 'Trainees._joinData'
		];

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);

		// PHPOE-2491: During edit, if there are more than one trainees and when all trainees were removed at the same time,
		// "trainees" array will not be included in $data. We have to manually add it so that 'saveStrategy' => 'replace' will work
		if ($data->offsetExists('TrainingSessions')) {
			if (!isset($data['TrainingSessions']['trainees'])) {
				$data['TrainingSessions']['trainees'] = [];
			}
		}
	}

	public function addEditOnChangeCourse(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['course']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('training_course_id', $request->data[$this->alias()])) {
					$request->query['course'] = $request->data[$this->alias()]['training_course_id'];
				}
			}
		}
	}

	public function addEditOnAddTrainer(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$dataOptions = [
			'type' => key($this->getSelectOptions($this->aliasField('trainer_types'))),
			'trainer_id' => '',
			'name' => ''
		];
		$data[$this->alias()]['trainers'][] = $dataOptions;

		//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
		$options['associated'] = [
			'Trainers' => ['validate' => false]
		];
	}

	public function addEditOnAddTrainee(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$alias = $this->alias();
		$key = 'trainees';

		if ($data->offsetExists('trainee_id')) {
			$id = $data['trainee_id'];
			try {
				$obj = $this->Trainees->get($id);

				if (!array_key_exists($key, $data[$alias])) {
					$data[$alias][$key] = [];
				}
				$data[$alias][$key][] = [
					'id' => $obj->id,
					'_joinData' => ['openemis_no' => $obj->openemis_no, 'trainee_id' => $obj->id, 'name' => $obj->name]
				];
			} catch (RecordNotFoundException $ex) {
				$this->log(__METHOD__ . ': Record not found for id: ' . $id, 'debug');
			}
		}

		//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
		$options['associated'] = [
			'Trainees' => ['validate' => false]
		];
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['course'] = $entity->training_course_id;
	}

	public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
		if ($action == 'edit') {
			$includes['autocomplete'] = [
				'include' => true, 
				'css' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/css/autocomplete'],
				'js' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/js/autocomplete']
			];
		}
	}

	public function ajaxTraineeAutocomplete() {
		$this->controller->autoRender = false;
		$this->ControllerAction->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$term = $this->request->query['term'];
			// $data = $this->Trainees->autocomplete($term);

			// autocomplete
			$session = $this->request->session();
			$sessionKey = $this->registryAlias() . '.id';

			$data = [];
			if ($session->check($sessionKey)) {
				$id = $session->read($sessionKey);
				$entity = $this->get($id);

				$TargetPopulations = TableRegistry::get('Training.TrainingCoursesTargetPopulations');
				$Staff = TableRegistry::get('Institution.Staff');
				$Users = TableRegistry::get('User.Users');
				$Positions = TableRegistry::get('Institution.InstitutionPositions');
				$search = sprintf('%%%s%%', $term);

				$targetPopulationIds = $TargetPopulations
					->find('list', ['keyField' => 'target_population_id', 'valueField' => 'target_population_id'])
					->where([$TargetPopulations->aliasField('training_course_id') => $entity->training_course_id])
					->toArray();

				$list = $Staff
					->find()
					->matching('Users', function($q) use ($Users, $search) {
						return $q
							->find('all')
							->where([
								'OR' => [
									$Users->aliasField('openemis_no') . ' LIKE' => $search,
									$Users->aliasField('first_name') . ' LIKE' => $search,
									$Users->aliasField('middle_name') . ' LIKE' => $search,
									$Users->aliasField('third_name') . ' LIKE' => $search,
									$Users->aliasField('last_name') . ' LIKE' => $search
								]
							]);
					})
					->matching('Positions', function($q) use ($Positions, $targetPopulationIds) {
						return $q
							->find('all')
							->where([
								'Positions.staff_position_title_id IN' => $targetPopulationIds
							]);
					})
					->group([
						$Staff->aliasField('staff_id')
					])
					->order([$Users->aliasField('first_name')])
					->all();

				foreach($list as $obj) {
					$_matchingData = $obj->_matchingData['Users'];
					$data[] = [
						'label' => sprintf('%s - %s', $_matchingData->openemis_no, $_matchingData->name),
						'value' => $_matchingData->id
					];
				}
			}
			// End

			echo json_encode($data);
			die;
		}
	}

	public function onUpdateFieldTrainingCourseId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$courseOptions = $this->Training->getCourseList();
			$courseId = $this->queryString('course', $courseOptions);

			$attr['options'] = $courseOptions;
			$attr['onChangeReload'] = 'changeCourse';
		} else if ($action == 'edit') {
			$courseId = $request->query('course');
			$course = $this->Courses->get($courseId);

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $course->code_name;
		}

		return $attr;
	}

	public function onUpdateFieldTrainingProviderId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$courseId = $request->query('course');

			$TrainingCoursesProviders = TableRegistry::get('Training.TrainingCoursesProviders');
			$providers = $TrainingCoursesProviders
				->find()
				->matching('TrainingProviders')
				->where([
					$TrainingCoursesProviders->aliasField('training_course_id') => $courseId
				])
				->all();

			$providerOptions = [];
			foreach ($providers as $provider) {
				$providerOptions[$provider->_matchingData['TrainingProviders']->id] = $provider->_matchingData['TrainingProviders']->name;
			}
			$attr['options'] = $providerOptions;
		}

		return $attr;
	}

	public function onUpdateFieldTrainers(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$Users = $this->Trainers->Users;
			$trainerOptions = $Users
				->find('list', ['keyField' => 'id', 'valueField' => 'name_with_id'])
				->where([
					$Users->aliasField('is_student') => 0,
					$Users->aliasField('is_staff') => 0,
					$Users->aliasField('is_guardian') => 0
				])
				->toArray();
			$trainerOptions = ['' => '-- ' . __('Select Trainer') . ' --'] + $trainerOptions;

			$attr['options'] = $trainerOptions;
		}

		return $attr;
	}

	public function getTrainingSession($id=null) {
		if (!is_null($id)) {
			return $results = $this
				->find()
				->contain('Courses.ResultTypes')
				->matching('Statuses')
				->matching('TrainingProviders')
				->where([
					$this->aliasField('id') => $id
				])
				->first();
		}

		return null;
	}

	public function setupFields(Event $event, Entity $entity) {
		$fieldOrder = [
			'training_course_id', 'training_provider_id',
			'code', 'name', 'start_date', 'end_date', 'comment',
			'trainers'
		];

		$this->ControllerAction->field('training_course_id', [
			'type' => 'select'
		]);
		$this->ControllerAction->field('training_provider_id', [
			'type' => 'select',
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('trainers', [
			'type' => 'element',
			'element' => 'Training.Sessions/trainers',
			'valueClass' => 'table-full-width'
		]);

		if (isset($entity->id)) {

			/**
			 * Import field variables
			 */
			$comment = __('* Format Supported: ' . implode(', ', array_keys($this->fileTypesMap)));
			$comment .= '<br/>';
			$comment .= __('* Recommended Maximum File Size: ' . $this->bytesToReadableFormat($this->MAX_SIZE));
			$comment .= '<br/>';
			$comment .= __('* Recommended Maximum Records: ' . $this->MAX_ROWS);
			$data = $event->subject()->request->data;
			if ((is_object($data) && $data->offsetExists('trainees_import_error')) || (is_array($data) && isset($data['trainees_import_error']))) {
				$entity->errors('trainees_import', $data['trainees_import_error']);
			}
			/**
			 * End Import field variables
			 */

			// this is a fake field to make the form render with an "enctype"
			$this->ControllerAction->field('trainees_fake_field', ['type' => 'binary', 'visible'=>false]);

			$this->ControllerAction->field('trainees', [
				'type' => 'trainee_table',
				'valueClass' => 'table-full-width',
				'comment' => $comment
			]);
			$fieldOrder[] = 'trainees';
		}

		$this->ControllerAction->setFieldOrder($fieldOrder);
	}


/******************************************************************************************************************
**
** Import Functions
**
******************************************************************************************************************/
	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Model.custom.onUpdateToolbarButtons'] = ['callable' => 'onUpdateToolbarButtons', 'priority' => 1];
		$events['ControllerAction.Model.addEdit.onMassAddTrainees'] = ['callable' => 'addEditOnMassAddTrainees'];
		return $events;
	}

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		switch ($action) {
			case 'edit':
				$toolbarButtons['import'] = $toolbarButtons['back'];
				$toolbarButtons['import']['url'][0] = 'template';
				$toolbarButtons['import']['attr']['title'] = __('Download Template');
				$toolbarButtons['import']['label'] = '<i class="fa kd-download"></i>';
				break;
		}
	}

	public function template() {
		// prepareDownload() resides in ImportTrait
		$folder = $this->prepareDownload();
		// Do not localize file name as certain non-latin characters might cause issue 
		$excelFile = 'OpenEMIS_Core_Import_Training_Session_Trainees.xlsx';
		$excelPath = $folder . DS . $excelFile;

		$header = ['OpemEMIS ID'];
		$dataSheetName = __('Training Session Trainees');

		$objPHPExcel = new \PHPExcel();
		$autoTitle = false;
		$titleColumn = 'F';
		// setImportDataTemplate() resides in ImportTrait
		$this->setImportDataTemplate( $objPHPExcel, $dataSheetName, $header, $autoTitle, $titleColumn );

		$objPHPExcel->setActiveSheetIndex(0);
		$objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
		$objWriter->save($excelPath);

		// performDownload() resides in ImportTrait
		$this->performDownload($excelFile);
		die;
	}

	public function addEditOnMassAddTrainees(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {

		$request = $event->subject()->request;
		$model = $this;
		$alias = $model->alias();
		$key = 'trainees';
		$error = '';
		// MAX_SIZE resides in ImportTrait
		if ($request->env('CONTENT_LENGTH') >= $this->MAX_SIZE) {
			$error = $model->getMessage('Import.over_max');
		} 
		// file_upload_max_size() resides in ImportTrait
		if ($request->env('CONTENT_LENGTH') >= $this->file_upload_max_size()) {
			$error = $model->getMessage('Import.over_max');
		} 
		if ($request->env('CONTENT_LENGTH') >= $this->post_upload_max_size()) {
			$error = $model->getMessage('Import.over_max');
		}
		if (!array_key_exists($alias, $data)) {
			$error = $model->getMessage('Import.not_supported_format');	
		}
		if (!array_key_exists('trainees_import', $data[$alias])) {
			$error = $model->getMessage('Import.not_supported_format');
		}
		if (empty($data[$alias]['trainees_import'])) {
			$error = $model->getMessage('Import.not_supported_format');
		}
		if ($data[$alias]['trainees_import']['error']==4) {
			$error = $model->getMessage('Import.not_supported_format');
		}
		if ($data[$alias]['trainees_import']['error']>0) {
			$error = $model->getMessage('Import.over_max');
		}

		$fileObj = $data[$alias]['trainees_import'];
		// fileTypesMap resides in ImportTrait
		$supportedFormats = $this->fileTypesMap;

		$finfo = finfo_open(FILEINFO_MIME_TYPE);
		$fileFormat = finfo_file($finfo, $fileObj['tmp_name']);
		finfo_close($finfo);
		$formatFound = false;
		foreach ($supportedFormats as $eachformat) {
			if (in_array($fileFormat, $eachformat)) {
				$formatFound = true;
			} 
		}
		if (!$formatFound) {
			if (!empty($fileFormat)) {
				$error = $model->getMessage('Import.not_supported_format');
			}
		}				

		$fileExt = $fileObj['name'];
		$fileExt = explode('.', $fileExt);
		$fileExt = $fileExt[count($fileExt)-1];
		if (!array_key_exists($fileExt, $supportedFormats)) {
			if (!empty($fileFormat)) {
				$error = $model->getMessage('Import.not_supported_format');
			}
		}

		if (!empty($error)) {
			$data['trainees_import_error'] = $error;
		} else {

			$controller = $model->controller;
			$controller->loadComponent('PhpExcel');
			$columns = ['trainees_import'];
			$header = ['OpemEMIS ID'];

			$fileObj = $data[$alias]['trainees_import'];
			$uploaded = $fileObj['tmp_name'];
			$objPHPExcel = $controller->PhpExcel->loadWorksheet($uploaded);

			$maxRows = $this->MAX_ROWS;
			$maxRows = $maxRows + 3;
			$sheet = $objPHPExcel->getSheet(0);
			$totalColumns = 1;
			$highestRow = $sheet->getHighestRow();
			if ($highestRow > $maxRows) {
				$data['trainees_import_error'] = $model->getMessage('Import.over_max_rows');
				return $event->response;
			}

			$TargetPopulations = TableRegistry::get('Training.TrainingCoursesTargetPopulations');
			$Staff = TableRegistry::get('Institution.Staff');
			$Users = TableRegistry::get('User.Users');
			$Positions = TableRegistry::get('Institution.InstitutionPositions');
			
			$targetPopulationIds = $TargetPopulations
				->find('list', ['keyField' => 'target_population_id', 'valueField' => 'target_population_id'])
				->where([$TargetPopulations->aliasField('training_course_id') => $entity->training_course_id])
				->toArray();
			$trainees = new Collection($data[$alias][$key]);
			$traineeIds = $trainees->extract('_joinData.openemis_no');
			$traineeIds = $traineeIds->toArray();

			for ($row = 2; $row <= $highestRow; ++$row) {
				if ($row == $this->RECORD_HEADER) { // skip header but check if the uploaded template is correct
					if (!$this->isCorrectTemplate($header, $sheet, $totalColumns, $row)) {
						$data['trainees_import_error'] = $model->getMessage('Import.wrong_template');
						return $event->response;
					}
					continue;
				}
				if ($row == $highestRow) { // if $row == $highestRow, check if the row cells are really empty, if yes then end the loop
					if ($this->checkRowCells($sheet, $totalColumns, $row) === false) {
						break;
					}
				}
					
				$cell = $sheet->getCellByColumnAndRow(0, $row);
				$openemis_no = $cell->getValue();
				if (empty($openemis_no)) {
					continue;
				}
				if (in_array($openemis_no, $traineeIds)) {
					continue;
				}
				$trainee = $Staff
							->find()
							->matching('Users', function($q) use ($openemis_no) {
								return $q
									->find('all')
									->where(['Users.openemis_no' => $openemis_no])
									;
							})
							->matching('Positions', function($q) use ($targetPopulationIds) {
								return $q
									->find('all')
									->where([
										'Positions.staff_position_title_id IN' => $targetPopulationIds
									]);
							})
							->group([
								$Staff->aliasField('staff_id')
							])
							->order([$Users->aliasField('first_name')])
							->first();

				if ($trainee) {
					if (!array_key_exists($key, $data[$alias])) {
						$data[$alias][$key] = [];
					}
					$data[$alias][$key][$openemis_no] = [
						'id' => $trainee->_matchingData['Users']->id,
						'_joinData' => ['openemis_no' => $openemis_no, 'trainee_id' => $trainee->_matchingData['Users']->id, 'name' => $trainee->name]
					];
				} else {
					// $model->log(__CLASS__.'->'.__METHOD__ . ': Record not found for id: ' . $openemis_no, 'debug');
				}
			}
		}
	}
/******************************************************************************************************************
** End Import Functions
******************************************************************************************************************/

}
