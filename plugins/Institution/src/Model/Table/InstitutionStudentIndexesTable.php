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
        $this->belongsTo('Risks', ['className' => 'Risk.Risks', 'foreignKey' =>'index_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' =>'student_id']);

        $this->hasMany('StudentIndexesCriterias', ['className' => 'Institution.StudentIndexesCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->addBehavior('User.AdvancedNameSearch');

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        // for search
        $events['ControllerAction.Model.getSearchableFields'] = ['callable' => 'getSearchableFields', 'priority' => 5];

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

    public function getSearchableFields(Event $event, ArrayObject $searchableFields) {
        $searchableFields[] = 'student_id';
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openEMIS_ID');
        $this->field('index_id',['visible' => false]);
        $this->field('average_index',['visible' => false]);
        $this->field('student_id', [
            'sort' => ['field' => 'Users.first_name']
        ]);
        $this->field('total_index', ['sort' => true]);
        $this->field('academic_period_id',['visible' => false]);

        $session = $this->request->session();
        $requestQuery = $this->request->query;
        $params = $this->paramsDecode($requestQuery['queryString']);
        $institutionId = $session->read('Institution.Institutions.id');

        // back buttons
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Indexes',
            'index'
        ];
        $toolbarButtonsArray['back'] = $this->getButtonTemplate();
        $toolbarButtonsArray['back']['label'] = '<i class="fa kd-back"></i>';
        $toolbarButtonsArray['back']['attr']['title'] = __('Back');
        $toolbarButtonsArray['back']['url'] = $url;

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end back buttons

        // element control
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $selectedAcademicPeriodId = $params['academic_period_id'];

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
        $params = $this->paramsDecode($requestQuery['queryString']);
        $session = $this->request->session();

        $institutionId = $session->read('Institution.Institutions.id');
        $academicPeriodId = $params['academic_period_id'];
        $classId = $extra['selectedClass'];

        $conditions = [
            $this->aliasField('index_id') => $params['index_id'],
            $this->aliasField('academic_period_id') => $academicPeriodId,
            $this->aliasField('total_index') . ' >' => 0
        ];

        if ($classId > 0) {
            $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
            $studentList = $InstitutionClassStudents->getStudentsList($academicPeriodId, $institutionId, $classId);

            $conditions = [
                $this->aliasField('index_id') => $params['index_id'],
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('student_id') . ' IN ' => $studentList,
                $this->aliasField('total_index') . ' >' => 0
            ];
        }

        // for sorting of student_id by name and total_index
        $sortList = [
            $this->fields['student_id']['sort']['field'],
            'total_index'
        ];

        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
        // end sorting (refer to commentsTable)

        $query->where([$conditions]);

        $search = $this->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('name');
        $this->field('grade');
        $this->field('class');
        $this->field('index_id', ['after' => 'academic_period_id']);
        $this->field('total_index', ['after' => 'index_id']);
        $this->field('indexes_criterias', ['type' => 'custom_criterias', 'after' => 'total_index']);
        $this->field('average_index', ['visible' => false]);
        $this->field('student_id', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);

        // BreadCrumb
        $requestQuery = $this->request->query;
        $params = $this->paramsDecode($requestQuery['queryString']);

        $indexId = $params['index_id'];
        $academicPeriodId = $params['academic_period_id'];
        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'InstitutionStudentIndexes'
        ];

        $indexesUrl = $this->setQueryString($url, [
            'index_id' => $indexId,
            'academic_period_id' => $academicPeriodId
        ]);

        $this->Navigation->substituteCrumb('Institution Student Indexes', 'Institution Student Indexes', $indexesUrl);

        // Header
        $studentName = $entity->user->first_name . ' ' . $entity->user->last_name;
        $header = $studentName . ' - ' . __(Inflector::humanize(Inflector::underscore($this->alias())));

        $this->controller->set('contentHeader', $header);
    }

    public function onGetOpenemisId(Event $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function onGetName(Event $event, Entity $entity)
    {
        return $entity->user->name;
    }

    public function onGetGrade(Event $event, Entity $entity)
    {
        // some class not configure in the institutionClassStudents, therefore using the institutionStudents
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $InstitutionStudents = TableRegistry::get('Institution.InstitutionStudents');
        $studentId = $entity->student_id;
        $academicPeriodId = $entity->academic_period_id;

        $educationGradeData = $InstitutionStudents->find()
            ->where([
                'student_id' => $studentId,
                'academic_period_id' => $academicPeriodId,
                'student_status_id' => 1 // enrolled status
            ])
            ->first();

        $educationGradesName = '';
        if (isset($educationGradeData->education_grade_id)) {
            $educationGradesName = $EducationGrades->get($educationGradeData->education_grade_id)->name;
        }

        return $educationGradesName;
    }

    public function onGetClass(Event $event, Entity $entity)
    {
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $studentId = $entity->student_id;
        $academicPeriodId = $entity->academic_period_id;

        $institutionClassesData = $InstitutionClassStudents->find()
            ->where([
                'student_id' => $studentId,
                'academic_period_id' => $academicPeriodId,
                'student_status_id' => 1 // enrolled status
            ])
            ->first();

        $institutionClassesName = '';
        if (isset($institutionClassesData->institution_class_id)) {
            $institutionClassesName = $InstitutionClasses->get($institutionClassesData->institution_class_id)->name;
        }

        return $institutionClassesName;
    }

    public function afterSaveOrDelete(Event $mainEvent, Entity $afterSaveOrDeleteEntity)
    {
        $criteriaModel = $afterSaveOrDeleteEntity->source();

        // on student admission this will be updated (student gender, guardians, student repeated)
        $consolidatedModel = ['Institution.StudentUser', 'Student.Guardians', 'Institution.IndividualPromotion'];

        if (in_array($criteriaModel, $consolidatedModel)) {
            $criteriaModel = 'Institution.Students';
        }

        $RisksCriterias = TableRegistry::get('Risk.RisksCriterias');
        $criteriaTable = TableRegistry::get($criteriaModel);

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

        if (!empty($institutionId)) {
            $criteriaRecord = $this->Indexes->getCriteriaByModel($criteriaModel, $institutionId);

            foreach ($criteriaRecord as $criteriaDataKey => $criteriaDataObj) {
                // to get the indexes criteria to get the value on the student_indexes_criterias
                $indexesCriteriaResults = $RisksCriterias->find('ActiveIndexesCriteria', ['criteria_key' => $criteriaDataKey, 'institution_id' => $institutionId]);

                if (!$indexesCriteriaResults->isEmpty()) {
                    foreach ($indexesCriteriaResults as $key => $indexesCriteriaData) {
                        $indexId = $indexesCriteriaData->index_id;
                        $threshold = $indexesCriteriaData->threshold;
                        $operator = $indexesCriteriaData->operator;
                        $criteria = $indexesCriteriaData->criteria;

                        $params = new ArrayObject([
                            'institution_id' => $institutionId,
                            'student_id' => $studentId,
                            'academic_period_id' => $academicPeriodId,
                            'criteria_name' => $criteriaDataKey
                        ]);

                        $event = $criteriaTable->dispatchEvent('Model.InstitutionStudentIndexes.calculateIndexValue', [$params], $this);

                        if ($event->isStopped()) {
                            $mainEvent->stopPropagation();
                            return $event->result;
                        }

                        $valueIndexData = $event->result;


                        // if the condition fulfilled then the value will be saved as its value, if not saved as null
                        switch ($operator) {
                            case 1: // '<='
                                if($valueIndexData <= $threshold){
                                    $valueIndex = $valueIndexData;
                                } else {
                                    $valueIndex = null;
                                }
                                break;

                            case 2: // '>='
                                if($valueIndexData >= $threshold){
                                    $valueIndex = $valueIndexData;
                                } else {
                                    $valueIndex = null;
                                }
                                break;

                            case 3: // '='
                            case 11: // for status Repeated
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

                        $conditions = [
                            $this->aliasField('academic_period_id') => $academicPeriodId,
                            $this->aliasField('institution_id') => $institutionId,
                            $this->aliasField('student_id') => $studentId,
                            $this->aliasField('index_id') => $indexId
                        ];

                        if ($criteria == 'SpecialNeeds') {
                            if (isset($afterSaveOrDeleteEntity->trigger_from) && $afterSaveOrDeleteEntity->trigger_from == 'shell') {
                            } else {
                                $conditions = [
                                    $this->aliasField('academic_period_id') => $academicPeriodId,
                                    $this->aliasField('student_id') => $studentId,
                                    $this->aliasField('index_id') => $indexId
                                ];
                            }
                        }

                        $institutionStudentIndexesResults = $this->find()
                            ->where([$conditions])
                            ->all();

                        // to update and add new records into the institution_student_indexes
                        if (!$institutionStudentIndexesResults->isEmpty()) {
                            // $entity = $institutionStudentIndexesResults->first();
                            foreach ($institutionStudentIndexesResults as $institutionStudentIndexesResultsObj) {
                                $entity = $institutionStudentIndexesResultsObj;

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

                                $data = [];
                                $data['student_indexes_criterias'][] = $criteriaData;

                                $patchOptions = ['validate' => false];
                                $entity = $this->patchEntity($entity, $data, $patchOptions);

                                $this->save($entity);
                            }
                        } else {
                            $entity = $this->newEntity([
                                'average_index' => 0,
                                'total_index' => 0,
                                'academic_period_id' => $academicPeriodId,
                                'institution_id' => $institutionId,
                                'student_id' => $studentId,
                                'index_id' => $indexId
                            ]);

                            $data = [];
                            $data['student_indexes_criterias'][] = $criteriaData;

                            $patchOptions = ['validate' => false];
                            $entity = $this->patchEntity($entity, $data, $patchOptions);

                            $this->save($entity);
                        }
                    }
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

        // $IndexesCriterias = TableRegistry::get('Indexes.IndexesCriterias');

        $InstitutionStudentIndexesData = $this->find()
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
            ])
            ->all();

        if (!$InstitutionStudentIndexesData->isEmpty()) {
            foreach ($InstitutionStudentIndexesData as $institutionStudentIndexesObj) {
                $InstitutionStudentIndexesid = $institutionStudentIndexesObj->id;

                $StudentIndexesCriteriasResults = $this->StudentIndexesCriterias->find()
                    ->where([$this->StudentIndexesCriterias->aliasField('institution_student_index_id') => $InstitutionStudentIndexesid])
                    ->all();

                $indexTotal = [];
                // to get the total of the index of the student
                foreach ($StudentIndexesCriteriasResults as $studentIndexesCriteriasObj) {
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
        // $IndexesCriterias = TableRegistry::get('Indexes.IndexesCriterias');
        $tableHeaders = $this->getMessage('Risk.TableHeader');
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
                ->contain(['RisksCriterias'])
                ->where([
                    $this->StudentIndexesCriterias->aliasField('institution_student_index_id') => $institutionStudentIndexId,
                    $this->StudentIndexesCriterias->aliasField('value') . ' IS NOT NULL'
                ])
                ->order(['criteria','threshold'])
                ->all();

            foreach ($studentIndexesCriteriasResults as $obj) {
                if (isset($obj->indexes_criteria)) {
                    $indexesCriteriasId = $obj->indexes_criteria->id;

                    $criteriaName = $obj->indexes_criteria->criteria;
                    $operatorId = $obj->indexes_criteria->operator;
                    $operator = $this->Indexes->getOperatorDetails($operatorId);
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
                        if ($thresholdName == 'Repeated') {
                            $threshold = $this->Indexes->getCriteriasDetails($criteriaName)['threshold']['value']; // 'Yes'
                        }
                    } else {
                        // numeric value come here (absence quantity, results)
                        // for value
                        $indexValue = '<div style="color : red">'.$obj->indexes_criteria->index_value.'</div>';

                        // for the reference tooltip
                        $reference = $CriteriaModel->getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName);
                    }

                    // blue info tooltip
                    $tooltipReference = '<i class="fa fa-info-circle fa-lg icon-blue" data-placement="left" data-toggle="tooltip" data-animation="false" data-container="body" title="" data-html="true" data-original-title="'.$reference.'"></i>';

                    if (!is_numeric($threshold)) {
                        $threshold = __($threshold);
                    }

                    if (!is_numeric($value)) {
                        $value = __($value);
                    }

                    // to put in the table
                    $rowData = [];
                    $rowData[] = __($this->Indexes->getCriteriasDetails($criteriaName)['name']);
                    $rowData[] = __($operator);
                    $rowData[] = $threshold;
                    $rowData[] = $value;
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
            case 'Students': // The student_id is the Id
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
        $institutionId = null;
        switch ($criteriaTable->alias()) {
            case 'Students':
                // guardian will have student_id, while gender only have id
                $studentId = !empty($afterSaveOrDeleteEntity->student_id) ? $afterSaveOrDeleteEntity->student_id : $afterSaveOrDeleteEntity->id;

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
