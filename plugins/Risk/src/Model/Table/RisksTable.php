<?php
namespace Risk\Model\Table;

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
        'SpecialNeeds.SpecialNeedsAssessments' => [
            'SpecialNeedsAssessments' => [
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
        $this->table('risks');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);

        $this->hasMany('RiskCriterias', ['className' => 'Risk.RiskCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionRisks', ['className' => 'Institution.InstitutionRisks', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('InstitutionStudentRisks', ['className' => 'Institution.InstitutionStudentRisks', 'dependent' => true, 'cascadeCallbacks' => true]);
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

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'risk_criterias') {
            return __('Risk Criterias');
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
            if ($key == 'SpecialNeedsAssessments') {
                $criteriaOptions[$key] = $obj['name'];
            } else {
                $criteriaOptions[$key] = __(Inflector::humanize(Inflector::underscore($key)));
            }
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

    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $criteriaData = $this->getCriteriasData();
        $tableHeaders = $this->getMessage('Risk.TableHeader');
        $tableCells = [];
        $criteriaOptions = ['' => '-- '.__('Select Criteria').' --'] + $this->getCriteriasOptions();

        $alias = $this->alias();
        $fieldKey = 'risk_criterias';

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
                    $rowData[] = __($obj['risk_value']);

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
                            'risk_value' => $obj->risk_value,
                            'risk_id' => $obj->risk_id
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
                    $riskId = $obj['risk_id'];

                    if ($criteriaType == 'StatusRepeated') {
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
                    $cell .= $Form->hidden("$alias.$fieldKey.$key.risk_id", ['value' => $riskId]);

                    $rowData[] = $cell;
                    $rowData[] = $this->operatorTypes[$operator];
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.threshold", $this->getThresholdParams($criteriaType));
                    $rowData[] = $Form->input("$alias.$fieldKey.$key.risk_value", ['type' => 'number', 'label' => false, 'min' => 1, 'max' => 99]);
                    $rowData[] = $this->getDeleteButton();
                    $tableCells[] = $rowData;
                }
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;
        $attr['criteriaOptions'] = $criteriaOptions;

        return $event->subject()->renderElement('Risk.Risks/' . $fieldKey, ['attr' => $attr]);
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
        $this->field('modified_user_id', ['visible' => true]);
        $this->field('modified', ['visible' => true]);
        $this->field('academic_period_id', ['visible' => false]);

        // element control
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $requestQuery = $this->request->query;

        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this->AcademicPeriods->getCurrent();

        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

        $extra['elements']['control'] = [
            'name' => 'Risks/controls',
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
            'RiskCriterias' => [
                'sort' => [
                    'RiskCriterias.criteria' => 'ASC',
                    'RiskCriterias.operator' => 'ASC',
                    'RiskCriterias.threshold' => 'ASC'
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
        $this->field('academic_period_id', ['before' => 'name']);
    }

    public function editBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        // to clear the risk criteria when delete all the criteria
        if (!isset($data[$this->alias()]['risk_criterias'])) {
            $data[$this->alias()]['risk_criterias'] = [];
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $entityRiskCriteriasData = $entity->risk_criterias;
        // list of criteria in the risk type
        $entityRiskCriterias = [];
        if (!empty($entityRiskCriteriasData)) {
            foreach ($entityRiskCriteriasData as $key => $entityRiskCriteriasObj) {
                $entityRiskCriterias[$entityRiskCriteriasObj->id] = $entityRiskCriteriasObj;
            }
        }

        $riskId = $entity->id;
        // get the list of student that using this risk type (student that will be affected)
        $institutionStudentRisksResults = $this->InstitutionStudentRisks->find()
            ->where(['risk_id' => $riskId])
            ->all();

        $riskTotal = [];
        foreach ($institutionStudentRisksResults as $key => $obj) {
            $institutionStudentRisksId = $obj->id;
            $institutionId = $obj->institution_id;
            $studentId = $obj->student_id;
            $academicPeriodId = $obj->academic_period_id;

            if (!empty($entityRiskCriterias)) {
                foreach ($entityRiskCriterias as $entityRiskCriteriasKey => $entityRiskCriteriasObj) {
                    $StudentRisksCriterias = TableRegistry::get('Institution.StudentRisksCriterias');
                    $value = $StudentRisksCriterias->getValue($institutionStudentRisksId, $entityRiskCriteriasKey);
                    $riskValue = $StudentRisksCriterias->getRiskValue($value, $entityRiskCriteriasKey, $institutionId, $studentId, $academicPeriodId);

                    $riskTotal[$institutionStudentRisksId] = !empty($riskTotal[$institutionStudentRisksId]) ? $riskTotal[$institutionStudentRisksId] : 0;
                    $riskTotal[$institutionStudentRisksId] = $riskTotal[$institutionStudentRisksId] + $riskValue;
                }
            } else {
                // if the risks doesnt have anymore criteria
                $riskTotal[$institutionStudentRisksId] = 0;
            }

            // update the total risk on the student risks.
            foreach ($riskTotal as $key => $obj) {
                $this->InstitutionStudentRisks->query()
                    ->update()
                    ->set(['total_risk' => $obj])
                    ->where([
                        'id' => $key
                    ])
                    ->execute();
            }
        }
    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        // stop the processing before delete the risks
        $InstitutionRisks = TableRegistry::get('Institution.InstitutionRisks');
        $riskId = $entity->id;

        $records = $InstitutionRisks->find()
            ->where([
                'risk_id' => $riskId,
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
        // delete the institution Risks records
        $InstitutionRisks = TableRegistry::get('Institution.InstitutionRisks');
        // pr($InstitutionRisks);
        $riskId = $entity->id;
        // pr($riskId);die;

        $InstitutionRisks->deleteAll(['risk_id' => $riskId]);

        $this->InstitutionStudentRisks->deleteAll(['risk_id' => $riskId]);
    }

    public function editAfterSave(Event $event, Entity $entity, ArrayObject $data, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $fieldKey = 'risk_criterias';
        $userId = $this->request->session()->read('Auth.User.id');
        $undeletedList = [];
        $originalEntityList = [];
        
        // Get original data from table
        $originalEntity = $this->RiskCriterias->find()->where(['risk_id'=>$entity->id])
                ->toArray();
        
        // get list of original entity
        if (isset($originalEntity)) {
            foreach ($originalEntity as $key => $obj) {
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
                    $this->RiskCriterias->delete($this->RiskCriterias->get($key));
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
        $fieldKey = 'risk_criterias';

        if (array_key_exists($alias, $data) && array_key_exists('criteria_type', $data[$alias])) {
            $criteriaType = $data[$alias]['criteria_type'];
            $operator = $this->getCriteriasDetails($criteriaType)['operator'];

            $data[$alias][$fieldKey][] = [
                'criteria' => $criteriaType,
                'operator' => $operator,
                'threshold' => '',
                'risk_value' => '',
                'risk_id' => 0
            ];

            unset($data[$alias]['criteria_type']);
        }

        //Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
        $options['associated'] = [
            'RiskCriterias' => ['validate' => false]
        ];
    }

    public function setupFields(Event $event, Entity $entity)
    {
        $this->field('risk_criterias', ['type' => 'custom_criterias']);

        $this->setFieldOrder(['name', 'risk_criterias']);
    }

    public function getCriteriasDetails($criteriaKey)
    {
        $criteriaData = $this->getCriteriasData();
        return $details = $criteriaData[$criteriaKey];
    }

    public function getCriteriaByModel($model, $institutionId)
    {
        $InstitutionRisks = TableRegistry::get('Institution.InstitutionRisks');
        $criteriaData = $this->getCriteriasData();

        $criteria = [];
        foreach ($criteriaData as $criteriaKey => $criteriaObj) {
            if ($criteriaObj['model'] == $model) {
                $riskCriteriasData = $this->RiskCriterias->find()
                    ->where(['criteria' => $criteriaKey])
                    ->all();

                foreach ($riskCriteriasData as $riskCriteriasDataObj) {
                    $riskId = $riskCriteriasDataObj->risk_id;

                    if (!empty($riskId) && !empty($institutionId)) {
                        $status = $InstitutionRisks->getStatus($riskId, $institutionId);

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

    public function triggerUpdateIndexesShell($shellName, $institutionId = 0, $userId = 0, $riskId = 0, $academicPeriodId = 0)
    {
        $args = '';
        $args .= !is_null($institutionId) ? ' '.$institutionId : '';
        $args .= !is_null($userId) ? ' '.$userId : '';
        $args .= !is_null($riskId) ? ' '.$riskId : '';
        $args .= !is_null($academicPeriodId) ? ' '.$academicPeriodId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    public function triggerUpdateRisksShell($shellName, $institutionId = 0, $userId = 0, $riskId = 0, $academicPeriodId = 0)
    {
        $args = '';
        $args .= !is_null($institutionId) ? ' '.$institutionId : '';
        $args .= !is_null($userId) ? ' '.$userId : '';
        $args .= !is_null($riskId) ? ' '.$riskId : '';
        $args .= !is_null($academicPeriodId) ? ' '.$academicPeriodId : '';

        $cmd = ROOT . DS . 'bin' . DS . 'cake '.$shellName.' '.$args;
        $logs = ROOT . DS . 'logs' . DS . $shellName.'.log & echo $!';
        $shellCmd = $cmd . ' >> ' . $logs;
        $pid = exec($shellCmd);
        Log::write('debug', $shellCmd);
    }

    public function generate(Event $event, ArrayObject $extra)
    {
        $Risks = TableRegistry::get('Risk.Risks');
        $requestQuery = $this->request->query;
        $params = $this->paramsDecode($requestQuery['queryString']);

        $institutionId = $params['institution_id'];
        $userId = $params['user_id'];
        $riskId = $params['risk_id'];
        $academicPeriodId = $params['academic_period_id'];

        // update indexes pid and status
        $pid = getmypid();
        $Institutions = TableRegistry::get('Institution.Institutions');
        $InstitutionsData = $Institutions
       									->find()
		        					    ->toArray();
        foreach($InstitutionsData AS $key => $Institutionsids){
        $institutionId = $Institutionsids->id;
        $record = $this->getInstitutionIndexesRecords($riskId, $institutionId)->first();

        // if processing id not null (process still running or process stuck)
        if (!empty($record->pid)) {
            exec("kill -9 " . $record->pid);
        }

        if (!empty($record)) {
            // update the status to processing
            $this->InstitutionRisks->updateAll([
                'pid' => $pid,
                'status' => 2 // processing
            ],
            ['id' => $record->id]);
        } else {
            $entity = $this->InstitutionRisks->newEntity([
                'status' => 2, // processing
                'pid' => $pid,
                'generated_on' => NULL,
                'generated_by' => NULL,
                'risk_id' => $riskId,
                'institution_id' => $institutionId,
            ]);
            $this->InstitutionRisks->save($entity);
        }

        // trigger shell
        // trigger shell
        $Risks->triggerUpdateRisksShell('UpdateRisks', $institutionId, $userId, $riskId, $academicPeriodId);
        }

        // redirect to index page
        $url = [
            'plugin' => 'Risk',
            'controller' => 'Risks',
            'action' => 'Risks',
            'index',
            'academic_period_id' => $params['academic_period_id']
        ];

        $event->stopPropagation();
        return $this->controller->redirect($url);
    }
    public function getInstitutionIndexesRecords($riskId)
    {
        return $this->InstitutionRisks->find('Record', ['risk_id' => $riskId]);
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $userId = $session->read('Auth.User.id');
        $riskId = $entity->id;

        if (array_key_exists('view', $buttons)) {
            // generate button
            if ($this->AccessControl->check(['Institutions', 'Risks', 'generate'])) {
                $url = [
                    'plugin' => 'Risk',
                    'controller' => 'Risks',
                    'action' => 'Risks',
                    'generate'
                ];

                $buttons['generate'] = $buttons['view'];
                $buttons['generate']['label'] = '<i class="fa fa-refresh"></i>' . __('Generate');
                $buttons['generate']['url'] = $this->setQueryString($url, [
                    'institution_id' => $institutionId,
                    'user_id' => $userId,
                    'risk_id' => $riskId,
                    'academic_period_id' => $entity->academic_period_id,
                    'action' => 'index'
                ]);
            }
            // end generate button
        }

        return $buttons;
    }
}
