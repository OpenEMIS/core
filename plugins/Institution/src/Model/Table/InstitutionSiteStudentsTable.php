<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use App\Model\Table\AppTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;



class InstitutionSiteStudentsTable extends AppTable {
	private $academicPeriodId;
	private $educationProgrammeId;
	private $education_grade;

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Users', 		 		['className' => 'User.Users', 					'foreignKey' => 'security_user_id']);
		$this->belongsTo('Institutions', 		['className' => 'Institution.Institutions', 	'foreignKey' => 'institution_site_id']);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes','foreignKey' => 'education_programme_id']);
		$this->belongsTo('StudentStatuses',		['className' => 'Student.StudentStatuses', 	'foreignKey' => 'student_status_id']);

		$this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
		$this->addBehavior('AcademicPeriod.Period');
	}
	
	// public function addBeforeAction(Event $event) {
	// 	$this->ControllerAction->field('institution');
	// 	$this->ControllerAction->field('academic_period');
	// 	$this->ControllerAction->field('education_programme_id');
	// 	$this->ControllerAction->field('education_grade');
	// 	$this->ControllerAction->field('section');
	// 	$this->ControllerAction->field('student_status_id');
	// 	$this->ControllerAction->field('student_status_id');
	// 	$this->ControllerAction->field('start_date');
	// 	$this->ControllerAction->field('end_date');
	// 	$this->ControllerAction->field('security_user_id');
	// 	// $this->ControllerAction->field('search');

	// 	$this->fields['start_year']['visible'] = false;
	// 	$this->fields['end_year']['visible'] = false;
	// 	// initializing to bypass validation - will be modified later when appropriate
	// 	$this->fields['security_user_id']['type'] = 'hidden';
	// 	$this->fields['security_user_id']['value'] = 0;

	// 	$this->ControllerAction->setFieldOrder([
	// 		'institution', 'academic_period', 'education_programme_id', 'education_grade', 'section', 'student_status_id', 'start_date', 'end_date'
	// 		// , 'search'
	// 		]);
	// }

	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$timeNow = strtotime("now");
		$sessionVar = $this->alias().'.add.'.strtotime("now");
		$this->Session->write($sessionVar, $this->request->data);

		if (!$entity->errors()) {
			$event->stopPropagation();
			return $this->controller->redirect(['plugin' => 'Student', 'controller' => 'Students', 'action' => 'add'.'?new='.$timeNow]);
		}
	}

	public function validationDefault(Validator $validator) {
		return $validator
			->add('start_date', 'ruleCompareDate', [
				'rule' => ['compareDate', 'end_date', false]
			])
			->add('end_date', [
			])
			->add('student_status_id', [
			])
			->add('academic_period', [
			])
			->add('education_programme_id',[
			])
			->add('education_grade',[
			])
		;
	}
}
