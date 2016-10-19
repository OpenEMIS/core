<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Institution\Model\Behavior\UndoBehavior;

class UndoDropoutBehavior extends UndoBehavior {
    public function initialize(array $config) {
        parent::initialize($config);
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Undo.'.'get'.$this->undoAction.'Students'] = 'onGet'.$this->undoAction.'Students';
        $events['Undo.'.'processSave'.$this->undoAction.'Students'] = 'processSave'.$this->undoAction.'Students';
        return $events;
    }

    public function onGetDropoutStudents(Event $event, $data) {
        //this function is to re-check if the student try to undo not the latest status.
        //if yes, then the checkbox will be replaced by tooltip (not able to revert/undo)
        return $this->getStudents($data); 
    }

    public function processSaveDropoutStudents(Event $event, Entity $entity, ArrayObject $data) 
    {
        $StudentDropoutTable = TableRegistry::get('Institution.StudentDropout');
        
        $studentIds = [];

        $institutionId = $entity->institution_id;
        $selectedPeriod = $entity->academic_period_id;
        $selectedGrade = $entity->education_grade_id;
        $selectedStatus = $entity->student_status_id;

        if (isset($entity->students)) {
            foreach ($entity->students as $key => $obj) {
                $studentId = $obj['id'];
                if ($studentId != 0) {
                    $studentIds[$studentId] = $studentId;

                    $this->deleteEnrolledStudents($studentId, $this->statuses['DROPOUT']);

                    $where = [
                        'institution_id' => $institutionId,
                        'academic_period_id' => $selectedPeriod,
                        'education_grade_id' => $selectedGrade,
                        'student_status_id' => $selectedStatus,
                        'student_id' => $studentId
                    ];
                    
                    $this->updateStudentStatus('CURRENT', $where);

                    //update dropout request (institution_student_dropout) to undo status.
                    $conditions = [
                        'student_id' => $studentId,
                        'status' => 1, //undo approved status
                        'institution_id' => $institutionId,
                        'academic_period_id' => $selectedPeriod,
                        'education_grade_id' => $selectedGrade
                    ];

                    $StudentDropoutTable->updateAll(
                        ['status' => 3], //status 3 = undo
                        [$conditions]
                    );
                }
            }
        }
        return $studentIds;
    }
}
