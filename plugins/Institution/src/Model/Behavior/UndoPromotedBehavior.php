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

		$studentIds = [];
		foreach ($list as $obj) {
			if (empty($obj->info_message)) {
				$studentIds[] = $obj->student_id;
			}
		}

		// Batched instead of one find() per student -- this fires on every load of the Undo
		// Promoted Students screen, and a per-row query doesn't scale to a large cohort.
		$currentInstitutionByStudent = [];
		if (!empty($studentIds)) {
			$currentRecords = $institutionStudent->find()
				->select(['student_id', 'institution_id'])
				->where([
					$institutionStudent->aliasField('student_status_id') => $currentStatusId,
					$institutionStudent->aliasField('student_id IN') => $studentIds
				])
				->all();

			// POCOR-9848: keep every CURRENT institution per student -- a student can hold more than
			// one CURRENT row, and keeping only the last one let a row through as selectable here
			// that Next / Confirm then rejected.
			foreach ($currentRecords as $record) {
				$currentInstitutionByStudent[$record->student_id][] = $record->institution_id;
			}
		}

		foreach ($list as $key => $obj) {
			if (!empty($obj->info_message)) {
				continue;
			}

			if (isset($currentInstitutionByStudent[$obj->student_id])) {
				foreach ($currentInstitutionByStudent[$obj->student_id] as $currentInstitutionId) {
					if ($currentInstitutionId != $obj->institution_id) {
						$obj->info_message = $alreadyEnrolledMessage;
						break;
					}
				}
			}

			$list[$key] = $obj;
		}

		return $list;
	}
	// POCOR-9816 end

	// POCOR-9848: single rule shared by the list screen, Next (addBeforeSave) and Confirm
	// (processSavePromotedStudents) -- a student is enrolled elsewhere if ANY of their CURRENT
	// records is at an institution other than the one they were promoted from. These used to
	// disagree (last row wins vs. an unordered first()), which bounced Next back to the add page.
	public function getCurrentEnrolmentElsewhere($studentId, $institutionId)
	{
		$institutionStudent = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');

		return $institutionStudent->find()
			->where([
				$institutionStudent->aliasField('student_id') => $studentId,
				$institutionStudent->aliasField('student_status_id') => $this->statuses['CURRENT'],
				$institutionStudent->aliasField('institution_id <>') => $institutionId
			])
			->first();
	}

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
		$StudentStatuses = TableRegistry::getTableLocator()->get('Student.StudentStatuses');

		if (isset($entity->students)) {
			foreach ($entity->students as $key => $obj) {
				$studentId = $obj['id'];
				if ($studentId != 0) {
					$promoteId = $StudentStatuses->getIdByCode('PROMOTED');
					//POCOR-6992 start
					$studentPromoteRecord = $institutionStudent->find()->where(['student_status_id'=>$promoteId, 'student_id'=>$studentId,'academic_period_id'=>$entity->academic_period_id])->first();
					// POCOR-9848: same rule as the list screen and Next -- any CURRENT record at another
					// institution blocks this student. Skip just that student rather than return false,
					// which aborted the batch after earlier students had already been reverted.
					$enrolledElsewhere = null;
					if (!empty($studentPromoteRecord)) {
						$enrolledElsewhere = $this->getCurrentEnrolmentElsewhere($studentId, $studentPromoteRecord->institution_id);
					}
					if (!empty($enrolledElsewhere)) {
						continue; //POCOR-6992 end
					}else{ // add if else condition in POCOR-6992
						$studentIds[$studentId] = $studentId;
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
