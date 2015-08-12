<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\Event\Event;

class StudentSectionsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_section_students');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);

		$this->hasMany('InstitutionSiteSectionGrade', ['className' => 'Institution.InstitutionSiteSectionGrade', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['education_grade_id']['visible'] = false;
		$this->fields['status']['visible'] = false;

		$this->ControllerAction->addField('academic_period', []);
		$this->ControllerAction->addField('institution', []);
		$this->ControllerAction->addField('education_grade', []);
		$this->ControllerAction->addField('homeroom_teacher_name', []);

		$order = 0;
		$this->ControllerAction->setFieldOrder('academic_period', $order++);
		$this->ControllerAction->setFieldOrder('institution', $order++);
		$this->ControllerAction->setFieldOrder('education_grade', $order++);
		$this->ControllerAction->setFieldOrder('institution_site_section_id', $order++);
		$this->ControllerAction->setFieldOrder('homeroom_teacher_name', $order++);
	}
}