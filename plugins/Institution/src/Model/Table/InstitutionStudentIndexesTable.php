<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class InstitutionStudentIndexesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('Indexes', ['className' => 'Indexes.Indexes', 'foreignKey' =>'index_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' =>'student_id']);

        $this->hasMany('StudentIndexesCriterias', ['className' => 'Institution.StudentIndexesCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('add', false);
        $this->toggle('search', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        // student absence and attendance
        $events['Model.InstitutionStudentAbsences.afterSave'] = 'afterSaveOrDelete';
        $events['Model.InstitutionStudentAbsences.afterDelete'] = 'afterSaveOrDelete';

        // student behaviour
        $events['Model.StudentBehaviours.afterSave'] = 'afterSaveOrDelete';
        $events['Model.StudentBehaviours.afterDelete'] = 'afterSaveOrDelete';

        // student gender
        $events['Model.StudentUser.afterSave'] = 'afterSaveOrDelete';

        // student guardian
        $events['Model.Guardians.afterSave'] = 'afterSaveOrDelete';
        $events['Model.Guardians.afterDelete'] = 'afterSaveOrDelete';

        // student with special need
        $events['Model.SpecialNeeds.afterSave'] = 'afterSaveOrDelete';
        $events['Model.SpecialNeeds.afterDelete'] = 'afterSaveOrDelete';

        // student dropout (Students), repeated (IndividualPromotion), Overage will trigger the Students
        $events['Model.Students.afterSave'] = 'afterSaveOrDelete';
        $events['Model.Students.afterDelete'] = 'afterSaveOrDelete';
        $events['Model.IndividualPromotion.afterSave'] = 'afterSaveOrDelete';
        $events['Model.IndividualPromotion.afterDelete'] = 'afterSaveOrDelete';

        // Assessment result item
        $events['Model.AssessmentItemResults.afterSave'] = 'afterSaveOrDelete';
        $events['Model.AssessmentItemResults.afterDelete'] = 'afterSaveOrDelete';
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no');
        $this->field('index_id',['visible' => false]);
        $this->field('average_index',['visible' => false]);
        $this->field('student_id');
        $this->field('academic_period_id',['visible' => false]);

        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $userId = $session->read('Auth.User.id');
        $indexId = $this->request->query['index_id'];
        $academicPeriodId = $this->request->query['academic_period_id'];

        // back buttons
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
        $toolbarButtonsArray['back']['url']['plugin'] = 'Institution';
        $toolbarButtonsArray['back']['url']['controller'] = 'Institutions';
        $toolbarButtonsArray['back']['url']['action'] = 'InstitutionIndexes';
        $toolbarButtonsArray['back']['url'][0] = 'index';

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end back buttons

        // generate buttons
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $toolbarButtonsArray['generate']['type'] = 'button';
        $toolbarButtonsArray['generate']['label'] = '<i class="fa fa-refresh"></i>';
        $toolbarButtonsArray['generate']['attr'] = $toolbarAttr;
        $toolbarButtonsArray['generate']['attr']['title'] = __('Generate');
        $url = [
            'plugin' => 'Indexes',
            'controller' => 'Indexes',
            'action' => 'Indexes',
            'generate'
        ];
        $toolbarButtonsArray['generate']['url'] = $this->setQueryString($url, [
            'institution_id' => $institutionId,
            'user_id' => $userId,
            'index_id' => $indexId,
            'academic_period_id' => $academicPeriodId
        ]);

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end generate buttons

        // element control
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $selectedAcademicPeriodId = $this->request->query['academic_period_id'];

        $classOptions = $Classes->getClassOptions($selectedAcademicPeriodId, $institutionId);
        if (!empty($classOptions)) {
            $classOptions = [0 => 'All Classes'] + $classOptions;
        }

        $selectedClassId = $this->queryString('class_id', $classOptions);
        $this->advancedSelectOptions($classOptions, $selectedClassId, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
            'callable' => function($id) use ($InstitutionClassStudents) {
                return $InstitutionClassStudents
                    ->find()
                    ->where([
                        $InstitutionClassStudents->aliasField('institution_class_id') => $id
                    ])
                    ->count();
            }
        ]);
        $extra['selectedClass'] = $selectedClassId;

        $extra['elements']['control'] = [
            'name' => 'StudentIndexes/controls',
            'data' => [
                'classOptions'=>$classOptions,
                'selectedClass'=>$selectedClassId,
            ],
            'options' => [],
            'order' => 3
        ];
        // end element control
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $requestQuery = $this->request->query;
        $session = $this->request->session();

        $institutionId = $session->read('Institution.Institutions.id');
        $academicPeriodId = $requestQuery['academic_period_id'];
        $classId = $extra['selectedClass'];

        $conditions = [
            $this->aliasField('index_id') => $requestQuery['index_id'],
            $this->aliasField('academic_period_id') => $academicPeriodId
        ];

        if ($classId > 0) {
            $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $studentList = $InstitutionClassStudents->getStudentsList($academicPeriodId, $institutionId, $classId);

            $conditions = [
                $this->aliasField('index_id') => $requestQuery['index_id'],
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('student_id') . ' IN ' => $studentList
            ];
        }

        return $query->where([$conditions]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('index_id', ['after' => 'academic_period_id']);
        $this->field('total_index', ['after' => 'index_id']);
        $this->field('indexes_criterias', ['type' => 'custom_criterias', 'after' => 'total_index']);
        $this->field('average_index', ['visible' => false]);
        $this->field('student_id', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);

        // BreadCrumb
        $indexId = $this->request->query['index_id'];
        $academicPeriodId = $this->request->query['academic_period_id'];
        $institutionIndexesUrl = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'InstitutionStudentIndexes', 'index_id' => $indexId, 'academic_period_id' => $academicPeriodId];

        $this->Navigation->substituteCrumb('Institution Student Indexes', 'Institution Student Indexes', $institutionIndexesUrl);

        // Header
        $studentName = $entity->user->first_name . ' ' . $entity->user->last_name;
        $header = $studentName . ' - ' . __(Inflector::humanize(Inflector::underscore($this->alias())));

        $this->controller->set('contentHeader', $header);
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function afterSaveOrDelete(Event $mainEvent, Entity $afterSaveOrDeleteEntity)
    {

        $criteriaModel = $afterSaveOrDeleteEntity->source();

// Institution.StudentUser       Student.Guardians
        $consolidatedModel = ['Institution.StudentUser', 'Student.Guardians', 'Institution.IndividualPromotion'];
// pr('criteriaModel - afterSaveOrDelete');
// pr($criteriaModel);
// die;
        // if ($criteriaModel == 'Institution.IndividualPromotion') {
        if (in_array($criteriaModel, $consolidatedModel)) {
            // pr('inside');die;
            $criteriaModel = 'Institution.Students';
        }

        // $criteriaData = $this->Indexes->getCriteriaByModel($criteriaModel);

        // $IndexesCriterias = TableRegistry::get('Indexes.IndexesCriterias');
        pr($criteriaModel);
        $criteriaTable = TableRegistry::get($criteriaModel);

        pr($criteriaTable);die;
        // to get studentId
        if (isset($afterSaveOrDeleteEntity->student_id)) {
            $studentId = $afterSaveOrDeleteEntity->student_id;
        } else {
            // for gender will be using security_user table the student_id is the ID
            $studentId = $this->getStudentId($criteriaTable, $afterSaveOrDeleteEntity);
        }

        // to get the academicPeriodId
        if (isset($afterSaveOrDeleteEntity->academic_period_id)) {
            $academicPeriodId = $afterSaveOrDeleteEntity->academic_period_id;
        } else {
            // afterDelete $afterSaveOrDeleteEntity doesnt have academicPeriodId, model also have different date
            $academicPeriodId = $this->getAcademicPeriodId($criteriaTable, $afterSaveOrDeleteEntity);
        }

        // to get the institutionId
        if (isset($afterSaveOrDeleteEntity->institution_id)) {
            $institutionId = $afterSaveOrDeleteEntity->institution_id;
        } else {
            // for gender will be using security_user table, doesnt have any institution
            $institutionId = $this->getInstitutionId($criteriaTable, $afterSaveOrDeleteEntity, $academicPeriodId);
        }
// pr('afterSaveOrDelete - ins stu inde table');
// pr($afterSaveOrDeleteEntity);
// pr($criteriaData);
// die;
        foreach ($criteriaData as $criteriaDataKey => $criteriaDataObj) {
            $criteriaName = $criteriaDataObj['name'];

            // to get the indexes criteria to get the value on the student_indexes_criterias
            $indexesCriteriaResults = $IndexesCriterias->find()
                ->contain(['Indexes'])
                ->where([
                    'criteria' => $criteriaName,
                    'Indexes.academic_period_id' => $academicPeriodId
                ])
                ->all();
// pr('indexesCriteriaResults');
// pr($indexesCriteriaResults);
pr($criteriaTable);
die;
            if (!$indexesCriteriaResults->isEmpty()) {
                foreach ($indexesCriteriaResults as $key => $indexesCriteriaData) {
                    $indexId = $indexesCriteriaData->index_id;
                    $threshold = $indexesCriteriaData->threshold;
                    $operator = $indexesCriteriaData->operator;

                    $params = new ArrayObject([
                        'institution_id' => $institutionId,
                        'student_id' => $studentId,
                        'academic_period_id' => $academicPeriodId,
                        'criteria_name' => $criteriaName
                    ]);

                    $event = $criteriaTable->dispatchEvent('Model.InstitutionStudentIndexes.calculateIndexValue', [$params], $this);

                    if ($event->isStopped()) {
                        $mainEvent->stopPropagation();
                        return $event->result;
                    }

                    $valueIndexData = $event->result;
pr('valueIndexData');
pr($valueIndexData);
die;
                    $institutionStudentIndexesResults = $this->find()
                        ->where([
                            $this->aliasField('academic_period_id') => $academicPeriodId,
                            $this->aliasField('institution_id') => $institutionId,
                            $this->aliasField('student_id') => $studentId,
                            $this->aliasField('index_id') => $indexId
                        ])
                        ->all();

                    // to update and add new records into the institution_student_indexes
                    if (!$institutionStudentIndexesResults->isEmpty()) {
                        $entity = $institutionStudentIndexesResults->first();
                    } else {
                        $entity = $this->newEntity([
                            'average_index' => 0,
                            'total_index' => 0,
                            'academic_period_id' => $academicPeriodId,
                            'institution_id' => $institutionId,
                            'student_id' => $studentId,
                            'index_id' => $indexId
                        ]);
                    }

                    // if the condition fulfilled then the value will be saved as its value, if not saved as null
                    switch ($operator) {
                        case 1: // '<'
                            if($valueIndexData < $threshold){
                                $valueIndex = $valueIndexData;
                            } else {
                                $valueIndex = null;
                            }
                            break;

                        case 2: // '>'
                            if($valueIndexData > $threshold){
                                $valueIndex = $valueIndexData;
                            } else {
                                $valueIndex = null;
                            }
                            break;

                        case 3: // '='
                            // value index is an array (valueIndex[threshold] = value)
                            if (array_key_exists($threshold, $valueIndexData)) {
                                $valueIndex = 'True';
                            } else {
                                $valueIndex = null;
                            }
                            break;
                    }

                    // saving association to student_indexes_criterias
                    $criteriaData = [
                        'value' => $valueIndex,
                        'indexes_criteria_id' => $indexesCriteriaData->id
                    ];

pr('valueIndexData');
pr($valueIndexData);
// pr('academicPeriodId '. $academicPeriodId);
// pr('institutionId '.$institutionId);
// pr('studentId '.$studentId);
// pr('indexId '.$indexId);

// // pr('institutionStudentIndexesResults');
// // pr($institutionStudentIndexesResults);

// pr('criteriaData');
// pr($criteriaData);
// pr($entity);
// die;

                    if (!$entity->isNew()) {
                        $studentIndexesCriteriaResults = $this->StudentIndexesCriterias->find()
                            ->where([
                                $this->StudentIndexesCriterias->aliasField('institution_student_index_id') => $entity->id,
                                $this->StudentIndexesCriterias->aliasField('indexes_criteria_id') => $indexesCriteriaData->id
                            ])
                            ->all();

                        // find id from db
                        if (!$studentIndexesCriteriaResults->isEmpty()) {
                            $criteriaEntity = $studentIndexesCriteriaResults->first();
                            $criteriaData['id'] = $criteriaEntity->id;
                        }
                    }

                    $data = [];
                    $data['student_indexes_criterias'][] = $criteriaData;

                    $patchOptions = ['validate' => false];
                    $entity = $this->patchEntity($entity, $data, $patchOptions);
pr('before the saving');
pr($entity);
die;
                    $this->save($entity);
                }
            }
        }
    }

    // will update the total index on the institution_student_indexes
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $academicPeriodId = $entity->academic_period_id;
        $institutionId = $entity->institution_id;
        $studentId = $entity->student_id;

        $IndexesCriterias = TableRegistry::get('Indexes.IndexesCriterias');

        $InstitutionStudentIndexesData = $this->find()
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
            ])
            ->all();

        if (!$InstitutionStudentIndexesData->isEmpty()) {
            foreach ($InstitutionStudentIndexesData as $key => $institutionStudentIndexesObj) {
                $InstitutionStudentIndexesid = $institutionStudentIndexesObj->id;
                $StudentIndexesCriteriasResults = $this->StudentIndexesCriterias->find()
                    ->where([$this->StudentIndexesCriterias->aliasField('institution_student_index_id') => $InstitutionStudentIndexesid])
                    ->all();

                $indexTotal = [];
                // to get the total of the index of the student
                foreach ($StudentIndexesCriteriasResults as $key => $studentIndexesCriteriasObj) {
                    $value = $studentIndexesCriteriasObj->value;
                    $indexesCriteriaId = $studentIndexesCriteriasObj->indexes_criteria_id;

                    $indexValue = $this->StudentIndexesCriterias->getIndexValue($value, $indexesCriteriaId, $institutionId, $studentId, $academicPeriodId);
                    $indexTotal[$studentIndexesCriteriasObj->institution_student_index_id] = !empty($indexTotal[$studentIndexesCriteriasObj->institution_student_index_id]) ? $indexTotal[$studentIndexesCriteriasObj->institution_student_index_id] : 0 ;
                    $indexTotal[$studentIndexesCriteriasObj->institution_student_index_id] = $indexTotal[$studentIndexesCriteriasObj->institution_student_index_id] + $indexValue;
                }

                foreach ($indexTotal as $key => $obj) {
                    $this->query()
                        ->update()
                        ->set(['total_index' => $obj])
                        ->where([
                            'id' => $key
                        ])
                        ->execute();
                }
            }
        }
    }

    public function onGetCustomCriteriasElement(Event $event, $action, $entity, $attr, $options=[])
    {
        $IndexesCriterias = TableRegistry::get('Indexes.IndexesCriterias');
        $tableHeaders = $this->getMessage('Indexes.TableHeader');
        array_splice($tableHeaders, 3, 0, __('Value')); // adding value header
        $tableHeaders[] = __('References');
        $tableCells = [];
        $fieldKey = 'indexes_criterias';

        $indexId = $entity->index->id;
        $institutionId = $entity->institution->id;
        $studentId = $entity->user->id;
        $academicPeriodId = $entity->academic_period_id;

        $institutionStudentIndexId = $this->paramsDecode($this->paramsPass(0))['id']; // paramsPass(0) after the hash of Id

        if ($action == 'view') {
            $studentIndexesCriteriasResults = $this->StudentIndexesCriterias->find()
                ->contain(['IndexesCriterias'])
                ->where([
                    $this->StudentIndexesCriterias->aliasField('institution_student_index_id') => $institutionStudentIndexId,
                    $this->StudentIndexesCriterias->aliasField('value') . ' IS NOT NULL'
                ])
                ->order(['criteria','threshold'])
                ->all();

            foreach ($studentIndexesCriteriasResults as $key => $obj) {
                if (isset($obj->indexes_criteria)) {
                    $indexesCriteriasId = $obj->indexes_criteria->id;

                    $criteriaName = $obj->indexes_criteria->criteria;
                    $operator = $obj->indexes_criteria->operator;
                    $threshold = $obj->indexes_criteria->threshold;

                    $value = $this->StudentIndexesCriterias->getValue($institutionStudentIndexId, $indexesCriteriasId);

                    $criteriaDetails = $this->Indexes->getCriteriasDetails($criteriaName);
                    $CriteriaModel = TableRegistry::get($criteriaDetails['model']);

                    if ($value == 'True') {
                        // Comparison like behaviour
                        $LookupModel = TableRegistry::get($criteriaDetails['threshold']['lookupModel']);

                        // to get total number of behaviour
                        $getValueIndex = $CriteriaModel->getValueIndex($institutionId, $studentId, $academicPeriodId, $criteriaName);
                        $quantity = '';
                        if ($getValueIndex[$threshold] > 1) {
                            $quantity = ' ( x'. $getValueIndex[$threshold]. ' )';
                        }

                        $indexValue = '<div style="color : red">' . $obj->indexes_criteria->index_value . $quantity  .'</div>';

                        // for reference tooltip
                        $reference = $CriteriaModel->getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName);

                        // for threshold name
                        $thresholdName = $LookupModel->get($threshold)->name;
                        $threshold = $thresholdName;
                    } else {
                        // numeric value come here (absence quantity, results)
                        // for value
                        $indexValue = '<div style="color : red">'.$obj->indexes_criteria->index_value.'</div>';

                        // for the reference tooltip
                        $reference = $CriteriaModel->getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName);
                    }

                    // blue info tooltip
                    $tooltipReference = '<i class="fa fa-info-circle fa-lg icon-blue" data-placement="left" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="'.$reference.'"></i>';

                    // to put in the table
                    $rowData = [];
                    $rowData[] = __($this->Indexes->getCriteriasDetails($criteriaName)['name']);
                    $rowData[] = $this->Indexes->getCriteriasDetails($criteriaName)['operator'][$operator];
                    $rowData[] = __($threshold);
                    $rowData[] = __($value);
                    $rowData[] = $indexValue;
                    $rowData[] = $tooltipReference;

                    $tableCells [] = $rowData;
                }
            }
        }

        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('Indexes.Indexes/' . $fieldKey, ['attr' => $attr]);
    }


    public function getStudentId($criteriaTable, $afterSaveOrDeleteEntity)
    {
        switch ($criteriaTable->alias()) {
            case 'StudentUser': // The student_id is the Id
                $studentId = $afterSaveOrDeleteEntity->id;
                break;

            case 'SpecialNeeds': // The student_id is the Id
                $studentId = $afterSaveOrDeleteEntity->security_user_id;
                break;
        }

        return $studentId;
    }

    public function getAcademicPeriodId($criteriaTable, $afterSaveOrDeleteEntity)
    {

pr('getAcademicPeriodId');
pr($criteriaTable->alias());
        // afterDelete $afterSaveOrDeleteEntity doesnt have academicPeriodId, every model also have different date
        switch ($criteriaTable->alias()) {
            case 'InstitutionStudentAbsences': // have start date and end date
                $startDate = $afterSaveOrDeleteEntity->start_date;
                $endDate = $afterSaveOrDeleteEntity->end_date;
                $academicPeriodId = $this->AcademicPeriods->getAcademicPeriodId($startDate, $endDate);
                break;

            case 'StudentBehaviours': // have date of behaviours
                $date = $afterSaveOrDeleteEntity->date_of_behaviour;
                $academicPeriodId = $this->AcademicPeriods->getAcademicPeriodIdByDate($date);
                break;

            case 'StudentUser': // no date, will get the current academic period Id
                $academicPeriodId = $this->AcademicPeriods->getCurrent();
                break;

            case 'Students': // no date, will get the current academic period Id
                $academicPeriodId = $this->AcademicPeriods->getCurrent();
                break;

            case 'SpecialNeeds': // have special need date
                $date = $afterSaveOrDeleteEntity->special_need_date;
                $academicPeriodId = $this->AcademicPeriods->getAcademicPeriodIdByDate($date);
                break;
        }

        return $academicPeriodId;
    }

    public function getInstitutionId($criteriaTable, $afterSaveOrDeleteEntity, $academicPeriodId)
    {
        switch ($criteriaTable->alias()) {
            case 'StudentUser':
                $studentId = $afterSaveOrDeleteEntity->id;
                $institutionId = $criteriaTable->getInstitutionIdByUser($studentId, $academicPeriodId);
                break;

            case 'Students':
                $studentId = $afterSaveOrDeleteEntity->student_id;
                $Students = TableRegistry::get('Institution.Students');
                $institutionId = $Students->getInstitutionIdByUser($studentId, $academicPeriodId);
                break;

            case 'SpecialNeeds':
                $studentId = $afterSaveOrDeleteEntity->security_user_id;
                $Students = TableRegistry::get('Institution.Students');
                $institutionId = $Students->getInstitutionIdByUser($studentId, $academicPeriodId);
                break;
        }

        return $institutionId;
    }
}
