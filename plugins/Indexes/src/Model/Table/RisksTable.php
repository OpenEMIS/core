<?php
namespace Indexes\Model\Table;

use ArrayObject;

use Cake\I18n\Date;
use Cake\I18n\Time;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Log\Log;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\HtmlTrait;


class RisksTable extends ControllerActionTable
{
    use HtmlTrait;

    private $criteriaTypes = [
        // 'Assessment.AssessmentItemResults' => [
        //     'name' => 'Results',
        //     'operator' => 2,
        //     'threshold' => ['type' => 'number']
        // ],
        'Institution.InstitutionStudentAbsences' => [
            'AbsencesExcused' => [
                'name' => 'Absence - Excused',
                'operator' => 2,
                'threshold' => ['type' => 'number'],
                'absence_type_id' => 1 // excused
            ],
            'AbsencesUnexcused' => [
                'name' => 'Absence - Unexcused',
                'operator' => 2,
                'threshold' => ['type' => 'number'],
                'absence_type_id' => 2 // unexcused
            ],
            'AbsencesLate' => [
                'name' => 'Absence - Late',
                'operator' => 2,
                'threshold' => ['type' => 'number'],
                'absence_type_id' => 3 // late
            ]
        ],
        'Institution.StudentBehaviours' => [
            'Behaviour' => [
                'name' => 'Behaviour',
                'operator' => 3,
                'threshold' => ['type' => 'select', 'lookupModel' => 'Student.BehaviourClassifications']
            ]
        ],
        // dropout will used the institution.students, while repeated will used Institution.IndividualPromotion
        'Institution.Students' => [
            // 'StatusDropout' => [
            //     'name' => 'Student Status - Dropout',
            //     'operator' => 3,
                // 'threshold' => ['type' => 'select', 'lookupModel' => 'Student.StudentStatuses', 'value' => 'Yes']
            // ],
            'StatusRepeated' => [
                'name' => 'Student Status',
                'operator' => 11, // Repeated
                'threshold' => ['type' => 'select', 'lookupModel' => 'Student.StudentStatuses', 'value' => 'Yes']
            ],
            'Overage' => [
                'name' => 'Overage',
                'operator' => 2,
                'threshold' => ['type' => 'number']
            ],            //
            'Genders' => [
                'name' => 'Genders',
                'operator' => 3,
                'threshold' => ['type' => 'select', 'lookupModel' => 'User.Genders']
            ],
            'Guardians' => [
                'name' => 'Guardians',
                'operator' => 1,
                'threshold' => ['type' => 'number']
            ]
        ],
        'User.SpecialNeeds' => [
            'SpecialNeeds' => [
                'name' => 'Special Needs',
                'operator' => 2,
                'threshold' => ['type' => 'number']
            ]
        ],
    ];

    private $operatorTypes = [
        1 => 'Less than or equal to',
        2 => 'Greater than or equal to',
        3 => 'Equal to',
        11 => 'Repeated'
    ];

    private $statusTypes = [
        1 => 'Not Generated',
        2 => 'Processing',
        3 => 'Generated',
        4 => 'Not Completed'
    ];

    public function initialize(array $config)
    {
        $this->table('indexes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);

        $this->hasMany('IndexesCriterias', ['className' => 'Indexes.IndexesCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentIndexes', ['className' => 'Institution.InstitutionStudentIndexes', 'dependent' => true, 'cascadeCallbacks' => true]);
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'indexes_criterias') {
            return __('Risks Criterias');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function getCriteriasData()
    {
        $criteriaData = [];
        foreach ($this->criteriaTypes as $key => $obj) {
            foreach ($this->criteriaTypes[$key] as $typesKey => $typesObj) {
                $criteriaData[$typesKey] = $typesObj;
                $criteriaData[$typesKey]['model'] = $key;
            }
        }

        return $criteriaData;
    }

    public function getCriteriasOptions()
    {
        $criteriaData = $this->getCriteriasData();

        $criteriaOptions = [];
        foreach ($criteriaData as $key => $obj) {
            $criteriaOptions[$key] = __(Inflector::humanize(Inflector::underscore($key)));
        }
        ksort($criteriaOptions); // sorting the option by Key

        return $criteriaOptions;
    }

    public function getThresholdParams($criteriaType)
    {
        $criteriaData = $this->getCriteriasData();

        $thresholdParams['label'] = false;
        $thresholdParams['type'] = $criteriaData[$criteriaType]['threshold']['type'];
        $thresholdParams['min'] = 1;
        $thresholdParams['max'] = 99;

        if ($criteriaType == 'Guardians') {
            $thresholdParams['min'] = 0;
        }

        if ($thresholdParams['type'] == 'select') {
            $model = $criteriaData[$criteriaType]['threshold']['lookupModel'];

            if ($criteriaType == 'StatusRepeated') {
                $value = $criteriaData[$criteriaType]['threshold']['value'];
                $operatorId = $criteriaData[$criteriaType]['operator'];
                $operator = $this->operatorTypes[$operatorId];
                $options = $this->getOptions($model);

                // change the threshold to 'Yes' instead of 'Repeated'
                $thresholdParams['options'] = str_replace($operator, $value, $options);
            } else {
                $thresholdParams['options'] = $this->getOptions($model);
            }
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
                foreach ($associated[$fieldKey] as $obj) {
                    if ($obj['operator'] == 3) {
                        // '=' the threshold is a string
                        $lookupModel = TableRegistry::get($criteriaData[$obj['criteria']]['threshold']['lookupModel']);
                        $thresholdData = __($lookupModel->get($obj['threshold'])->name);
                    } else if ($obj['operator'] == 11) { // for Repeated
                        // for student status, the threshold value will be 'Yes'
                        $thresholdData = __($criteriaData[$obj->criteria]['threshold']['value']);
                    } else {
                        // '<' and '>' the threshold is a numeric
                        $thresholdData = $obj['threshold'];
                    }

                    $rowData = [];
                    $rowData[] = __($criteriaData[$obj['criteria']]['name']);
                    $rowData[] = __($this->operatorTypes[$obj->operator]);
                    $rowData[] = $thresholdData; // will get form the FO or from the model related
                    $rowData[] = __($obj['index_value']);

                    $tableCells[] = $rowData;
                }
            }

        } else if ($action == 'add' || $action == 'edit') {
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

                    if ($criteriaType == 'StatusRepeated' ) {
                        // for status the criteria name will be student status.
                        $cell = $criteriaData[$criteriaType]['name'];
                    } else {
                        $cell = $criteriaOptions[$criteriaType];
                    }

                    if (isset($obj['id'])) {
                        $cell .= $Form->hidden("$alias.$fieldKey.$key.id", ['value' => $obj['id']]);
                    }
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.criteria", ['value' => $criteriaType]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.operator", ['value' => $operator]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.threshold", ['value' => $threshold]);
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.index_id", ['value' => $indexId]);

                    $rowData[] = $cell;
                    $rowData[] = $this->operatorTypes[$operator];
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.threshold", $this->getThresholdParams($criteriaType));
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.index_value", ['type' => 'number', 'label' => false, 'min' => 1, 'max' => 99]);
                    $rowData[] = $this->getDeleteButton();
                    $tableCells[] = $rowData;
                }
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;
        $attr['criteriaOptions'] = $criteriaOptions;

        return $event->subject()->renderElement('Indexes.Risks/' . $fieldKey, ['attr' => $attr]);
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $periodOptions = $this->AcademicPeriods->getYearList();

            $attr['type'] = 'select';
            $attr['options'] = $periodOptions;
        } else if ($action == 'edit') {
            $requestQuery = $this->request->query;

            $academicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $this->AcademicPeriods->get($academicPeriodId)->name;
            $attr['value'] = $academicPeriodId;
        }

        return $attr;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('modified_user_id',['visible' => true]);
        $this->field('modified',['visible' => true]);
        $this->field('academic_period_id',['visible' => false]);

        // element control
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $requestQuery = $this->request->query;

        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();

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
            'RisksCriterias' => [
                'sort' => [
                    'RisksCriterias.criteria' => 'ASC',
                    'RisksCriterias.operator' => 'ASC',
                    'RisksCriterias.threshold' => 'ASC'
                ]
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

    public function beforeDelete(Event $event, Entity $entity)
    {
        // stop the processing before delete the indexes.
        $InstitutionIndexes = TableRegistry::get('Institution.InstitutionIndexes');
        $indexId = $entity->id;

        $records = $InstitutionIndexes->find()
            ->where([
                'index_id' => $indexId,
                'status' => 2 // processing
            ])
            ->all();

        if (!empty($records)) {
            foreach ($records as $obj) {
                $pid = $obj->pid;
                if (!empty($pid)) {
                    exec("kill -9 " . $pid);
                }
            }
        }
    }

    public function afterDelete(Event $event, Entity $entity, ArrayObject $options)
    {
        // delete the institution Indexes records
        $InstitutionIndexes = TableRegistry::get('Institution.InstitutionIndexes');
        $indexId = $entity->id;
        $InstitutionIndexes->deleteAll(['index_id' => $indexId]);

        $this->InstitutionStudentIndexes->deleteAll(['index_id' => $indexId]);
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $fieldKey = 'indexes_criterias';
        $userId = $this->request->session()->read('Auth.User.id');
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
                    $this->RisksCriterias->delete($this->RisksCriterias->get($key));
                }
            }
        }

        // update the modified by and date
        $this->updateAll(
            ['modified_user_id' => $userId],
            ['id' => $entity->id]
        );

    }

    public function addEditOnAddCriteria(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $alias = $this->alias();
        $fieldKey = 'indexes_criterias';

        if (array_key_exists($alias, $data) && array_key_exists('criteria_type', $data[$alias])) {
            $criteriaType = $data[$alias]['criteria_type'];
            $operator = $this->getCriteriasDetails($criteriaType)['operator'];

            $data[$alias][$fieldKey][] = [
                'criteria' => $criteriaType,
                'operator' => $operator,
                'threshold' => '',
                'index_value' => '',
                'index_id' => 0
            ];

            unset($data[$alias]['criteria_type']);
        }

        //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
        $options['associated'] = [
            'RisksCriterias' => ['validate' => false]
        ];
    }

    public function setupFields(Event $event, Entity $entity)
    {
        $this->field('indexes_criterias', ['type' => 'custom_criterias']);

        $this->setFieldOrder(['name', 'indexes_criterias']);
    }

    public function getCriteriasDetails($criteriaKey)
    {
        $criteriaData = $this->getCriteriasData();
        return $details = $criteriaData[$criteriaKey];
    }

    public function getCriteriaByModel($model, $institutionId)
    {
        $InstitutionIndexes = TableRegistry::get('Institution.InstitutionIndexes');
        $criteriaData = $this->getCriteriasData();

        $criteria = [];
        foreach ($criteriaData as $criteriaKey => $criteriaObj) {
            if ($criteriaObj['model'] == $model) {
                $indexesCriteriasData = $this->IndexesCriterias->find()
                    ->where(['criteria' => $criteriaKey])
                    ->all();

                foreach ($indexesCriteriasData as $indexesCriteriasDataObj) {
                    $indexesId = $indexesCriteriasDataObj->index_id;

                    if (!empty($indexesId) && !empty($institutionId)) {
                        $status = $InstitutionIndexes->getStatus($indexesId, $institutionId);

                        if ($status == 2 || $status == 3) { // Status processing and completed
                            $criteria[$criteriaKey] = $criteriaObj;
                        }
                    }
                }
            }
        }

        return $criteria;
    }

    public function getIndexesStatus($statusId)
    {
        return $this->statusTypes[$statusId];
    }


    public function getOperatorDetails($operatorId)
    {
        return $this->operatorTypes[$operatorId];
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
