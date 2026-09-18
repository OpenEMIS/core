<?php 
namespace Institution\Model\Behavior;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Institution\Model\Behavior\UndoBehavior;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;

class UndoPromotedBehavior extends UndoBehavior {
	public function initialize(array $config): void {
		parent::initialize($config);
	}

	public function implementedEvents(): array {
		$events = parent::implementedEvents();
		$events['Undo.'.'get'.$this->undoAction.'Students'] = 'onGet'.$this->undoAction.'Students';
		$events['Undo.'.'processSave'.$this->undoAction.'Students'] = 'processSave'.$this->undoAction.'Students';
		return $events;
	}

	public function onGetPromotedStudents(EventInterface $event, $data) {
		$list = $this->getStudents($data);
		return $this->markAlreadyEnrolledElsewhere($list);
	}

	// POCOR-9816 start
	// A student promoted with no next grade may later enrol at a different institution.
	// getStudents() in UndoBehavior only flags records with a non-CURRENT status elsewhere,
	// so this catches the CURRENT-status-at-another-institution case that it deliberately skips.
	protected function markAlreadyEnrolledElsewhere($list) {
		$institutionStudent = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
		$StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');
		$currentStatusId = $StudentStatuses->getIdByCode('CURRENT');
		$alreadyEnrolledMessage = $this->_table->getMessage($this->_table->getAlias() . '.alreadyEnrolled');

		foreach ($list as $key => $obj) {
			if (!empty($obj->info_message)) {
				continue;
			}

			$studentEnrollRecord = $institutionStudent->find()
				->where([
					$institutionStudent->aliasField('student_status_id') => $currentStatusId,
					$institutionStudent->aliasField('student_id') => $obj->student_id
				])
				->first();

			if (!empty($studentEnrollRecord) && $studentEnrollRecord->institution_id != $obj->institution_id) {
				$obj->info_message = $alreadyEnrolledMessage;
			}

			$list[$key] = $obj;
		}

		return $list;
	}
	// POCOR-9816 end

	public function processSavePromotedStudents(EventInterface $event, Entity $entity, ArrayObject $data) 
	{
		//echo "<pre>"; print_r($entity);die;
		$studentIds = [];

		$undoPromote  = '';
		$institutionId = $entity->institution_id;
		$selectedPeriod = $entity->academic_period_id;
		$selectedGrade = $entity->education_grade_id;
		$selectedStatus = $entity->student_status_id;

		$institutionStudent = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
		$institution = TableRegistry::getTableLocator()->get('Institution.Institutions');
		$StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');

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
