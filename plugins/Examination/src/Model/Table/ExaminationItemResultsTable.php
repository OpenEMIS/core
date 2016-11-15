<?php
namespace Examination\Model\Table;

use ArrayObject;

use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Utility\Text;
use Cake\Utility\Security;

use App\Model\Table\AppTable;

class ExaminationItemResultsTable extends AppTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('ExaminationGradingOptions', ['className' => 'Examination.ExaminationGradingOptions']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['index', 'add']
        ]);
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
        if ($entity->isNew()) {
            $hashString = $entity->academic_period_id . ',' . $entity->examination_id . ',' . $entity->education_subject_id . ',' . $entity->student_id;
            $entity->id = Security::hash($hashString, 'sha256');
        }
        $this->getExamGrading($entity);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $this->setTotalMark($entity);
    }

    private function getExamGrading(Entity $entity)
    {
        if (!$entity->has('examination_grading_option_id') && $entity->has('marks') && $entity->marks > 0) {
            $ExaminationItems = TableRegistry::get('Examination.ExaminationItems');
            $examItemEntity = $ExaminationItems
                ->find()
                ->contain(['ExaminationGradingTypes.GradingOptions'])
                ->where([
                    $ExaminationItems->aliasField('examination_id') => $entity->examination_id,
                    $ExaminationItems->aliasField('education_subject_id') => $entity->education_subject_id
                ])
                ->first();

            if ($examItemEntity->has('examination_grading_type')) {
                $resultType = $examItemEntity->examination_grading_type->result_type;
                if ($resultType == 'MARKS') {
                    if ($examItemEntity->examination_grading_type->has('grading_options') && !empty($examItemEntity->examination_grading_type->grading_options)) {
                        foreach ($examItemEntity->examination_grading_type->grading_options as $key => $obj) {
                            if ($entity->marks >= $obj->min && $entity->marks <= $obj->max) {
                                $entity->examination_grading_option_id = $obj->id;
                            }
                        }
                    }
                    $entity->total_mark = round($entity->marks * $examItemEntity->weight, 2);
                } else if ($resultType == 'GRADES') {
                    $entity->total_mark = NULL;
                }
            }
        }
    }

    private function setTotalMark(Entity $entity)
    {
        if ($entity->has('total_mark')) {
            $ExaminationCentreStudents = TableRegistry::get('Examination.ExaminationCentreStudents');
            $ExaminationCentreStudents->updateAll(['total_mark' => $entity->total_mark], [
                'examination_centre_id' => $entity->examination_centre_id,
                'student_id' => $entity->student_id,
                'education_subject_id' => $entity->education_subject_id,
                'institution_id' => $entity->institution_id,
                'academic_period_id' => $entity->academic_period_id,
                'examination_id' => $entity->examination_id
            ]);
        }
    }
}
