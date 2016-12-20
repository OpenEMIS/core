<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
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
        $this->toggle('view', false);
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

        // // overage
        // $events['Model.IndividualPromotion.afterSave'] = 'afterSaveOrDelete';
        // $events['Model.IndividualPromotion.afterDelete'] = 'afterSaveOrDelete';
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no');
        $this->field('index_id',['visible' => false]);
        $this->field('academic_period_id',['visible' => false]);
        $this->field('average_index',['visible' => false]);
        $this->field('student_id');

        $session = $this->request->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $userId = $session->read('Auth.User.id');
        $indexId = $this->request->query['index_id'];

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
            'index_id' => $indexId
        ]);

        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);
        // end generate buttons

        // element control
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();
        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');

        $selectedAcademicPeriodId = $this->queryString('academic_period_id', $academicPeriodOptions);
        $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriodId, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noClasses')),
            'callable' => function($id) use ($Classes, $institutionId) {
                return $Classes->find()
                    ->where([
                        $Classes->aliasField('institution_id') => $institutionId,
                        $Classes->aliasField('academic_period_id') => $id
                    ])
                    ->count();
            }
        ]);
        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

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
            'name' => 'Institution.Indexes/controls',
            'data' => [
                'academicPeriodOptions'=>$academicPeriodOptions,
                'selectedAcademicPeriod'=>$selectedAcademicPeriodId,
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
        $academicPeriodId = $extra['selectedAcademicPeriodId'];
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

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function afterSaveOrDelete(Event $mainEvent, Entity $afterSaveOrDeleteEntity)
    {
        $criteriaModel = $afterSaveOrDeleteEntity->source();

        if ($criteriaModel == 'Institution.IndividualPromotion') {
            $criteriaModel = 'Institution.Students';
        }

        $IndexesCriterias = TableRegistry::get('Indexes.IndexesCriterias');
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

        // to get the indexes criteria to get the value on the student_indexes_criterias
        $indexesCriteriaResults = $IndexesCriterias->find()
            ->where([$IndexesCriterias->aliasField('criteria') => $criteriaModel])
            ->all();

// pr('afterSaveOrDelete');
// pr('criteriaModel: '. $criteriaModel);
// pr('institutionId: '. $institutionId);
// pr('studentId: '. $studentId);
// pr('academicPeriodId: '. $academicPeriodId);
// pr($afterSaveOrDeleteEntity);
// pr($indexesCriteriaResults->toArray());
// die;
        if (!$indexesCriteriaResults->isEmpty()) {
            foreach ($indexesCriteriaResults as $key => $indexesCriteriaData) {
                $indexId = $indexesCriteriaData->index_id;
                $threshold = $indexesCriteriaData->threshold;
                $operator = $indexesCriteriaData->operator;

                $params = new ArrayObject([
                    'institution_id' => $institutionId,
                    'student_id' => $studentId,
                    'academic_period_id' => $academicPeriodId
                ]);

                $event = $criteriaTable->dispatchEvent('Model.InstitutionStudentIndexes.calculateIndexValue', [$params], $this);

                if ($event->isStopped()) {
                    $mainEvent->stopPropagation();
                    return $event->result;
                }

                $valueIndexData = $event->result;

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
// pr($operator);
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
// pr($threshold);
                        // value index is an array (valueIndex[threshold] = value)
                        if (array_key_exists($threshold, $valueIndexData)) {
// pr('masuk sini');
                            $valueIndex = 'True';
                        } else {
                            $valueIndex = null;
                        }
                        break;
                }
// pr($valueIndexData);
// die;
                // saving association to student_indexes_criterias
                $criteriaData = [
                    'value' => $valueIndex,
                    'indexes_criteria_id' => $indexesCriteriaData->id
                ];

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
                $this->save($entity);
            }
        }
// pr('here??');
// die;
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
// pr($institutionStudentIndexesObj);
                $InstitutionStudentIndexesid = $institutionStudentIndexesObj->id;
                $StudentIndexesCriteriasResults = $this->StudentIndexesCriterias->find()
                    ->where([$this->StudentIndexesCriterias->aliasField('institution_student_index_id') => $InstitutionStudentIndexesid])
                    ->all();

                $indexTotal = [];
                // to get the total of the index of the student
                foreach ($StudentIndexesCriteriasResults as $key => $studentIndexesCriteriasObj) {
// pr($studentIndexesCriteriasObj);
                    if (!is_null($studentIndexesCriteriasObj->value)) {
// pr('here?');
                        $value = $studentIndexesCriteriasObj->value;
                        $indexesCriteriaId = $studentIndexesCriteriasObj->indexes_criteria_id;

                        $indexValue = $this->StudentIndexesCriterias->getIndexValue($value, $indexesCriteriaId, $institutionId, $studentId, $academicPeriodId);
                        $indexTotal[$studentIndexesCriteriasObj->institution_student_index_id] = !empty($indexTotal[$studentIndexesCriteriasObj->institution_student_index_id]) ? $indexTotal[$studentIndexesCriteriasObj->institution_student_index_id] : 0 ;
                        $indexTotal[$studentIndexesCriteriasObj->institution_student_index_id] = $indexTotal[$studentIndexesCriteriasObj->institution_student_index_id] + $indexValue;
                    }
                }
// pr('indexTotal');
// pr($indexTotal);
// die;

                foreach ($indexTotal as $key => $obj) {
// pr('empty ????');
// pr($key);
// pr($obj);
                    $this->query()
                        ->update()
                        ->set(['total_index' => $obj])
                        ->where([
                            'id' => $key
                        ])
                        ->execute();
                }
// die;
            }
        }
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

            case 'Guardians': // no date, will get the current academic period Id
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

            case 'Guardians':
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
