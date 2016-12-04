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
        $events['Model.InstitutionStudentAbsences.afterSave'] = 'institutionStudentAbsencesAfterSaveOrDelete';
        $events['Model.InstitutionStudentAbsences.afterDelete'] = 'institutionStudentAbsencesAfterSaveOrDelete';
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

    public function institutionStudentAbsencesAfterSaveOrDelete(Event $event, Entity $institutionStudentAbsencesEntity)
    {
        $criteriaModel = $institutionStudentAbsencesEntity->source();
        $startDate = $institutionStudentAbsencesEntity->start_date;
        $endDate = $institutionStudentAbsencesEntity->end_date;

        if (isset($institutionStudentAbsencesEntity->academic_period_id)) {
            $academicPeriodId = $institutionStudentAbsencesEntity->academic_period_id;
        } else {
            // afterDelete $institutionStudentAbsencesEntity doesnt have academicPeriodId
            $academicPeriodId = $this->AcademicPeriods->getAcademicPeriodId($startDate, $endDate);
        }

        $institutionId = $institutionStudentAbsencesEntity->institution_id;
        $studentId = $institutionStudentAbsencesEntity->student_id;

        $IndexesCriterias = TableRegistry::get('Indexes.IndexesCriterias');
        $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');

        $totalAbsence = $InstitutionStudentAbsences->calculateValueIndex($institutionId, $studentId);

        $indexesCriteriaResults = $IndexesCriterias->find()
            ->where([$IndexesCriterias->aliasField('criteria') => $criteriaModel])
            ->all();

        if (!$indexesCriteriaResults->isEmpty()) {
            foreach ($indexesCriteriaResults as $key => $indexesCriteriaData) {
                $indexId = $indexesCriteriaData->index_id;
                $operator = $indexesCriteriaData->operator;
                $threshold = $indexesCriteriaData->threshold;
                $value = 0;

                if ($operator == 1) {
                    if ($totalAbsence < $threshold) {
                        $value = $indexesCriteriaData->index_value;
                    } else {
                        $value = 0;
                    }
                }

                if ($operator == 2) {
                    if ($totalAbsence > $threshold) {
                        $value = $indexesCriteriaData->index_value;
                    } else {
                        $value = 0;
                    }
                }

                $results = $this->find()
                    ->where([
                        $this->aliasField('academic_period_id') => $academicPeriodId,
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('student_id') => $studentId,
                        $this->aliasField('index_id') => $indexId
                    ])
                    ->all();

                if (!$results->isEmpty()) {
                    $entity = $results->first();
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

                $criteriaData = [
                    'value' => $value,
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

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $academicPeriodId = $entity->academic_period_id;
        $institutionId = $entity->institution_id;
        $studentId = $entity->student_id;

        $StudentIndexesCriterias = TableRegistry::get('Institution.StudentIndexesCriterias');

        $InstitutionStudentIndexesData = $this->find()
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_id') => $studentId,
            ])
            ->all();

        if (!$InstitutionStudentIndexesData->isEmpty()) {
            foreach ($InstitutionStudentIndexesData as $key => $obj) {
                $InstitutionStudentIndexesid = $obj->id;

                $StudentIndexesCriteriasResults = $StudentIndexesCriterias->find()
                    ->where([$StudentIndexesCriterias->aliasField('institution_student_index_id') => $InstitutionStudentIndexesid])
                    ->all();

                $indexTotal = [];
                foreach ($StudentIndexesCriteriasResults as $key => $obj) {
                    $indexTotal [$obj->institution_student_index_id] = !empty($indexTotal [$obj->institution_student_index_id]) ? $indexTotal [$obj->institution_student_index_id] : 0;
                    $indexTotal [$obj->institution_student_index_id] = $indexTotal [$obj->institution_student_index_id] + $obj->value;
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
