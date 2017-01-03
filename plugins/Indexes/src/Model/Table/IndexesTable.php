<?php
namespace Indexes\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Log\Log;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\HtmlTrait;


class IndexesTable extends ControllerActionTable
{
    use HtmlTrait;

    private $criteriaTypes = [
        'Assessment.AssessmentItemResults' => [
            'name' => 'Results',
            'operator' => [1 => '<', 2 => '>'],
            'threshold' => ['type' => 'number']
        ],
        'Institution.InstitutionStudentAbsences' => [
            'name' => 'Absences',
            'operator' => [1 => '<', 2 => '>'],
            'threshold' => ['type' => 'number']
        ],
        'Institution.StudentBehaviours' => [
            'name' => 'Behaviour',
            'operator' => [3 => '='],
            'threshold' => ['type' => 'select', 'lookupModel' => 'Student.Classifications']
        ],
        // dropout will used the institution.students, while repeated will used Institution.IndividualPromotion
        'Institution.Students' => [
            'Status' => [
                'name' => 'Status',
                'operator' => [3 => '='],
                'threshold' => ['type' => 'select', 'lookupModel' => 'Student.StudentStatuses']
            ],
            'Overage' => [
                'name' => 'Overage',
                'operator' => [2 => '>'],
                'threshold' => ['type' => 'number']
            ]
        ],
        'Institution.StudentUser' => [
            'name' => 'Genders',
            'operator' => [3 => '='],
            'threshold' => ['type' => 'select', 'lookupModel' => 'User.Genders']
        ],
        'Student.Guardians' => [
            'name' => 'Guardians',
            'operator' => [1 => '<', 2 => '>'],
            'threshold' => ['type' => 'number']
        ],
        'User.SpecialNeeds' => [
            'name' => 'Special Needs',
            'operator' => [1 => '<', 2 => '>'],
            'threshold' => ['type' => 'number']
        ],
    ];


    public function initialize(array $config)
    {
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);

        $this->hasMany('IndexesCriterias', ['className' => 'Indexes.IndexesCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentIndexes', ['className' => 'Institution.InstitutionStudentIndexes', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('search', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.generate'] = 'generate';
        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->add('name', 'ruleUnique', [
                'rule' => 'validateUnique',
                'provider' => 'table',
                'message' => __('This field has to be unique')
            ]);
    }

    public function generate(Event $event, ArrayObject $extra)
    {
        $requestQuery = $this->request->query;
        $session = $this->request->session();
        $sessionUserId = $session->read('Auth.User.id');
        $sessionIndexId = $session->read('Indexes.Indexes.primaryKey.id');
        $currentAcademicPeriodId = $this->AcademicPeriods->getCurrent();

        // back Button
        // if have queryString will be redirected to institution>indexes>generate
        if (array_key_exists('queryString', $requestQuery)) {
            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'InstitutionIndexes',
                'index'
            ];
        } else {
            $url = $this->url('index');
        }

        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $toolbarButtonsArray['back']['type'] = 'button';
        $toolbarButtonsArray['back']['label'] = '<i class="fa kd-back"></i>';
        $toolbarButtonsArray['back']['attr'] = $toolbarAttr;
        $toolbarButtonsArray['back']['attr']['title'] = __('Back');
        $toolbarButtonsArray['back']['url'] = $url;
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end back Button

        $this->fields = []; // reset all the fields

        $extra['config']['form'] = true;
        $extra['elements']['edit'] = ['name' => 'OpenEmis.ControllerAction/edit'];

        $params = [];

        if (array_key_exists('queryString', $requestQuery)) {
            $params = $this->getQueryString();
        }

        $institutionId = array_key_exists('institution_id', $params) ? $params['institution_id'] : 0;
        $userId = array_key_exists('user_id', $params) ? $params['user_id'] : $sessionUserId;
        $indexId = array_key_exists('index_id', $params) ? $params['index_id'] : $sessionIndexId;
        $academicPeriodId = array_key_exists('academic_period_id', $params) ? $params['academic_period_id'] : $currentAcademicPeriodId;

        $entity = $this->newEntity();

        if ($this->request->is(['get'])) {
        } else if ($this->request->is(['post', 'put'])) {
            // trigger shell
            $this->triggerUpdateIndexesShell('UpdateIndexes', $institutionId, $userId, $indexId, $academicPeriodId);
        }

        $this->controller->set('data', $entity);
        return $entity;
    }

    public function getCriteriasData()
    {
        $criteriaData = [];
        foreach ($this->criteriaTypes as $key => $obj) {
            if ($key == 'Institution.Students') {
                foreach ($this->criteriaTypes[$key] as $institutionStudentsKey => $institutionStudentsObj) {
                    $criteriaData[$institutionStudentsObj['name']] = $institutionStudentsObj;
                    $criteriaData[$institutionStudentsObj['name']]['model'] = $key;
                }
            } else {
                $criteriaData[$obj['name']] = $obj;
                $criteriaData[$obj['name']]['model'] = $key;
            }
        }

        return $criteriaData;
    }

    public function getCriteriasOptions()
    {
        $criteriaData = $this->getCriteriasData();

        $criteriaOptions = [];
        foreach ($criteriaData as $key => $obj) {
            $criteriaOptions[$key] = __($obj['name']);
        }
        ksort($criteriaOptions); // sorting the option by Key

        return $criteriaOptions;
    }

    public function getOperatorParams($criteriaType)
    {
        $criteriaData = $this->getCriteriasData();

        $operatorParams['label'] = false;
        $operatorParams['type'] = 'select';
        $operatorParams['options'] = $criteriaData[$criteriaType]['operator'];

        return $operatorParams;
    }

    public function getThresholdParams($criteriaType)
    {
        $criteriaData = $this->getCriteriasData();

        $thresholdParams['label'] = false;
        $thresholdParams['type'] = $criteriaData[$criteriaType]['threshold']['type'];

        if ($thresholdParams['type'] == 'select') {
            $model = $criteriaData[$criteriaType]['threshold']['lookupModel'];
            $thresholdParams['options'] = $this->getOptions($model);
        }

        return $thresholdParams;
    }

    public function getOptions($model)
    {
        $model = TableRegistry::get($model);
        $options = [];
        $options = $model->getThresholdOptions();

        return $options;
    }

    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $criteriaData = $this->getCriteriasData();
        $tableHeaders = $this->getMessage('Indexes.TableHeader');
        $tableCells = [];
        $criteriaOptions = ['' => '-- '.__('Select Criteria').' --'] + $this->getCriteriasOptions();

        $alias = $this->alias();
        $fieldKey = 'indexes_criterias';

        if ($action == 'view') {
            $associated = $entity->extractOriginal([$fieldKey]);

            if (!empty($associated[$fieldKey])) {
                foreach ($associated[$fieldKey] as $i => $obj) {
                    if ($obj['operator'] == 3) {
                        // '=' the threshold is a string
                        $lookupModel = TableRegistry::get($criteriaData[$obj['criteria']]['threshold']['lookupModel']);
                        $thresholdData = $lookupModel->get($obj['threshold'])->name;
                    } else {
                        // '<' and '>' the threshold is a numeric
                        $thresholdData = $obj['threshold'];
                    }

                    $rowData = [];
                    $rowData[] = __($criteriaData[$obj['criteria']]['name']);
                    $rowData[] = $criteriaData[$obj['criteria']]['operator'][$obj['operator']];
                    $rowData[] = __($thresholdData); // will get form the FO or from the model related
                    $rowData[] = __($obj['index_value']);

                    $tableCells[] = $rowData;
                }
            }

        } else if ($action == 'add' || $action == 'edit') {
            $tableHeaders[] = ''; // for delete column
            $Form = $event->subject()->Form;
            $Form->unlockField($alias.".".$fieldKey);

            if ($this->request->is(['get'])) {
                // to read from saved data
                if (!array_key_exists($alias, $this->request->data)) {
                    $this->request->data[$alias] = [$fieldKey => []];
                } else {
                    $this->request->data[$alias][$fieldKey] = [];
                }

                $associated = $entity->extractOriginal([$fieldKey]);

                if (!empty($associated[$fieldKey])) {
                    foreach ($associated[$fieldKey] as $key => $obj) {
                        $this->request->data[$alias][$fieldKey][$key] = [
                            'id' => $obj->id,
                            'criteria' => $obj->criteria,
                            'operator' => $obj->operator,
                            'threshold' => $obj->threshold,
                            'index_value' => $obj->index_value,
                            'index_id' => $obj->index_id
                        ];
                    }
                }
            }

            // refer to addEditOnAddTrainer for http post
            if ($this->request->data("$alias.$fieldKey")) {
                $associated = $this->request->data("$alias.$fieldKey");
                foreach ($associated as $key => $obj) {
                    $rowData = [];
                    $criteriaType = $obj['criteria'];
                    $operator = $obj['operator'];
                    $threshold = $obj['threshold'];
                    $indexId = $obj['index_id'];

                    $cell = $criteriaOptions[$criteriaType];
                    if (isset($obj['id'])) {
                        $cell .= $Form->hidden("$alias.$fieldKey.$key.id", ['value' => $obj['id']]);
                    }
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.criteria", ['value' => $criteriaType]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.operator", ['value' => $operator]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.threshold", ['value' => $threshold]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.index_id", ['value' => $indexId]);

                    $rowData[] = $cell;
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.operator", $this->getOperatorParams($criteriaType));
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.threshold", $this->getThresholdParams($criteriaType));
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.index_value", ['type' => 'number', 'label' => false]);
                    $rowData[] = $this->getDeleteButton();
                    $tableCells[] = $rowData;
                }
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;
        $attr['criteriaOptions'] = $criteriaOptions;

        return $event->subject()->renderElement('Indexes.Indexes/' . $fieldKey, ['attr' => $attr]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList(['isEditable' => true]);

            $attr['type'] = 'select';
            $attr['options'] = $periodOptions;
        } elseif ($action == 'edit') {
            $requestQuery = $this->request->query;

            $academicPeriodId = !empty($requestQuery) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->AcademicPeriods->get($academicPeriodId)->name;
            $attr['value'] = $academicPeriodId;
        }

        return $attr;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name',['sort' => false]);
        $this->field('modified_user_id',['visible' => true]);
        $this->field('modified',['visible' => true, 'sort' => false]);
        $this->field('generated_on',['sort' => false, 'after' => 'generated_by']);
        $this->field('academic_period_id',['visible' => false]);

        // element control
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $requestQuery = $this->request->query;

        $selectedAcademicPeriodId = !empty($requestQuery) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();

        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

        $extra['elements']['control'] = [
            'name' => 'Indexes/controls',
            'data' => [
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId
            ],
            'options' => [],
            'order' => 3
        ];
        // end element control
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'IndexesCriterias' => [
                'sort' => [
                    'IndexesCriterias.criteria' => 'ASC',
                    'IndexesCriterias.operator' => 'ASC',
                    'IndexesCriterias.threshold' => 'ASC'
                ]
            ]
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);

        // generate buttons
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $url = [
            'plugin' => 'Indexes',
            'controller' => 'Indexes',
            'action' => 'Indexes',
            'generate'
        ];
        $toolbarButtonsArray['generate']['type'] = 'button';
        $toolbarButtonsArray['generate']['label'] = '<i class="fa fa-refresh"></i>';
        $toolbarButtonsArray['generate']['attr'] = $toolbarAttr;
        $toolbarButtonsArray['generate']['attr']['title'] = __('Generate');
        $toolbarButtonsArray['generate']['url'] = $url;

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end generate buttons

    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
        $this->field('academic_period_id',['before' => 'name']);
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // to clear the indexes criteria when delete all the criteria
        if (!isset($data[$this->alias()]['indexes_criterias'])) {
            $data[$this->alias()]['indexes_criterias'] = [];
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $entityIndexesCriteriasData = $entity->indexes_criterias;
        // list of criteria in the index type
        $entityIndexesCriterias = [];
        if (!empty($entityIndexesCriteriasData)) {
            foreach ($entityIndexesCriteriasData as $key => $entityIndexesCriteriasObj) {
                $entityIndexesCriterias[$entityIndexesCriteriasObj->id] = $entityIndexesCriteriasObj;
            }
        }

        $indexId = $entity->id;
        // get the list of student that using this index type (student that will be affected)
        $institutionStudentIndexesResults = $this->InstitutionStudentIndexes->find()
            ->where(['index_id' => $indexId])
            ->all();

        $indexTotal = [];
        foreach ($institutionStudentIndexesResults as $key => $obj) {
            $institutionStudentIndexesId = $obj->id;
            $institutionId = $obj->institution_id;
            $studentId = $obj->student_id;
            $academicPeriodId = $obj->academic_period_id;

            if (!empty($entityIndexesCriterias)) {
                foreach ($entityIndexesCriterias as $entityIndexesCriteriasKey => $entityIndexesCriteriasObj) {
                    $StudentIndexesCriterias = TableRegistry::get('Institution.StudentIndexesCriterias');
                    $value = $StudentIndexesCriterias->getValue($institutionStudentIndexesId, $entityIndexesCriteriasKey);
                    $indexValue = $StudentIndexesCriterias->getIndexValue($value, $entityIndexesCriteriasKey, $institutionId, $studentId, $academicPeriodId);

                    $indexTotal[$institutionStudentIndexesId] = !empty($indexTotal[$institutionStudentIndexesId]) ? $indexTotal[$institutionStudentIndexesId] : 0;
                    $indexTotal[$institutionStudentIndexesId] = $indexTotal[$institutionStudentIndexesId] + $indexValue;
                }
            } else {
                // if the indexes doesnt have anymore criteria
                $indexTotal[$institutionStudentIndexesId] = 0;
            }

            // update the total index on the student indexes.
            foreach ($indexTotal as $key => $obj) {
                $this->InstitutionStudentIndexes->query()
                    ->update()
                    ->set(['total_index' => $obj])
                    ->where([
                        'id' => $key
                    ])
                    ->execute();
            }
        }
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $fieldKey = 'indexes_criterias';
        $undeletedList = [];
        $originalEntityList = [];
        $originalEntity = [];
        $originalEntity = $entity->extractOriginal([$fieldKey]);

        // get list of original entity
        if (isset($originalEntity[$fieldKey])) {
            foreach ($originalEntity[$fieldKey] as $key => $obj) {
                $originalEntityList[$obj->id] = $obj->criteria;
            }
        }

        // get the list of undeleted records, if all deleted, this list will be emtpy
        if (isset($data[$this->alias()][$fieldKey])) {
            foreach ($data[$this->alias()][$fieldKey] as $key => $obj) {
                if (!empty($obj['id'])) {
                    $undeletedList[$obj['id']] = $obj['criteria'];
                }
            }
        }

        // compare the original list and undeleted list, if not in undeleted list will be deleted.
        if (!empty($originalEntityList)) {
            foreach ($originalEntityList as $key => $obj) {
                if (!array_key_exists($key, $undeletedList)) {
                    $this->IndexesCriterias->delete($this->IndexesCriterias->get($key));
                }
            }
        }
    }

    public function addEditOnAddCriteria(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->alias();
        $fieldKey = 'indexes_criterias';

        if (array_key_exists($alias, $data) && array_key_exists('criteria_type', $data[$alias])) {
            $criteriaType = $data[$alias]['criteria_type'];

            $data[$alias][$fieldKey][] = [
                'criteria' => $criteriaType,
                'operator' => '',
                'threshold' => '',
                'index_value' => '',
                'index_id' => 0
            ];

            unset($data[$alias]['criteria_type']);
        }

        //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
        $options['associated'] = [
            'IndexesCriterias' => ['validate' => false]
        ];
    }

    public function setupFields(Event $event, Entity $entity)
    {
        $this->field('generated_by',['visible' => false]);
        $this->field('generated_on',['visible' => false]);
        $this->field('indexes_criterias', ['type' => 'custom_criterias']);

        $this->setFieldOrder(['name', 'indexes_criterias']);
    }

    public function onGetGeneratedBy(Event $event, Entity $entity)
    {
        $userName = '';
        if (isset($entity->generated_by)) {
            $generatedById = $entity->generated_by;

            $Users = TableRegistry::get('Security.Users');
            $userName = $Users->get($generatedById)->first_name . ' ' . $Users->get($generatedById)->last_name;
        }

        return $userName;
    }

    public function getCriteriasDetails($criteriaKey)
    {
        $criteriaData = $this->getCriteriasData();
        return $details = $criteriaData[$criteriaKey];
    }

    public function getCriteriaByModel($model)
    {
        $criteriaData = $this->getCriteriasData();

        $criteria = [];
        foreach ($criteriaData as $key => $obj) {
            if ($obj['model'] == $model) {
                $criteria[$obj['name']] = $obj;
            }
        }

        return $criteria;
    }

    public function triggerUpdateIndexesShell($shellName, $institutionId=0, $userId=0, $indexId=0, $academicPeriodId=0)
    {
        $args = '';
        $args .= !is_null($institutionId) ? ' '.$institutionId : '';
        $args .= !is_null($userId) ? ' '.$userId : '';
        $args .= !is_null($indexId) ? ' '.$indexId : '';
        $args .= !is_null($academicPeriodId) ? ' '.$academicPeriodId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }
}
