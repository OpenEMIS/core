<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class StudentClassesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_class_students');

		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionSiteClasses', ['className' => 'Institution.InstitutionSiteClasses']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['status']['visible'] = false;

		$this->ControllerAction->addField('academic_period', []);
		$this->ControllerAction->addField('institution', []);
		$this->ControllerAction->addField('educationSubject', []);
		$this->ControllerAction->addField('homeroom_teacher_name', []);
		
		$order = 0;
		$this->ControllerAction->setFieldOrder('academic_period', $order++);
		$this->ControllerAction->setFieldOrder('institution', $order++);
		$this->ControllerAction->setFieldOrder('institution_site_section_id', $order++);
		$this->ControllerAction->setFieldOrder('institution_site_class_id', $order++);
		$this->ControllerAction->setFieldOrder('educationSubject', $order++);
		$this->ControllerAction->setFieldOrder('homeroom_teacher_name', $order++);

	}
}