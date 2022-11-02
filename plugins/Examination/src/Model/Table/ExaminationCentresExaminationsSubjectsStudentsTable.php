<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use App\Model\Table\ControllerActionTable;

class ExaminationCentresExaminationsSubjectsStudentsTable extends ControllerActionTable {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('ExaminationItems', ['className' => 'Examination.ExaminationItems']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->belongsTo('EducationSubjects', ['className' => 'Education.EducationSubjects']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentresExaminationsStudents', [
            'className' => 'Examination.ExaminationCentresExaminationsStudents',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id']
        ]);
        $this->belongsTo('InstitutionExaminationStudents', [
            'className' => 'Institution.InstitutionExaminationStudents',
            'foreignKey' => ['examination_centre_id', 'examination_id', 'student_id']
        ]);
        $this->belongsTo('ExaminationCentresExaminationsSubjects', [
            'className' => 'Examination.ExaminationCentresExaminationsSubjects',
            'foreignKey' => ['examination_centre_id', 'examination_item_id']
        ]);

        $this->addBehavior('Restful.RestfulAccessControl', [
            'ExamResults' => ['add']
        ]);
        $this->addBehavior('CompositeKey');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.ExaminationResults.afterSave'] = 'examinationResultsAfterSave';
        return $events;
    }

    public function examinationResultsAfterSave(Event $event, $results)
    {
        // used to update total mark whenever an examination mark is added or updated
        $studentId = $results->student_id;
        $examinationCentreId = $results->examination_centre_id;
        $examinationItemId = $results->examination_item_id;
        $examinationId = $results->examination_id;

        $examItem = $this->ExaminationItems->get($examinationItemId, ['contain' => ['ExaminationGradingTypes']]);
        if (!empty($examItem)) {
            $resultType = $examItem->examination_grading_type->result_type;

            if ($resultType == 'MARKS') {
                $marks = $results->marks;
                $totalMark = !is_null($marks) ? ($marks * $examItem->weight) : null;
                $this->updateAll(['total_mark' => $totalMark], [
                    'examination_centre_id' => $examinationCentreId,
                    'student_id' => $studentId,
                    'examination_item_id' => $examinationItemId,
                    'examination_id' => $examinationId
                ]);
            }
        }
    }
}
