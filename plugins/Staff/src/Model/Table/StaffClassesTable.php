<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class StaffClassesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_class_staff');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('InstitutionSiteClasses', ['className' => 'Institution.InstitutionSiteClasses']);
		$this->hasMany('InstitutionSiteClassStudents', ['className' => 'Institution.InstitutionSiteClassStudents']);
	}


	public function indexBeforeAction(Event $event) {
		$this->fields['status']['visible'] = false;

		$this->ControllerAction->addField('academic_period', []);
		$this->ControllerAction->addField('institution', []);
		$this->ControllerAction->addField('institution_site_section', []);
		$this->ControllerAction->addField('educationSubject', []);
		$this->ControllerAction->addField('male_students', []);
		$this->ControllerAction->addField('female_students', []);
		
		$order = 0;
		$this->ControllerAction->setFieldOrder('academic_period', $order++);
		$this->ControllerAction->setFieldOrder('institution', $order++);
		$this->ControllerAction->setFieldOrder('institution_site_section', $order++);
		$this->ControllerAction->setFieldOrder('institution_site_class_id', $order++);
		$this->ControllerAction->setFieldOrder('educationSubject', $order++);
		$this->ControllerAction->setFieldOrder('male_students', $order++);
		$this->ControllerAction->setFieldOrder('female_students', $order++);
	}



}