<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class ProgrammesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_students');
		parent::initialize($config);

		$this->belongsTo('User', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('StudentStatus', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationProgramme', ['className' => 'Education.EducationProgrammes']);
		$this->belongsTo('InstitutionSite', ['className' => 'Institution.InstitutionSites']);
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['start_year']['visible'] = 'false';
		$this->fields['end_year']['visible'] = 'false';

		$order = 0;
		$this->ControllerAction->setFieldOrder('institution_site_id', $order++);
		$this->ControllerAction->setFieldOrder('education_programme_id', $order++);
		$this->ControllerAction->setFieldOrder('start_date', $order++);
		$this->ControllerAction->setFieldOrder('end_date', $order++);
		$this->ControllerAction->setFieldOrder('student_status_id', $order++);
	}
}
