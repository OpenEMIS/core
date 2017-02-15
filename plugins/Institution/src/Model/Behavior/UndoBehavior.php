<?php 
namespace Institution\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\TableRegistry;

class UndoBehavior extends Behavior {
	protected $_defaultConfig = [
		'model' => null,
		'statuses' => []
	];

	protected $undoAction;
	protected $model;
	protected $statuses;

	public function initialize(array $config) {
		parent::initialize($config);

		$class = basename(str_replace('\\', '/', get_class($this)));
		$class = str_replace('Undo', '', $class);
		$class = str_replace('Behavior', '', $class);
		$this->_table->addUndoActions($class);
		$this->undoAction = $class;

		$this->statuses = $this->config('statuses');
		$this->model = TableRegistry::get($this->config('model'));
	}

	protected function getStudents($data) {
		$list = [];

		$currentStatus = $this->statuses['CURRENT'];
		$infoMessage = $this->_table->getMessage($this->_table->alias().'.notUndo');

		foreach ($data as $key => $obj) {
			$id = $obj['id'];	// uuid of current student record
			$studentId = $obj['student_id'];
			$startDate = $obj['start_date']->format('Y-m-d');

			// find student records across all institutions regardless periods and grades
			// exclude itelf, exclude enrolled status and the start_date is from current record onwards
			$results = $this->model
				->find()
				->where([
					$this->model->aliasField('id <>') => $id,
					$this->model->aliasField('student_id') => $studentId,
					$this->model->aliasField('start_date >') => $startDate,
					$this->model->aliasField('student_status_id <>') => $currentStatus
				])
				->all();

			if (!$results->isEmpty()) {
				$obj->info_message = $infoMessage;
			}
			$list[$key] = $obj;
		}

		return $list;
	}

	protected function deleteEnrolledStudents($studentId, $selectedStatus) 
    {
		$currentStatus = $this->statuses['CURRENT'];
		
        $entity = $this->model
			->find()
			->where([
				$this->model->aliasField('student_id') => $studentId,
				$this->model->aliasField('student_status_id') => $currentStatus
			])
			->first();

		if (!empty($entity)) { //this is meant for get the immediate record before its being deleted
                $prevInstitutionStudentId = $entity->previous_institution_student_id;
                $this->model->delete($entity); //this will also trigger StudentCascadeDeleteBehavior to delete associated data
        } else {
            $entity = $this->model
                    ->find()
                    ->where([
                        $this->model->aliasField('student_id') => $studentId,
                        $this->model->aliasField('student_status_id') => $selectedStatus
                    ])
                    ->order(['start_date' => 'desc', 'created' => 'desc', 'id' => 'desc'])
                    ->first();
            if (!empty($entity)) {
                $prevInstitutionStudentId = $entity->id;
            }
        }
        if ($prevInstitutionStudentId) {
            return $this->model->get($prevInstitutionStudentId);
        } else {
            return null;
        }
	}

	protected function updateStudentStatus($undoStatus, $id, $conditions) 
    {
        $enrolledStatus = $this->statuses['CURRENT'];
		$entity = '';
        
        if ($id) {
            $entity = $this->model->get($id);
        } else { //if by ID cant find because of data problem.
            if ($conditions) {
                $entity = $this->model->find()->where([$conditions])->first();
            }
        }

        if (!empty($entity)) {
            if ($undoStatus == 'TRANSFERRED') { //for undo transfer, need to re-update end_date according to transfer request on admission table

                $studentAdmissionTable = TableRegistry::get('Institution.StudentAdmission');

                $studentAdmission = $studentAdmissionTable
                                    ->find()
                                    ->where([
                                        $studentAdmissionTable->aliasField('student_id') => $entity->student_id,
                                        $studentAdmissionTable->aliasField('status') => 1, //approved
                                        $studentAdmissionTable->aliasField('academic_period_id') => $entity->academic_period_id,
                                        $studentAdmissionTable->aliasField('education_grade_id') => $entity->education_grade_id,
                                        $studentAdmissionTable->aliasField('previous_institution_id') => $entity->institution_id,
                                    ])
                                    ->first();

                if (!empty($studentAdmission)) {
                    $endDate = $studentAdmission->end_date;
                    $entity->end_date = $endDate;
                }
            //undo which dont store previous end_date will then take the end date of the academic period.
            } else if ($undoStatus == 'WITHDRAWN' || $undoStatus == 'REPEATED' || $undoStatus == 'PROMOTED') {

                $academicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriod = $academicPeriodTable->get($entity->academic_period_id);

                if (!empty($academicPeriod)) {
                    $endDate = $academicPeriod->end_date;
                    $entity->end_date = $endDate;
                }
            }
            $entity->student_status_id = $enrolledStatus;
            $this->model->save($entity);
        }
	}
}
