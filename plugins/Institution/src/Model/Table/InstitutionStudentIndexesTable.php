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

        // $this->hasMany('StudentIndexesCriterias', ['className' => 'Indexes.StudentIndexesCriterias']);
        $this->hasMany('StudentIndexesCriterias', ['className' => 'Institution.StudentIndexesCriterias', 'dependent' => true, 'cascadeCallbacks' => true]);

        $this->toggle('add', false);
        $this->toggle('search', false);
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
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $requestQuery = $this->request->query;

        return $query = $query
            ->where([$this->aliasField('index_id') => $requestQuery['index_id']]);
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function institutionStudentAbsencesAfterSaveOrDelete(Event $event, Entity $institutionStudentAbsencesEntity)
    {
// pr($institutionStudentAbsencesEntity);
        $criteriaModel = $institutionStudentAbsencesEntity->source();
        $academicPeriodId = $institutionStudentAbsencesEntity->academic_period_id;
        $institutionId = $institutionStudentAbsencesEntity->institution_id;
        $studentId = $institutionStudentAbsencesEntity->student_id;
// pr($academicPeriodId);
        $IndexesCriterias = TableRegistry::get('Indexes.IndexesCriterias');
        $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');

        $totalAbsence = $InstitutionStudentAbsences->totalAbsences($studentId);
// pr($totalAbsence);
// die;
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
// pr('here');
                        $value = $indexesCriteriaData->index_value;
                    } else {
// pr('there');
                        $value = 0;
                    }
                }
// pr($value);
// die;
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

// pr('------');
// pr($value);
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
                $this->save($entity);
            }
        }
    }
}
