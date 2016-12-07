<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;

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
        $events['Model.InstitutionStudentAbsences.afterSave'] = 'afterSaveOrDelete';
        $events['Model.InstitutionStudentAbsences.afterDelete'] = 'afterSaveOrDelete';
        return $events;
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no');
        $this->field('index_id',['visible' => false]);
        $this->field('academic_period_id',['visible' => false]);
        $this->field('average_index',['visible' => false]);
        $this->field('student_id');

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

        // element control
        $session = $this->request->session();

        $institutionId = $session->read('Institution.Institutions.id');
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
        $institutionId = $afterSaveOrDeleteEntity->institution_id;
        $studentId = $afterSaveOrDeleteEntity->student_id;

        $IndexesCriterias = TableRegistry::get('Indexes.IndexesCriterias');
        $criteriaTable = TableRegistry::get($criteriaModel);

        if (isset($afterSaveOrDeleteEntity->academic_period_id)) {
            $academicPeriodId = $afterSaveOrDeleteEntity->academic_period_id;
        } else {
            // afterDelete $afterSaveOrDeleteEntity doesnt have academicPeriodId
            $startDate = $afterSaveOrDeleteEntity->start_date;
            $endDate = $afterSaveOrDeleteEntity->end_date;
            $academicPeriodId = $this->AcademicPeriods->getAcademicPeriodId($startDate, $endDate);
        }

        // to get the indexes criteria to get the value on the student_indexes_criterias
        $indexesCriteriaResults = $IndexesCriterias->find()
            ->where([$IndexesCriterias->aliasField('criteria') => $criteriaModel])
            ->all();

        if (!$indexesCriteriaResults->isEmpty()) {
            foreach ($indexesCriteriaResults as $key => $indexesCriteriaData) {
                $indexId = $indexesCriteriaData->index_id;

                $params = new ArrayObject([
                    'institution_id' => $institutionId,
                    'student_id' => $studentId
                ]);

                $event = $criteriaTable->dispatchEvent('Model.InstitutionStudentIndexes.calculateIndexValue', [$params], $this);

                if ($event->isStopped()) {
                    $mainEvent->stopPropagation();
                    return $event->result;
                }

                $valueIndex = $event->result;

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
                foreach ($StudentIndexesCriteriasResults as $key => $studentIndexesCriteriasObj) {
                    if (!empty($studentIndexesCriteriasObj->value)) {
                        $value = $studentIndexesCriteriasObj->value;
                        $indexesCriteriaId = $studentIndexesCriteriasObj->indexes_criteria_id;

                        $indexValue = $this->StudentIndexesCriterias->getIndexValue($value, $indexesCriteriaId);
                        $indexTotal[$studentIndexesCriteriasObj->institution_student_index_id] = !empty($indexTotal[$studentIndexesCriteriasObj->institution_student_index_id]) ? $indexTotal[$studentIndexesCriteriasObj->institution_student_index_id] : 0 ;
                        $indexTotal[$studentIndexesCriteriasObj->institution_student_index_id] = $indexTotal[$studentIndexesCriteriasObj->institution_student_index_id] + $indexValue;
                    }
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
}
