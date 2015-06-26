<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class StaffSectionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_sections');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('InstitutionSites', ['className' => 'Institution.InstitutionSites']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('InstitutionSiteShifts', ['className' => 'Institution.InstitutionSiteShifts']);
	}

	// Academic Period	Institution	Grade	Section	Male Students	Female Students
	public function indexBeforeAction(Event $event) {
		$this->fields['section_number']['visible'] = false;
		$this->fields['institution_site_shift_id']['visible'] = false;

		$this->ControllerAction->addField('male_students', []);
		$this->ControllerAction->addField('female_students', []);
		
		$order = 0;
		$this->ControllerAction->setFieldOrder('academic_period_id', $order++);
		$this->ControllerAction->setFieldOrder('institution_site_id', $order++);
		$this->ControllerAction->setFieldOrder('education_grade_id', $order++);
		$this->ControllerAction->setFieldOrder('name', $order++);
		$this->ControllerAction->setFieldOrder('male_students', $order++);
		$this->ControllerAction->setFieldOrder('female_students', $order++);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}

}