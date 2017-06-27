<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
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
        $this->belongsTo('PreviousInstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'previous_institution_student_id']);

		$this->toggle('remove', false);
		$this->toggle('add', false);
		$this->toggle('search', false);
	}

	public function onGetEducationGradeId(Event $event, Entity $entity)
	{
		return $entity->education_grade->programme_grade_name;
	}

	public function onGetInstitutionId(Event $event, Entity $entity)
	{
		return $entity->institution->code_name;
	}

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('previous_institution_student_id', ['visible' => false]);
    }

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->fields['student_id']['visible'] = 'false';
		$this->fields['start_year']['visible'] = 'false';
		$this->fields['end_year']['visible'] = 'false';
		$this->fields['institution_id']['type'] = 'integer';

		$this->setFieldOrder([
			'academic_period_id', 'institution_id', 'education_grade_id', 'start_date', 'end_date', 'student_status_id'
		]);
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
		$session = $this->request->session();

		// POCOR-1893 Profile using loginId as studentId
		if ($this->controller->name == 'Profiles') {
			$studentId = $session->read('Auth.User.id');
		} else {
			$studentId = $session->read('Student.Students.id');
		}
		// end POCOR-1893

        $query->where([$this->aliasField('student_id') => $studentId]);
        $extra['auto_contain_fields'] = ['Institutions' => ['code']];
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
	{
		parent::onUpdateActionButtons($event, $entity, $buttons);

		if (array_key_exists('view', $buttons)) {
			$url = [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'Students',
				'view',
				$this->paramsEncode(['id' => $entity->id]),
				'institution_id' => $entity->institution->id,
			];
			$buttons['view']['url'] = $url;
		}

		$statuses = $this->StudentStatuses->findCodeList();
		$studentStatusId = $entity->student_status_id;

		if (array_key_exists('edit', $buttons) && $studentStatusId == $statuses['CURRENT']) {
			$url = [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'Students',
				'edit',
				$this->paramsEncode(['id' => $entity->id]),
				'institution_id' => $entity->institution->id,
			];
			$buttons['edit']['url'] = $url;
		} else {
			if (array_key_exists('edit', $buttons)) {
				unset($buttons['edit']);
			}
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

	public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
	{
		$this->setupTabElements();
	}
}
