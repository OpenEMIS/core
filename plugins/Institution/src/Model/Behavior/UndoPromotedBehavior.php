<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Institution\Model\Behavior\UndoBehavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;

class UndoPromotedBehavior extends UndoBehavior {
	public function initialize(array $config) {
		parent::initialize($config);
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Undo.'.'get'.$this->undoAction.'Students'] = 'onGet'.$this->undoAction.'Students';
		$events['Undo.'.'processSave'.$this->undoAction.'Students'] = 'processSave'.$this->undoAction.'Students';
		return $events;
	}

	public function onGetPromotedStudents(Event $event, $data) {
		return $this->getStudents($data);
	}

	public function processSavePromotedStudents(Event $event, Entity $entity, ArrayObject $data) 
	{
		//echo "<pre>"; print_r($entity);die;
		$studentIds = [];

		$undoPromote  = '';
		$institutionId = $entity->institution_id;
		$selectedPeriod = $entity->academic_period_id;
		$selectedGrade = $entity->education_grade_id;
		$selectedStatus = $entity->student_status_id;

		$institutionStudent = TableRegistry::get('institution_students');
		$institution = TableRegistry::get('institutions');
		$StudentStatuses = TableRegistry::get('Student.StudentStatuses');

		if (isset($entity->students)) {
			foreach ($entity->students as $key => $obj) {
				$studentId = $obj['id'];
				if ($studentId != 0) {
					$studentIds[$studentId] = $studentId;
					$currentId = $StudentStatuses->getIdByCode('CURRENT');
					$promoteId = $StudentStatuses->getIdByCode('PROMOTED');
					//POCOR-6992 start
					$studentEnrollRecord = $institutionStudent->find()->where(['student_status_id'=>$currentId, 'student_id'=>$studentId])->first();
					$enrolledInstitutionId = '';
					if(!empty($studentEnrollRecord)){
						$enrolledInstitutionId = $studentEnrollRecord->institution_id;
						$getInstitutions = $institution->find()->where(['id'=>$enrolledInstitutionId])->first();
						$institutionCode = $getInstitutions->code;
						$institutionName = $getInstitutions->name;
					}

					$studentPromoteRecord = $institutionStudent->find()->where(['student_status_id'=>$promoteId, 'student_id'=>$studentId,'academic_period_id'=>$entity->academic_period_id])->first();
					$promoteInstitutionId = $studentPromoteRecord->institution_id;
					if($promoteInstitutionId != $enrolledInstitutionId && !empty($enrolledInstitutionId)){
						$message = __('There is an existing enrolment. Please contact ')."$institutionCode" .' - '. $institutionName;
			            return false; //POCOR-6992 end

					}else{ // add if else condition in POCOR-6992
	                    $prevInstitutionStudent = $this->deleteEnrolledStudents($studentId, $this->statuses['PROMOTED']);
	                    $whereId = '';
	                    $whereConditions = '';

	                    if ($prevInstitutionStudent) {
	                        $whereId = [
	                            'id' => $prevInstitutionStudent->id
	                        ];
	                    } else {
		                    $whereConditions = [
								'institution_id' => $institutionId,
								'academic_period_id' => $selectedPeriod,
								'education_grade_id' => $selectedGrade,
								'student_status_id' => $selectedStatus,
								'student_id' => $studentId
							];
						}
						$this->updateStudentStatus('PROMOTED', $whereId, $whereConditions);
					}
				}
			}
		}
			return $studentIds;
	}
}
