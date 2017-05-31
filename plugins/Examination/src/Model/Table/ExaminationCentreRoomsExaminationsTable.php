<?php
namespace Examination\Model\Table;

use ArrayObject;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class ExaminationCentreRoomsExaminationsTable extends ControllerActionTable
{
    private $queryString;
    private $examCentreId = null;

    public function initialize(array $config) {
        parent::initialize($config);
        $this->belongsTo('ExaminationCentreRooms', ['className' => 'Examination.ExaminationCentreRooms']);
        $this->belongsTo('Examinations', ['className' => 'Examination.Examinations']);
        $this->belongsTo('ExaminationCentres', ['className' => 'Examination.ExaminationCentres']);
        $this->hasMany('ExaminationCentreRoomsExaminationsInvigilators', [
            'className' => 'Examination.ExaminationCentreRoomsExaminationsInvigilators',
            'foreignKey' => ['examination_centre_room_id', 'examination_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
        $this->hasMany('ExaminationCentreRoomsExaminationsStudents', [
            'className' => 'Examination.ExaminationCentreRoomsExaminationsStudents',
            'foreignKey' => ['examination_centre_room_id', 'examination_id'],
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('CompositeKey');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.ExaminationCentreRooms.afterSave'] = 'examinationCentreRoomsAfterSave';
        $events['Model.ExaminationCentreExaminations.afterSave'] = 'examinationCentreExaminationsAfterSave';
        return $events;
    }

    // used when new room is added to an exam centre
    // add all linked exams to new room
    public function examinationCentreRoomsAfterSave(Event $event, $roomEntity)
    {
        if (!empty($roomEntity) && $roomEntity->isNew()) {
            $ExamCentresExams = TableRegistry::get('Examination.ExaminationCentresExaminations');
            $linkedExaminations = $ExamCentresExams->find()
                ->select([$ExamCentresExams->aliasField('examination_id')])
                ->where([$ExamCentresExams->aliasField('examination_centre_id') => $roomEntity->examination_centre_id])
                ->toArray();

            $obj = [];
            $obj['examination_centre_id'] = $roomEntity->examination_centre_id;
            $obj['examination_centre_room_id'] = $roomEntity->id;

            $newEntites = [];
            foreach($linkedExaminations as $exam) {
                $obj['examination_id'] = $exam->examination_id;
                $newEntites[] = $this->newEntity($obj);
            }

            $this->saveMany($newEntites);
        }
    }

    // used when new exam centres are linked to an exam
    // add all rooms to exam
    public function examinationCentreExaminationsAfterSave(Event $event, $examCentreExamEntity)
    {
        if (!empty($examCentreExamEntity) && $examCentreExamEntity->isNew()) {
            $examCentreRooms = $this->ExaminationCentreRooms->find()
                ->select([$this->ExaminationCentreRooms->aliasField('id')])
                ->where([$this->ExaminationCentreRooms->aliasField('examination_centre_id') => $examCentreExamEntity->examination_centre_id])
                ->toArray();

            $obj = [];
            $obj['examination_centre_id'] = $examCentreExamEntity->examination_centre_id;
            $obj['examination_id'] = $examCentreExamEntity->examination_id;

            $newEntites = [];
            foreach($examCentreRooms as $room) {
                $obj['examination_centre_room_id'] = $room->id;
                $newEntites[] = $this->newEntity($obj);
            }

            $this->saveMany($newEntites);
        }
    }
}
