<?php
namespace Student\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\Entity;

class ProgrammesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', ['className' => 'Institution.InstitutionSites', 'foreignKey' => 'institution_id']);
	}

	public function onGetEducationGradeId(Event $event, Entity $entity) {		
		return $entity->education_grade->programme_grade_name;
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['student_id']['visible'] = 'false';
		$this->fields['academic_period_id']['visible'] = 'false';
		$this->fields['start_year']['visible'] = 'false';
		$this->fields['end_year']['visible'] = 'false';

		$this->ControllerAction->setFieldOrder([
			'institution_id', 'education_grade_id', 'start_date', 'end_date', 'student_status_id'
		]);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$options['auto_contain'] = false;
		$query->contain(['StudentStatuses', 'EducationGrades', 'Institutions']);
		$query->order([$this->aliasField('start_date') => 'DESC']);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);

		if (array_key_exists('view', $buttons)) {
			$url = [
				'plugin' => 'Institution', 
				'controller' => 'Institutions', 
				'action' => 'Students',
				'view', $entity->id,
				'institution_id' => $entity->institution->id,
			];
			$buttons['view']['url'] = $url;
		}
		return $buttons;
	}
}
