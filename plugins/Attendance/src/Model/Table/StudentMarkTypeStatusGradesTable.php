<?php
namespace Attendance\Model\Table;

use App\Model\Table\AppTable;

class StudentMarkTypeStatusGradesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('StudentMarkTypeStatuses', ['className' => 'Attendance.StudentMarkTypeStatuses']);
		$this->belongsTo('EducationGrades', ['className' => 'Attendance.EducationGrades']);
	}
}
