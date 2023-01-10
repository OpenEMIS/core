<?php
namespace Training\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\Log\Log;
use App\Model\Table\ControllerActionTable;

class TrainingSessionResultsTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public $openStatusIds = [];
    public $approvedStatusIds = [];
    public $resultTypeOptions = [];

    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
        $this->belongsTo('TrainingSessions', ['className' => 'Training.TrainingSessions']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'assignee_id']);
        $this->addBehavior('User.AdvancedNameSearch');
        $this->toggle('add', true);
        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportTrainingSessionTraineeResults']);//5695
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $request = $this->request;
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $query
            ->contain([
                'Sessions.Courses' => [
                    'fields' => [
                        'id',
                        'name'
                        ]
                    ],
                'Sessions.TrainingProviders' => [
                    'fields' => [
                        'id',
                        'name'
                        ]
                    ]
                ]);
            $orConditions = [
             'TrainingSessions.name LIKE' => $search.'%',
             'Courses.name LIKE' => $search.'%',
             'TrainingProviders.name LIKE' => $search.'%'
        ];
        // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search,'OR' => $orConditions]);
        }
    }

	public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
	{   
        $process = function($model, $entity) use ($data) {
        	$sessionId = $data[$model->alias()]['training_session_id'];
			$resultTypeId = $data[$model->alias()]['result_type'];
			$trainees = array_key_exists('trainees', $data[$model->alias()]) ? $data[$model->alias()]['trainees'] : [];

			$newEntities = [];
			$deleteIds = [];
			foreach ($trainees as $key => $trainee) {
				if (strlen($trainee['result']) > 0) {
					$resultData = [
						'result' => $trainee['result'],
                        'attendance_days' => $trainee['attendance_days'],//5695
                        'certificate_number' => $trainee['certificate_number'],//5695
                        'practical' => $trainee['practical'],//5695
						//'training_result_type_id' => $resultTypeId, //5695
						// POCOR 6585 starts (i have reopen pocor 5695 comment code because it was giving error so i have set default value 0 in training_result_type_id when $resultTypeId variable empty)
						'training_result_type_id' => isset($resultTypeId) ? $resultTypeId : 0,
						// POCOR 6585 ends
						'trainee_id' => $trainee['trainee_id'],
						'training_session_id' => $sessionId,
						'counterNo' => $key
					];
                    
					if (isset($trainee['id'])) {
						$resultData['id'] = $trainee['id'];
					}

					$newEntities[] = $resultData;
				} else {
					if (isset($trainee['id'])) {
						$deleteIds[$trainee['id']] = $trainee['id'];
					}
				}
			}
           
			$success = $this->connection()->transactional(function() use ($newEntities, $entity) { 
                $return = true;
                foreach ($newEntities as $key => $newData) {
                    $TraineeResults = TableRegistry::get('Training.TrainingSessionTraineeResults');
                    $newEntity = $TraineeResults->newEntity($newData);
                    if ($newEntity->errors('result')) {
                        $counterNo = $newData['counterNo'];
                        $entity->trainees[$counterNo]['errors'] = $newEntity->errors();

                        $entity->errors('trainees', ['result' => $newEntity->errors('result')]);
                    }
                    //5695 starts
                    if ($newEntity->errors('attendance_days')) {
                        $counterNo = $newData['counterNo'];
                        $entity->trainees[$counterNo]['errors']['attendance_days'] = $newEntity->errors();
                        
                        $entity->errors('trainees', ['attendance_days' => $newEntity->errors('attendance_days')]);
                    }

                    if ($newEntity->errors('certificate_number')) {
                        $counterNo = $newData['counterNo'];
                        $entity->trainees[$counterNo]['errors'] = $newEntity->errors();

                        $entity->errors('trainees', ['certificate_number' => $newEntity->errors('certificate_number')]);
                    }

                    if ($newEntity->errors('practical')) {
                        $counterNo = $newData['counterNo'];
                        $entity->trainees[$counterNo]['errors'] = $newEntity->errors();

                        $entity->errors('trainees', ['practical' => $newEntity->errors('practical')]);
                    }//5695 ends

                    if (!$TraineeResults->save($newEntity)) {
                        $return = false;
                    }
                }

                return $return;
            });

            if ($success) { 
            	if (!empty($deleteIds)) {
            		$TraineeResults = TableRegistry::get('Training.TrainingSessionTraineeResults');
            		$TraineeResults->deleteAll([
						$TraineeResults->aliasField('id IN ') => $deleteIds
					]);
            	}

				return true;
            } else {
            	return false;
            }
        };

        return $process;
	}

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        // To manually clear all records in training_session_trainee_results when delete
        $TraineeResults = TableRegistry::get('Training.TrainingSessionTraineeResults');
        $TraineeResults->deleteAll([
            $TraineeResults->aliasField('training_session_id') => $entity->training_session_id
        ]);
        // End
    }

	public function onGetTrainingCourse(Event $event, Entity $entity)
	{
		$trainingSession = $this->Sessions->getTrainingSession($entity->training_session_id);
		return $trainingSession->course->name;
	}

	public function onGetTrainingProvider(Event $event, Entity $entity)
	{
		$trainingSession = $this->Sessions->getTrainingSession($entity->training_session_id);
		return $trainingSession->_matchingData['TrainingProviders']->name;
	}

	public function onGetResultType(Event $event, Entity $entity)
	{
		$html = '';

		$Form = $event->subject()->Form;
		$url = [
			'plugin' => $this->request->params['plugin'],
		    'controller' => $this->request->params['controller'],
		    'action' => $this->request->params['action']
		];
		if (!empty($this->request->pass)) {
			$url = array_merge($url, $this->request->pass);
		}
		$dataNamedGroup = [];
		if (!empty($this->request->query)) {
			foreach ($this->request->query as $key => $value) {
				if (in_array($key, ['result_type'])) continue;
				echo $Form->hidden($key, [
					'value' => $value,
					'data-named-key' => $key
				]);
				$dataNamedGroup[] = $key;
			}
		}
		$baseUrl = $event->subject()->Url->build($url);

		$inputOptions = [
			'class' => 'form-control',
			'label' => false,
			'options' => $this->resultTypeOptions,
			'url' => $baseUrl,
			'data-named-key' => 'result_type',
			'escape' => false
		];
		if (!empty($dataNamedGroup)) {
			$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
			$dataNamedGroup[] = 'result_type';
		}

		$fieldPrefix = $this->alias();
        $html = $Form->input($fieldPrefix.".result_type", $inputOptions);

		return $html;
	}

	public function onGetTraineeTableElement(Event $event, $action, $entity, $attr, $options=[])
	{ 
        $sessionId = $entity->training_session_id;
		//$selectedResultType = $this->request->query('result_type'); //5695 starts
        
        //5695 starts
        $TraineesSessions = TableRegistry::get('training_sessions');
        $TraineesSessions = $TraineesSessions->find()->where([
                                        $TraineesSessions->aliasField('id') => $sessionId
                                    ])->first();
        if(!empty($TraineesSessions)){
            $TrainingCoursesResultTypes = TableRegistry::get('training_courses_result_types');
            $TrainingResultTypes = TableRegistry::get('training_result_types');

            $TrainingCoursesResultTypes1 = $TrainingCoursesResultTypes
                                            ->find()
                                            ->where([
                                                $TrainingCoursesResultTypes->aliasField('training_course_id') => $TraineesSessions->training_course_id
                                            ])->toArray();
            $TrainingCoursesResultArr = [];
            $TrainingResultTypesArr = [];
            if(!empty($TrainingCoursesResultTypes1)){
                foreach ($TrainingCoursesResultTypes1 as $value) {
                    $TrainingCoursesResultArr[] = $value->training_result_type_id;
                }

                if($TrainingCoursesResultArr){
                    $TrainingResultTypesArr = $TrainingResultTypes
                                            ->find('list', ['keyField' => 'id', 'valueField'=>'name'])
                                            ->where([
                                                $TrainingResultTypes->aliasField('id IN') => $TrainingCoursesResultArr
                                            ])->toArray();
                }
            }
        }

		$tableHeaders = [__('OpenEMIS No'), __('Name'), __('Exam')]; //5695
        if(in_array('Practical', $TrainingResultTypesArr)){
            $tableHeaders[] = __('Practical'); //5695
        }
        if(in_array('Attendance', $TrainingResultTypesArr)){
            $tableHeaders[] = __('Attendance Days');
        }
        if(in_array('Certificate', $TrainingResultTypesArr)){
            $tableHeaders[] = __('Certificate'); //5695
        }
        //5695 ends       
		$tableCells = [];
		$alias = $this->alias();
		$key = 'trainees';
        $selectedResultType = array_values($TrainingCoursesResultArr);

		$trainees = [];
		if (!is_null($selectedResultType)) {
			$SessionsTrainees = TableRegistry::get('Training.TrainingSessionsTrainees');
			$TraineeResults = TableRegistry::get('Training.TrainingSessionTraineeResults');

			$query = $SessionsTrainees
				->find()
				->matching('Trainees')
				->select([
					$TraineeResults->aliasField('id'),
					$TraineeResults->aliasField('result'),
                    $TraineeResults->aliasField('attendance_days'), //5695
                    $TraineeResults->aliasField('certificate_number'), //5695
                    $TraineeResults->aliasField('practical'), //5695
					$TraineeResults->aliasField('training_result_type_id')
				])
				->leftJoin(
					[$TraineeResults->alias() => $TraineeResults->table()],
					[
						$TraineeResults->aliasField('trainee_id = ') . $SessionsTrainees->aliasField('trainee_id'),
						$TraineeResults->aliasField('training_session_id') => $sessionId,
						//$TraineeResults->aliasField('training_result_type_id IN') => $selectedResultType
					]
				)
				->where([
					$SessionsTrainees->aliasField('training_session_id') => $sessionId
				])
				->group([
					$SessionsTrainees->aliasField('trainee_id')
				])
				->autoFields(true);

			$trainees = $query->toArray();

			if (empty($trainees)) {
		  		$this->Alert->warning($this->aliasField('noTrainees'));
		  	}
		}

		if ($action == 'view') {
            foreach ($trainees as $i => $obj) {
				$traineeObj = $obj->_matchingData['Trainees'];
				$traineeResult = $obj->{$TraineeResults->alias()};

				$rowData = [];
				$rowData[] = $event->subject()->Html->link($traineeObj->openemis_no , [
					'plugin' => 'Directory',
                    'controller' => 'Directories',
                    'action' => 'Directories',
                    'view',
                    $this->paramsEncode(['id' => $traineeObj->id])
				]);
				$rowData[] = $traineeObj->name;
                //5695 starts
                $rowData[] = strlen($traineeResult['result']) ? $traineeResult['result'] : '';
                if(in_array('Practical', $TrainingResultTypesArr)){
                    $rowData[] = strlen($traineeResult['practical']) ? $traineeResult['practical'] : ''; //5695
                }
				if(in_array('Attendance', $TrainingResultTypesArr)){
                    $rowData[] = strlen($traineeResult['attendance_days']) ? $traineeResult['attendance_days'] : ''; //5695
                }
                if(in_array('Certificate', $TrainingResultTypesArr)){
                    $rowData[] = strlen($traineeResult['certificate_number']) ? $traineeResult['certificate_number'] : ''; //5695
                }//5695 end
				$tableCells[] = $rowData;
			}
		} else {
            $Form = $event->subject()->Form;

			foreach ($trainees as $i => $obj) {
				$fieldPrefix = $alias . '.' . $key . '.' . $i;
				$traineeObj = $obj->_matchingData['Trainees'];
				$traineeResult = $obj->{$TraineeResults->alias()};

				$rowData = [];
				$name = $traineeObj->name;
				$name .= $Form->hidden("$fieldPrefix.trainee_id", ['value' => $traineeObj->id]);
                if ($entity->submit == 'save') { //if come from save process.
                    $result = $Form->input("$fieldPrefix.result", ['label' => false, 'value' => $entity->trainees[$i]['result']]);
                    if (array_key_exists('errors', $entity->trainees[$i])) {
                        $result .= "<div class='error-message'>";
                        $errors = [];
                        //flattern 2 dimensional array to cater more than one error returned
                        array_walk_recursive($entity->trainees[$i]['errors'], function($v, $k) use (&$errors){ $errors[] = $v; });
                        foreach ($errors as $value) {
                            $result .= $value;
                        }
                        $result .= "</div>";
                    }
                    //5695 starts for attandance days
                    if(in_array('Attendance', $TrainingResultTypesArr)){
                        $attendance_days = $Form->input("$fieldPrefix.attendance_days", ['label' => false, 'value' => $entity->trainees[$i]['attendance_days']]);
                        if (array_key_exists('errors', $entity->trainees[$i])) {
                            $attendance_days .= "<div class='error-message'>";
                            $errors = [];
                            //flattern 2 dimensional array to cater more than one error returned
                            array_walk_recursive($entity->trainees[$i]['errors'], function($v, $k) use (&$errors){ $errors[] = $v; });
                            foreach ($errors as $value) {
                                $attendance_days .= $value;
                            }
                            $attendance_days .= "</div>";
                        }
                    }
                    
                    //5695 ends for attandance days
                    //5695 start for certificate number
                    if(in_array('Certificate', $TrainingResultTypesArr)){
                        $certificate_number = $Form->input("$fieldPrefix.certificate_number", ['label' => false, 'value' => $entity->trainees[$i]['certificate_number']]);
                        if (array_key_exists('errors', $entity->trainees[$i])) {
                            $certificate_number .= "<div class='error-message'>";
                            $errors = [];
                            //flattern 2 dimensional array to cater more than one error returned
                            array_walk_recursive($entity->trainees[$i]['errors'], function($v, $k) use (&$errors){ $errors[] = $v; });
                            foreach ($errors as $value) {
                                $certificate_number .= $value;
                            }
                            $certificate_number .= "</div>";
                        }
                    } //5695 ends for certificate number
                    //5695 start for Practical
                    if(in_array('Practical', $TrainingResultTypesArr)){
                        $practical = $Form->input("$fieldPrefix.practical", ['label' => false, 'value' => $entity->trainees[$i]['practical']]);
                        if (array_key_exists('errors', $entity->trainees[$i])) {
                            $practical .= "<div class='error-message'>";
                            $errors = [];
                            //flattern 2 dimensional array to cater more than one error returned
                            array_walk_recursive($entity->trainees[$i]['errors'], function($v, $k) use (&$errors){ $errors[] = $v; });
                            foreach ($errors as $value) {
                                $practical .= $value;
                            }
                            $practical .= "</div>";
                        }
                    } //5695 ends for Practical  
                } else {
                    $result = $Form->input("$fieldPrefix.result", ['label' => false, 'value' => $traineeResult['result']]);

                    //5695 start 
                    if(in_array('Practical', $TrainingResultTypesArr)){
                        $practical = $Form->input("$fieldPrefix.practical", ['label' => false, 'value' => $traineeResult['practical']]);
                    }
                    if(in_array('Attendance', $TrainingResultTypesArr)){
                        $attendance_days = $Form->input("$fieldPrefix.attendance_days", ['label' => false, 'value' => $traineeResult['attendance_days']]);
                    }
                    if(in_array('Certificate', $TrainingResultTypesArr)){
                        $certificate_number = $Form->input("$fieldPrefix.certificate_number", ['label' => false, 'value' => $traineeResult['certificate_number']]);
                    }
                    //5695 end
                }

				if (isset($traineeResult['id'])) {
					$result .= $Form->hidden("$fieldPrefix.id", ['value' => $traineeResult['id']]);
                }

				$rowData[] = $traineeObj->openemis_no;
				$rowData[] = $name;
				$rowData[] = $result;
                
                if(in_array('Practical', $TrainingResultTypesArr)){
                    $rowData[] = $practical; //5695
                }
                if(in_array('Attendance', $TrainingResultTypesArr)){
                    $rowData[] = $attendance_days; //5695
                }
                if(in_array('Certificate', $TrainingResultTypesArr)){
                    $rowData[] = $certificate_number; //5695
                }
                $tableCells[] = $rowData;
			}
		}

	  	$attr['tableHeaders'] = $tableHeaders;
    	$attr['tableCells'] = $tableCells;

		return $event->subject()->renderElement('Training.Results/' . $key, ['attr' => $attr]);
	}

	public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->openStatusIds = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'OPEN');
        $this->approvedStatusIds = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'APPROVED');
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->buildRecords();

        $this->field('training_course');
        $this->field('training_provider');

        $this->setFieldOrder([
            'training_course', 'training_provider', 'training_session_id'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit') {
            $statusOptions = $this->getWorkflowStepList();
            if (isset($attr['attr']['value'])) {
                $statusId = $attr['attr']['value'];

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $statusOptions[$statusId];
            }
        }

        return $attr;
    }

    public function onUpdateFieldTrainingCourse(Event $event, array $attr, $action, $request)
    {
        if ($action == 'view') {
            // refer onGetTrainingCourse
        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
            if (isset($attr['attr']['value'])) {
                $sessionId = $attr['attr']['value'];
                $trainingSession = $this->Sessions->getTrainingSession($sessionId);

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $trainingSession->course->name;
            }
        }

        return $attr;
    }

    public function onUpdateFieldTrainingProvider(Event $event, array $attr, $action, $request)
    {
        if ($action == 'view') {
            // refer onGetTrainingProvider
        } else if ($action == 'edit') {
            $attr['type'] = 'readonly';
            if (isset($attr['attr']['value'])) {
                $sessionId = $attr['attr']['value'];
                $trainingSession = $this->Sessions->getTrainingSession($sessionId);

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $trainingSession->_matchingData['TrainingProviders']->name;
            }
        }

        return $attr;
    }

    public function onUpdateFieldTrainingSessionId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'view') {
            $attr['type'] = 'select';
        } else if ($action == 'edit') {
            $sessionOptions = $this->Training->getSessionList(['listAll' => true]);
            if (isset($attr['attr']['value'])) {
                $sessionId = $attr['attr']['value'];

                $attr['type'] = 'readonly';
                $attr['attr']['value'] = $sessionOptions[$sessionId];
            }
        }

        return $attr;
    }

    public function onUpdateFieldResultType(Event $event, array $attr, $action, $request)
    {
        $resultTypeOptions = [];

        if (isset($attr['attr']['value'])) {
            $sessionId = $attr['attr']['value'];
            $resultTypeOptions = $this->getResultTypeOptions($sessionId);
        }

        if (empty($resultTypeOptions )) {
            $this->Alert->warning($this->aliasField('noResultTypes'));
        } else {
            $selectedResultType = $this->queryString('result_type', $resultTypeOptions);
            $this->advancedSelectOptions($resultTypeOptions, $selectedResultType);
        }

        if ($action == 'view') {
            $this->resultTypeOptions = $resultTypeOptions;
            $attr['valueClass'] = 'table-full-width';
        } else if ($action == 'edit') {
            $attr['type'] = 'select';
            $attr['attr']['options'] = $resultTypeOptions;
            $attr['onChangeReload'] = 'changeResultType';
        }

        return $attr;
    }

    public function editOnChangeResultType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['result_type']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('result_type', $request->data[$this->alias()])) {
                    $request->query['result_type'] = $request->data[$this->alias()]['result_type'];
                }
            }
            $data[$this->alias()]['trainees'] = [];
            $data[$this->alias()]['status_id'] = $entity->status_id;
        }
    }

    public function buildRecords($sessionId = null)
    {
        $sessions = $this->Training->getSessionList();

        $openStatusId = null;
        $workflow = $this->getWorkflow($this->registryAlias());
        if (!empty($workflow)) {
            foreach ($workflow->workflow_steps as $workflowStep) {
                if ($workflowStep->category == self::TO_DO && $workflowStep->is_system_defined == 1) {
                    $openStatusId = $workflowStep->id;
                    break;
                }
            }

            foreach ($sessions as $sessionId => $session) {
                $where = [
                    $this->aliasField('training_session_id') => $sessionId
                ];

                $results = $this
                    ->find('all')
                    ->where($where)
                    ->all();

                if ($results->isEmpty()) {
                    // Insert New Records if not found
                    $data = [
                        'status_id' => $openStatusId,
                        'training_session_id' => $sessionId
                    ];

                    $entity = $this->newEntity($data, ['validate' => false]);
                    if ($this->save($entity)) {
                    } else {
                        $this->log($entity->errors(), 'debug');
                    }
                }
            }
        }
    }

    public function getResultTypeOptions($id = null)
    {
        $list = [];

        if (!is_null($id)) {
            $trainingSession = $this->Sessions->getTrainingSession($id);
            foreach ($trainingSession->course->result_types as $key => $obj) {
                $list[$obj->id] = $obj->name;
            }
        }

        return $list;
    }

    public function setupFields(Entity $entity)
    {
        $this->field('status', [
            'visible' => ['index' => false, 'view' => false, 'edit' => true],
            'attr' => ['value' => $entity->status_id]
        ]);
        $this->field('training_course', [
            'attr' => ['value' => $entity->training_session_id]
        ]);
        $this->field('training_provider', [
            'attr' => ['value' => $entity->training_session_id]
        ]);
        /*$this->field('result_type', [
            'attr' => ['value' => $entity->training_session_id]
        ]);*/
        $this->field('training_session_id', [
            'attr' => ['value' => $entity->training_session_id]
        ]);
        $this->field('trainees', [
            'type' => 'trainee_table',  // custom type
            'valueClass' => 'table-full-width'
        ]);

        $this->setFieldOrder([
            'status', 'training_course', 'training_provider', 'training_session_id', 'result_type', 'trainees'
        ]);
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('training_session_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Sessions->aliasField('code'),
                $this->Sessions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Sessions->alias(), $this->CreatedUser->alias(),'Assignees'])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId,
                'Assignees.super_admin IS NOT'=>1]) //POCOR-7102
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {

                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Training',
                        'controller' => 'Trainings',
                        'action' => 'Results',
                        'view',
                        $this->paramsEncode(['id' => $row->id])
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('Results of %s'), $row->session->code_name);
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    //POCOR-6925
    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $workflowModel = 'Administration > Training > Results';
            $workflowModelsTable = TableRegistry::get('workflow_models');
            $workflowStepsTable = TableRegistry::get('workflow_steps');
            $Workflows = TableRegistry::get('Workflow.Workflows');
            $workModelId = $Workflows
                            ->find()
                            ->select(['id'=>$workflowModelsTable->aliasField('id'),
                            'workflow_id'=>$Workflows->aliasField('id'),
                            'is_school_based'=>$workflowModelsTable->aliasField('is_school_based')])
                            ->LeftJoin([$workflowModelsTable->alias() => $workflowModelsTable->table()],
                                [
                                    $workflowModelsTable->aliasField('id') . ' = '. $Workflows->aliasField('workflow_model_id')
                                ])
                            ->where([$workflowModelsTable->aliasField('name')=>$workflowModel])->first();
            $workflowId = $workModelId->workflow_id;
            $isSchoolBased = $workModelId->is_school_based;
            $workflowStepsOptions = $workflowStepsTable
                            ->find()
                            ->select([
                                'stepId'=>$workflowStepsTable->aliasField('id'),
                            ])
                            ->where([$workflowStepsTable->aliasField('workflow_id') => $workflowId])
                            ->first();
            $stepId = $workflowStepsOptions->stepId;
            $session = $request->session();
            if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
            }
            $institutionId = $institutionId;
            $assigneeOptions = [];
            if (!is_null($stepId)) {
                $WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
                $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
                if (!empty($stepRoles)) {
                    $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                    $Areas = TableRegistry::get('Area.Areas');
                    $Institutions = TableRegistry::get('Institution.Institutions');
                    if ($isSchoolBased) {
                        if (is_null($institutionId)) {                        
                            Log::write('debug', 'Institution Id not found.');
                        } else {
                            $institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $institutionId])->contain(['Areas'])->first();
                            $securityGroupId = $institutionObj->security_group_id;
                            $areaObj = $institutionObj->area;
                            // School based assignee
                            $where = [
                                'OR' => [[$SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId],
                                        ['Institutions.id' => $institutionId]],
                                $SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles
                            ];
                            $schoolBasedAssigneeQuery = $SecurityGroupUsers
                                    ->find('userList', ['where' => $where])
                                    ->leftJoinWith('SecurityGroups.Institutions');
                            $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();
                            
                            // Region based assignee
                            $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                            $regionBasedAssigneeQuery = $SecurityGroupUsers
                                        ->find('UserList', ['where' => $where, 'area' => $areaObj]);
                            
                            $regionBasedAssigneeOptions = $regionBasedAssigneeQuery->toArray();
                            // End
                            $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;
                        }
                    } else {
                        $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                        $assigneeQuery = $SecurityGroupUsers
                                ->find('userList', ['where' => $where])
                                ->order([$SecurityGroupUsers->aliasField('security_role_id') => 'DESC']);
                        $assigneeOptions = $assigneeQuery->toArray();
                    }
                }
            }
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['' => '-- ' . __('Select Assignee') . ' --'] + $assigneeOptions;
            $attr['onChangeReload'] = 'changeStatus';
            return $attr;
        }
    }
}
