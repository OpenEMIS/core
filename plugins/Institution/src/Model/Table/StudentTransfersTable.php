<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;

class StudentTransfersTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_student_transfers');
		parent::initialize($config);
		$this->belongsTo('StudentStatuses',		['className' => 'Student.StudentStatuses', 	'foreignKey' => 'student_status_id']);
	}
}
