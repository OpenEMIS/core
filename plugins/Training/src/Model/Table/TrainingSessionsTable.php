<?php
namespace Training\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use App\Model\Traits\OptionsTrait;
use App\Model\Traits\HtmlTrait;
use Cake\Collection\Collection;
use Cake\Routing\Router;
use Cake\Log\Log;
use Import\Model\Traits\ImportExcelTrait;

use App\Model\Table\ControllerActionTable;

class TrainingSessionsTable extends ControllerActionTable
{
    use OptionsTrait;
    use HtmlTrait;
    use ImportExcelTrait;

    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    const INTERNAL = 'INTERNAL';
    const EXTERNAL = 'EXTERNAL';

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_course_id']);
        $this->belongsTo('TrainingProviders', ['className' => 'Training.TrainingProviders', 'foreignKey' => 'training_provider_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        // revert back the association for Trainers to hasMany to handle saving of External Trainers
        $this->hasMany('Trainers', ['className' => 'Training.TrainingSessionTrainers', 'foreignKey' => 'training_session_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('TrainingApplications', ['className' => 'Training.TrainingApplications', 'foreignKey' => 'training_session_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('SessionResults', ['className' => 'Training.TrainingSessionResults', 'foreignKey' => 'training_session_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('TraineeResults', ['className' => 'Training.TrainingSessionTraineeResults', 'foreignKey' => 'training_session_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('Trainees', [
            'className' => 'User.Users',
            'joinTable' => 'training_sessions_trainees',
            'foreignKey' => 'training_session_id',
            'targetForeignKey' => 'trainee_id',
            'through' => 'Training.TrainingSessionsTrainees',
            'dependent' => false
        ]);
        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->setDeleteStrategy('restrict');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        $this->addBehavior('Import.ImportLink');
    }

    public function validationDefault(Validator $validator)
    {
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

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
        // Type / Visible
        $visible = ['index' => false, 'view' => true, 'edit' => true, 'add' => true];
        $this->field('end_date', ['visible' => $visible]);
        $this->field('comment', ['visible' => $visible]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder([
            'code', 'name', 'start_date', 'end_date', 'training_course_id', 'training_provider_id'
        ]);

        
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
                'Trainers' => [
                    'Users',
                    'sort' => ['Trainers.type' => 'DESC', 'Users.first_name' => 'ASC', 'Users.last_name' => 'ASC', 'Trainers.name' => 'ASC']
                ],
                'Trainees' => [
                    'sort' => ['Trainees.first_name' => 'ASC', 'Trainees.last_name' => 'ASC']
                ]
            ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        //Required by patchEntity for associated data
        // _joinData is required for 'saveStrategy' => 'replace' to work
        // Trainers and Trainees will not be validated since they are User.Users model and only their id is included so that
        // it will not be treated as a new record.
        $newOptions = [];
        $newOptions = [
            'associated' => [
                'Trainers',
                'Trainees' => ['validate' => false],
                'Trainees._joinData'
            ],
        ];

        $arrayOptions = $options->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $options->exchangeArray($arrayOptions);

        // POCOR-2491: During edit, if there are more than one trainees and when all trainees were removed at the same time,
        // "trainees" array will not be included in $data. We have to manually add it so that 'saveStrategy' => 'replace' will work
        // The same behavior occured on trainers.
        // Additional logic written for trainers array is to add "id" parameter outside of each "_joinData" array so that each record
        // will not be treated as a new User.Users record.
        // Including the "id" parameter on the web form needs extra javascript or a page reload method to work since the trainers is selected
        // through a dropdown input.
        if ($data->offsetExists('TrainingSessions')) {
            if (!isset($data['TrainingSessions']['trainees'])) {
                $data['TrainingSessions']['trainees'] = [];
            }
            if (!isset($data['TrainingSessions']['trainers'])) {
                $data['TrainingSessions']['trainers'] = [];
            }
        }
    }

    public function addEditOnChangeCourse(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
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

    public function addEditOnAddTrainer(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->alias();
        $fieldKey = 'trainers';

        if (empty($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        if ($data->offsetExists($alias)) {
            if (array_key_exists('trainer_id', $data[$alias]) && !empty($data[$alias]['trainer_id'])) {
                // Internal Trainer
                $id = $data[$alias]['trainer_id'];

                try {
                    $obj = $this->Trainers->Users->get($id);

                    $data[$alias][$fieldKey][] = [
                        'type' => self::INTERNAL,
                        'trainer_id' => $obj->id,
                        'name' => '',
                        'trainer_name' => $obj->name_with_id
                    ];

                    $data[$alias]['trainer_id'] = '';
                } catch (RecordNotFoundException $ex) {
                    Log::write('debug', __METHOD__ . ': Record not found for id: ' . $id);
                }
            } else {
                // External Trainer
                $data[$alias][$fieldKey][] = [
                    'type' => self::EXTERNAL,
                    'trainer_id' => '',
                    'name' => '',
                    'trainer_name' => ''
                ];
            }
        }

        //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
        $options['associated'] = [
            'Trainers' => ['validate' => false]
        ];
    }

    public function addEditOnAddTrainee(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->alias();
        $fieldKey = 'trainees';

        if (empty($data[$this->alias()][$fieldKey])) {
            $data[$this->alias()][$fieldKey] = [];
        }

        if ($data->offsetExists($alias)) {
            if (array_key_exists('trainee_id', $data[$alias]) && !empty($data[$alias]['trainee_id'])) {
                $id = $data[$alias]['trainee_id'];

                try {
                    $obj = $this->Trainees->get($id);

                    $data[$alias][$fieldKey][] = [
                        'id' => $obj->id,
                        '_joinData' => ['openemis_no' => $obj->openemis_no, 'trainee_id' => $obj->id, 'name' => $obj->name, 'training_session_id' => $entity->id]
                    ];
                } catch (RecordNotFoundException $ex) {
                    Log::write('debug', __METHOD__ . ': Record not found for id: ' . $id);
                }
            }
        }

        //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
        $options['associated'] = [
            'Trainees' => ['validate' => false]
        ];
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->query['course'] = $entity->training_course_id;
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();

        $downloadUrl = $toolbarButtonsArray['back']['url'];
        $downloadUrl[0] = 'template';
        $this->controller->set('downloadOnClick', "javascript:window.location.href='". Router::url($downloadUrl) ."'");
        $this->controller->set('importOnClick', "$('#reload').val('massAddTrainees').click();$('#file-input-wrapper').trigger('clear.bs.fileinput');");

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $extra)
    {
        $model = $this;
        $process = function ($model, $entity) use ($data) {
            $errors = $entity->errors();

            if (empty($errors)) {
                // always manual delete all trainers and re-insert
                $trainerRecords = $this->Trainers
                    ->find()
                    ->where([$this->Trainers->aliasField('training_session_id') => $entity->id])
                    ->all();

                foreach ($trainerRecords as $key => $obj) {
                    $this->Trainers->delete($obj);
                }

                return $model->save($entity);
            } else {
                return false;
            }
        };

        return $process;
    }

    public function onUpdateIncludes(Event $event, ArrayObject $includes, $action)
    {
        if ($action == 'edit') {
            $includes['autocomplete'] = [
                'include' => true,
                'css' => ['OpenEmis.../plugins/autocomplete/css/autocomplete'],
                'js' => ['OpenEmis.../plugins/autocomplete/js/autocomplete']
            ];
        }
    }

    public function ajaxTrainerAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->query['term'];
            $data = $this->Trainers->Users->autocomplete($term);
            echo json_encode($data);
            die;
        }
    }

    public function ajaxTraineeAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->query['term'];
            // $data = $this->Trainees->autocomplete($term);

            // autocomplete
            $session = $this->request->session();
            $sessionKey = $this->registryAlias() . '.primaryKey.id';

            $data = [];
            if ($session->check($sessionKey)) {
                $id = $session->read($sessionKey);
                $entity = $this->get($id);

                $TargetPopulations = TableRegistry::get('Training.TrainingCoursesTargetPopulations');
                $Staff = TableRegistry::get('Institution.Staff');
                $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
                $Users = TableRegistry::get('User.Users');
                $Positions = TableRegistry::get('Institution.InstitutionPositions');
                $search = sprintf('%s%%', $term);

                $targetPopulationIds = $TargetPopulations
                    ->find('list', ['keyField' => 'target_population_id', 'valueField' => 'target_population_id'])
                    ->where([$TargetPopulations->aliasField('training_course_id') => $entity->training_course_id])
                    ->toArray();

                $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
                $query = $Staff
                    ->find()
                    ->matching('Users')
                    ->matching('Positions', function ($q) use ($Positions, $targetPopulationIds) {
                        return $q
                            ->find('all')
                            ->where([
                                'Positions.staff_position_title_id IN' => $targetPopulationIds
                            ]);
                    })
                    ->where([$Staff->aliasField('staff_status_id') => $assignedStatus])
                    ->group([$Staff->aliasField('staff_id')])
                    ->order([$Users->aliasField('first_name'), $Users->aliasField('last_name')]);

                // function from AdvancedNameSearchBehavior
                $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
                $list = $query->toArray();

                foreach ($list as $obj) {
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

    public function onUpdateFieldTrainingCourseId(Event $event, array $attr, $action, Request $request)
    {
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

    public function onUpdateFieldTrainingProviderId(Event $event, array $attr, $action, Request $request)
    {
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

    public function onGetCustomTrainersElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $tableHeaders = [$this->getMessage($this->aliasField('trainer_type')), $this->getMessage($this->aliasField('trainer'))];
        $tableCells = [];
        $alias = $this->alias();
        $fieldKey = 'trainers';
        $trainerTypeOptions = $this->getSelectOptions($this->aliasField('trainer_types'));

        if ($action == 'view') {
            $associated = $entity->extractOriginal([$fieldKey]);
            if (!empty($associated[$fieldKey])) {
                foreach ($associated[$fieldKey] as $i => $obj) {
                    $cell = '';
                    if ($obj->type == self::INTERNAL) {
                        $cell = $obj->user->name_with_id;
                    } else if ($obj->type == self::EXTERNAL) {
                        $cell = $obj->name;
                    }

                    $rowData = [];
                    $rowData[] = $trainerTypeOptions[$obj->type];
                    $rowData[] = $cell;

                    $tableCells[] = $rowData;
                }
            }
        } else if ($action == 'add' || $action == 'edit') {
            $tableHeaders[] = ''; // for delete column
            $Form = $event->subject()->Form;
            $Form->unlockField('TrainingSessions.trainers');

            if ($this->request->is(['get'])) {
                if (!array_key_exists($alias, $this->request->data)) {
                    $this->request->data[$alias] = [$fieldKey => []];
                } else {
                    $this->request->data[$alias][$fieldKey] = [];
                }

                $associated = $entity->extractOriginal([$fieldKey]);
                if (!empty($associated[$fieldKey])) {
                    foreach ($associated[$fieldKey] as $key => $obj) {
                        $trainerType = $obj['type'];

                        if ($trainerType == self::INTERNAL) {
                            $trainerId = $obj->trainer_id;
                            $name = '';
                            $trainerName = $obj->user->name_with_id;
                        } else if ($trainerType == self::EXTERNAL) {
                            $trainerId = '';
                            $name = $obj->name;
                            $trainerName = '';
                        }

                        $this->request->data[$alias][$fieldKey][$key] = [
                            'id' => $obj->id,
                            'type' => $trainerType,
                            'trainer_id' => $trainerId,
                            'name' => $name,
                            'trainer_name' => $trainerName
                        ];
                    }
                }
            }

            // refer to addEditOnAddTrainer for http post
            if ($this->request->data("$alias.$fieldKey")) {
                $associated = $this->request->data("$alias.$fieldKey");

                foreach ($associated as $key => $obj) {
                    $trainerType = $obj['type'];
                    $trainerId = $obj['trainer_id'];
                    $trainerName = $obj['trainer_name'];

                    $rowData = [];
                    if ($trainerType == self::INTERNAL) {
                        $cell = $trainerName;
                        $cell .= $Form->hidden("$alias.$fieldKey.$key.name", ['value' => '']);
                        $cell .= $Form->hidden("$alias.$fieldKey.$key.type", ['value' => $trainerType]);
                        $cell .= $Form->hidden("$alias.$fieldKey.$key.trainer_id", ['value' => $trainerId]);
                        $cell .= $Form->hidden("$alias.$fieldKey.$key.trainer_name", ['value' => $trainerName]);
                    } else if ($trainerType == self::EXTERNAL) {
                        $cell = $Form->input("$alias.$fieldKey.$key.name", ['label' => false]);
                        $cell .= $Form->hidden("$alias.$fieldKey.$key.type", ['value' => $trainerType]);
                        $cell .= $Form->hidden("$alias.$fieldKey.$key.trainer_id", ['value' => $trainerId]);
                        $cell .= $Form->hidden("$alias.$fieldKey.$key.trainer_name", ['value' => $trainerName]);
                    }

                    $rowData[] = [$trainerTypeOptions[$trainerType], ['autocomplete-exclude' => $trainerId]];
                    $rowData[] = $cell;
                    $rowData[] = $this->getDeleteButton();
                    $tableCells[] = $rowData;
                }
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('Training.Sessions/' . $fieldKey, ['attr' => $attr]);
    }

    public function onGetCustomTraineesElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $tableHeaders = [__('OpenEMIS ID'), __('Name'), __('Action')];
        $tableCells = [];
        $alias = $this->alias();
        $key = 'trainees';

        if ($action == 'view') {
            $tableHeaders = [__('OpenEMIS ID'), __('Name'), __('Status')];
            $associated = $entity->extractOriginal([$key]);
            if (!empty($associated[$key])) {
                foreach ($associated[$key] as $i => $obj) {
                    $traineeStatus = $obj['_joinData']->status;
                    $rowData = [];
                    $rowData[] = $obj->openemis_no;
                    $rowData[] = $obj->name;
                    if ($traineeStatus == 1) {
                        $rowData[] = __('Approved');
                    } else {
                        $rowData[] = __('Withdrawn');
                    }

                    $tableCells[] = $rowData;
                }
            }
        } else if ($action == 'edit') {
            $tableHeaders[] = ''; // for delete column
            $Form = $event->subject()->Form;
            $Form->unlockField('TrainingSessions.trainees');
            $Form->unlockField('TrainingSessions.trainees_import');

            if ($this->request->is(['get'])) {
                if (!array_key_exists($alias, $this->request->data)) {
                    $this->request->data[$alias] = [$key => []];
                } else {
                    $this->request->data[$alias][$key] = [];
                }

                $associated = $entity->extractOriginal([$key]);
                if (!empty($associated[$key])) {
                    foreach ($associated[$key] as $i => $obj) {
                        $this->request->data[$alias][$key][$obj->id] = [
                            'id' => $obj->id,
                            '_joinData' => ['openemis_no' => $obj->openemis_no, 'trainee_id' => $obj->id, 'name' => $obj->name, 'training_session_id' => $obj->_joinData->training_session_id, 'status' => $obj->_joinData->status]
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
                    $name .= $Form->hidden("$alias.$key.$i._joinData.training_session_id", ['value' => $joinData['training_session_id']]);
                    if (empty($joinData['status'])) {
                        $joinData['status'] = 1;
                    }
                    $name .= $Form->hidden("$alias.$key.$i._joinData.status", ['value' => $joinData['status']]);
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

    public function getTrainingSession($id = null)
    {
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

    public function setupFields(Event $event, Entity $entity)
    {
        $fieldOrder = [
            'training_course_id', 'training_provider_id',
            'code', 'name', 'start_date', 'end_date', 'comment',
            'trainers'
        ];

        $this->field('training_course_id', [
            'type' => 'select'
        ]);
        $this->field('training_provider_id', [
            'type' => 'select',
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $this->field('trainers', [
            'type' => 'custom_trainers',
            'valueClass' => 'table-full-width'
        ]);

        if (!$entity->isNew()) {
            /**
             * Import field variables
             */
            $comment = __('* Format Supported: ' . implode(', ', array_keys($this->fileTypesMap)));
            $comment .= '<br/>';
            $comment .= __('* Recommended Maximum File Size: ' . $this->bytesToReadableFormat($this->MAX_SIZE));
            $comment .= '<br/>';
            $comment .= __('* Recommended Maximum Records: ' . $this->MAX_ROWS);
            // $data = $event->subject()->request->data;
            $data = $this->controller->request->data;
            if ((is_object($data) && $data->offsetExists('trainees_import_error')) || (is_array($data) && isset($data['trainees_import_error']))) {
                $entity->errors('trainees_import', $data['trainees_import_error']);
            }
            /**
             * End Import field variables
             */

            // this is a fake field to make the form render with an "enctype"
            $this->field('trainees_fake_field', ['type' => 'binary', 'visible'=>false]);

            $this->field('trainees', [
                'type' => 'custom_trainees',
                'valueClass' => 'table-full-width',
                'comment' => $comment
            ]);
            $fieldOrder[] = 'trainees_fake_field';
            $fieldOrder[] = 'trainees';
        }

        $this->setFieldOrder($fieldOrder);
    }

    private function setupTabElements()
    {
        $tabElements = $this->controller->getSessionTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Sessions');
    }


/******************************************************************************************************************
**
** Import Functions
**
******************************************************************************************************************/
    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.template'] = 'template';
        $events['ControllerAction.Model.ajaxTrainerAutocomplete'] = 'ajaxTrainerAutocomplete';
        $events['ControllerAction.Model.ajaxTraineeAutocomplete'] = 'ajaxTraineeAutocomplete';
        $events['ControllerAction.Model.addEdit.onMassAddTrainees'] = ['callable' => 'addEditOnMassAddTrainees'];
        return $events;
    }

    public function template()
    {
        // prepareDownload() resides in ImportTrait
        $folder = $this->prepareDownload();
        // Do not localize file name as certain non-latin characters might cause issue
        $excelFile = 'OpenEMIS_Core_Import_Training_Session_Trainees.xlsx';
        $excelPath = $folder . DS . $excelFile;

        $header = ['OpenEMIS ID', 'Identity Type', 'Identity Number'];
        $dataSheetName = __('Training Session Trainees');

        $objPHPExcel = new \PHPExcel();
        $autoTitle = false;
        $titleColumn = 'F';
        // setImportDataTemplate() resides in ImportTrait
        $this->setImportDataTemplate( $objPHPExcel, $dataSheetName, $header, $autoTitle, $titleColumn);

        $this->setCodesDataTemplate($objPHPExcel);

        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
        $objWriter->save($excelPath);

        // performDownload() resides in ImportTrait
        $this->performDownload($excelFile);
        die;
    }

    public function excelGetCodesData() {
        // $survey_form = $this->institutionSurvey->survey_form;
        // $questions = $survey_form->custom_fields;
        // $data = [];
        // $data[0] = [
        //     'data' => [
        //         [__('Name'), __('Code')],
        //         [$survey_form->name, $survey_form->code]
        //     ],
        //     'sheetName' => __('Survey Form'),
        //     'noDropDownList' => true
        // ];
        // foreach ($questions as $question) {
        //     if ($question->field_type == 'DROPDOWN' || $question->field_type == 'CHECKBOX') {
        //         $sheetName = $question->code;
        //         $columnOrder = $question->_joinData->order;
        //         $data[$columnOrder] = [
        //             'data' => [],
        //             'sheetName' => '( '. $question->code .' ) '. $question->name . "\n\n"
        //         ];
        //         $data[$columnOrder]['lookupColumn'] = 2;
        //         $data[$columnOrder]['data'][] = [__('Answer Name'), __('Answer Code')];
        //         if ($question->field_type == 'DROPDOWN') {
        //             $data[$columnOrder]['sheetName'] .= __('(Use only one of the answer codes)');
        //             foreach($question->custom_field_options as $key=>$row) {
        //                 if ($row->visible) {
        //                     $data[$columnOrder]['data'][] = [$row->name, $row->id];
        //                 }
        //             }
        //         } elseif ($question->field_type == 'CHECKBOX') {
        //             $data[$columnOrder]['sheetName'] .= __('(Multiple codes can be selected and seperated by comma and a space. Example: 1, 2)');
        //             $data[$columnOrder]['noDropDownList'] = true;
        //             foreach($question->custom_field_options as $key=>$row) {
        //                 if ($row->visible) {
        //                     $data[$columnOrder]['data'][] = [$row->name, $row->id];
        //                 }
        //             }
        //         }
        //     }
        // }
        // return $data;

        $data = new ArrayObject;

        $data[2] = [
            'data'=>[],
            'sheetName'=>'Identity Type'
        ];

        $data[2]['lookupColumn'] = 2;
        $data[2]['data'][] = [__('Identity Type'), __('ID')];
        $data[2]['data'][] = ['Birth Certificate', '160'];
        $data[2]['data'][] = ['Driver\'s License', '162'];
        $data[2]['data'][] = ['Passport', '163'];

        return $data;
    }

    public function addEditOnMassAddTrainees(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->controller->request;
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
            $header = ['OpenEMIS ID'];

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
            $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
            $Users = TableRegistry::get('User.Users');
            $Positions = TableRegistry::get('Institution.InstitutionPositions');

            $targetPopulationIds = $TargetPopulations
                ->find('list', ['keyField' => 'target_population_id', 'valueField' => 'target_population_id'])
                ->where([$TargetPopulations->aliasField('training_course_id') => $entity->training_course_id])
                ->toArray();

            if (array_key_exists($key, $data[$alias])) {
                $trainees = new Collection($data[$alias][$key]);
                $traineeIds = $trainees->extract('_joinData.openemis_no');
                $traineeIds = $traineeIds->toArray();
            } else {
                $data[$alias][$key] = [];
                $traineeIds = [];
            }

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
                $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');
                $trainee = $Staff
                            ->find()
                            ->matching('Users', function ($q) use ($openemis_no) {
                                return $q
                                    ->find('all')
                                    ->where(['Users.openemis_no' => $openemis_no]);
                            })
                            ->where([$Staff->aliasField('staff_status_id') => $assignedStatus])
                            ;

                if (!empty($targetPopulationIds)) {
                    $trainee =  $trainee
                                ->matching('Positions', function ($q) use ($targetPopulationIds) {
                                    return $q
                                        ->find('all')
                                        ->where([
                                            'Positions.staff_position_title_id IN' => $targetPopulationIds
                                        ]);
                                });
                }

                $trainee =  $trainee
                            ->group([
                                $Staff->aliasField('staff_id')
                            ])
                            ->order([$Users->aliasField('first_name')])
                            ->first();

                if ($trainee) {
                    $data[$alias][$key][$openemis_no] = [
                        'id' => $trainee->_matchingData['Users']->id,
                        '_joinData' => ['openemis_no' => $openemis_no, 'trainee_id' => $trainee->_matchingData['Users']->id, 'name' => $trainee->name, 'training_session_id' => $entity->id]
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
                $this->aliasField('code'),
                $this->aliasField('name'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->CreatedUser->alias()])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {

                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Training',
                        'controller' => 'Trainings',
                        'action' => 'Sessions',
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
                    $row['request_title'] = $row->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
