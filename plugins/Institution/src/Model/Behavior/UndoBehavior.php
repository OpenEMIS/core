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
            $prevInstitutionStudentId = $entity->id;
        }
        return $this->model->get($prevInstitutionStudentId);
	}

	protected function updateStudentStatus($code, $id, $conditions) 
    {
		$status = $this->statuses[$code];
        $entity = $this->model->find()->where([$id])->first();

        if (empty($entity)) { //if by ID cant find because of data problem.
           $entity = $this->model->find()->where([$conditions])->first();
        }

        if (!empty($entity)) {
            $entity->student_status_id = $status;
            $this->model->save($entity);
        }
	}

    protected function removePendingAdmission($selectedStatus, $studentId, $institutionId) 
    {
        $StudentAdmissionTable = TableRegistry::get('Institution.StudentAdmission');

        //remove pending transfer request.
        //could not include grade / academic period because not always valid. (promotion/graduation/repeat and transfer/admission can be done on different grade / academic period)
        $conditions = [
            'student_id' => $studentId,
            'previous_institution_id' => $institutionId,
            'status' => 0, //pending status
            'type' => 2 //transfer
        ];
        
        $entity = $StudentAdmissionTable
                ->find()
                ->where(
                    $conditions
                )
                ->first();
        
        if (!empty($entity)) {
            $StudentAdmissionTable->delete($entity);
        }
        
        //remove pending admission request.
        //no institution_id because in the pending admission, the value will be (0)
        if ($selectedStatus == $this->statuses['PROMOTED'] || $selectedStatus == $this->statuses['GRADUATED']) {
            $conditions = [
                'student_id' => $studentId,
                'status' => 0, //pending status
                'type' => 1 //admission
            ];
            
            $entity = $StudentAdmissionTable
                    ->find()
                    ->where(
                        $conditions
                    )
                    ->first();
            
            if (!empty($entity)) {
                $StudentAdmissionTable->delete($entity);
            }
        }
    }

    protected function removePendingDropout($studentId, $institutionId) 
    {
        $StudentDropoutTable = TableRegistry::get('Institution.StudentDropout');
        
        //could not include grade / academic period because not always valid. (promotion/graduation/repeat and dropout can be done on different grade / academic period)
        $conditions = [
            'student_id' => $studentId,
            'institution_id' => $institutionId,
            'status' => 0, //pending status
        ];
        
        $entity = $StudentDropoutTable
                ->find()
                ->where(
                    $conditions
                )
                ->first();
        
        if (!empty($entity)) {
            $StudentDropoutTable->delete($entity);
        }
    }
}
