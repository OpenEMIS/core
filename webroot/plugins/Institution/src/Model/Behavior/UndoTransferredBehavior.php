<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Institution\Model\Behavior\UndoBehavior;

class UndoTransferredBehavior extends UndoBehavior {
    public function initialize(array $config) {
        parent::initialize($config);
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Undo.'.'get'.$this->undoAction.'Students'] = 'onGet'.$this->undoAction.'Students';
        $events['Undo.'.'processSave'.$this->undoAction.'Students'] = 'processSave'.$this->undoAction.'Students';
        return $events;
    }

    public function onGetTransferredStudents(Event $event, $data) {
        //this function is to re-check if the student try to undo not the latest status.
        //if yes, then the checkbox will be replaced by tooltip (not able to revert/undo)
        return $this->getStudents($data); 
    }

    public function processSaveTransferredStudents(Event $event, Entity $entity, ArrayObject $data) 
    {
        $StudentAdmissionTable = TableRegistry::get('Institution.StudentAdmission');
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

                    $prevInstitutionStudent = $this->deleteEnrolledStudents($studentId, $this->statuses['TRANSFERRED']);
                    $whereId = '';
                    $whereConditions = '';

                    if ($prevInstitutionStudent) {
                        $whereId = [
                            'id' => $prevInstitutionStudent->id
                        ];
                    } else {
                        $whereConditions = [
                            'institution_id' => $prevInstitutionStudent->institution_id, //for transferred status, then need to enrolled back the status on the previous institution
                            'academic_period_id' => $selectedPeriod,
                            'education_grade_id' => $prevInstitutionStudent->education_grade_id,
                            'student_status_id' => $selectedStatus,
                            'student_id' => $studentId
                        ];
                    }
                    
                    $this->updateStudentStatus('TRANSFERRED', $whereId, $whereConditions);

                    //update transfer request (student admission) to undo status.
                    $conditions = [
                        'student_id' => $studentId,
                        'status' => 1, //undo approved status
                        'institution_id' => $institutionId,
                        'academic_period_id' => $selectedPeriod,
                        'new_education_grade_id' => $selectedGrade,
                        'previous_institution_id' => $prevInstitutionStudent->institution_id,
                        'type' => 2 //transfer
                    ];

                    $StudentAdmissionTable->updateAll(
                        ['status' => 3], //status 3 = undo
                        [$conditions]
                    );
                }
            }
        }
        return $studentIds;
    }
}
