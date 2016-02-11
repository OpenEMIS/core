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

	protected function deleteEnrolledStudents($studentId) {
		$currentStatus = $this->statuses['CURRENT'];
		$entity = $this->model
			->find()
			->where([
				$this->model->aliasField('student_id') => $studentId,
				$this->model->aliasField('student_status_id') => $currentStatus
			])
			->first();
		if (!empty($entity)) {
			$this->model->delete($entity);
		}
	}

	protected function updateStudentStatus($code, $conditions) {
		$status = $this->statuses[$code];
		$entity = $this->model->find()->where([$conditions])->first();
		$entity->student_status_id = $status;
		$this->model->save($entity);
	}
}
