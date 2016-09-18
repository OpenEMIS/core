<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\Entity;

use App\Model\Table\ControllerActionTable;

class ProgrammesTable extends ControllerActionTable
{
	public function initialize(array $config)
	{
		$this->table('institution_students');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
	}

	public function onGetEducationGradeId(Event $event, Entity $entity)
	{		
		return $entity->education_grade->programme_grade_name;
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->fields['student_id']['visible'] = 'false';
		$this->fields['academic_period_id']['visible'] = 'false';
		$this->fields['start_year']['visible'] = 'false';
		$this->fields['end_year']['visible'] = 'false';

		$this->setFieldOrder([
			'institution_id', 'education_grade_id', 'start_date', 'end_date', 'student_status_id'
		]);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$options['auto_contain'] = false;
		$query->contain(['StudentStatuses', 'EducationGrades', 'Institutions']);
		$options['order'] = [$this->aliasField('start_date') => 'DESC'];
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
	{
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

		if (array_key_exists('edit', $buttons)) {
			$url = [
				'plugin' => 'Institution', 
				'controller' => 'Institutions', 
				'action' => 'Students',
				'edit', $entity->id,
				'institution_id' => $entity->institution->id,
			];
			$buttons['edit']['url'] = $url;
		}

		return $buttons;
	}

	private function setupTabElements()
	{
		$options['type'] = 'student';
		$tabElements = $this->controller->getAcademicTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function indexAfterAction(Event $event, $data, ArrayObject $extra)
	{
		$this->setupTabElements();
	}
}
