<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Entity;

use App\Model\Table\ControllerActionTable;

class TransitionTable extends ControllerActionTable
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

        $this->addBehavior('User.User');
	}

	public function editBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['Users', 'EducationGrades', 'AcademicPeriods', 'EducationGrades.EducationProgrammes']);
    }

    public function editAfterAction(Event $event, Entity $entity)
    {//echo "<pre>";print_r($entity->education_grade->programme_grade_name);die();
        $this->field('start_year', ['visible' => 'false']);
        $this->field('end_year', ['visible' => 'false']);

        // Start PHPOE-1897
        $statuses = $this->StudentStatuses->findCodeList();
        if ($entity->student_status_id != $statuses['CURRENT']) {
            $event->stopPropagation();
            $urlParams = $this->url('view');
            return $this->controller->redirect($urlParams);
        // End PHPOE-1897
        } else {
            $this->field('student_id', [
                'type' => 'readonly',
                'order' => 10,
                'attr' => ['value' => $entity->user->name_with_id]
            ]);
            $this->field('academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $entity->academic_period->name]]);
            $this->field('education_programme_id', ['type' => 'select', 'attr' => ['selected' => $entity->education_grade->programme_grade_name]]);
            $this->field('education_grade_id', ['type' => 'select', 'attr' => ['selected' => $entity->education_grade->programme_grade_name]]);
            $this->field('student_status_id', ['visible' => 'false', 'attr' => ['value' => $entity->student_status->name]]);

            $period = $entity->academic_period;
            $dateOptions = [
                'startDate' => $period->start_date->format('d-m-Y'),
                'endDate' => $period->end_date->format('d-m-Y')
            ];

            $this->fields['start_date']['date_options'] = $dateOptions;
            $this->fields['end_date']['date_options'] = $dateOptions;
        }
    }
}
