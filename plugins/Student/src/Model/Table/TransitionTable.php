<?php
namespace Student\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

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

        $this->EducationProgrammes      = TableRegistry::get('Education.EducationProgrammes');
        $this->EducationGrades          = TableRegistry::get('Education.EducationGrades');

		$this->toggle('remove', false);
		$this->toggle('add', false);
		$this->toggle('search', false);

        $this->addBehavior('User.User');
	}

	private function setupTabElements()
	{
		$options['type'] = 'student';
		$tabElements = $this->controller->getAcademicTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function onGetEducationGradeId(Event $event, Entity $entity)
	{
		return $entity->education_grade->programme_grade_name;
	}

    public function onUpdateFieldEducationProgrammeId(Event $event, array $attr, $action, Request $request)
    {   $EducationProgrammes = TableRegistry::get('Education.EducationProgrammes');

        if ($action == 'add') {
            $programmeOptions = $EducationProgrammes
                ->find('list', ['keyField' => 'id', 'valueField' => 'cycle_programme_name'])
                ->find('availableProgrammes')
                ->toArray();

            $attr['type'] = 'select';
            $attr['options'] = $programmeOptions;
            $attr['onChangeReload'] = 'changeEducationProgrammeId';
        } else if ($action == 'edit') {//echo "<pre>";print_r($attr['entity']);die();
            $gradeId = $attr['entity']->education_grade_id;
            $programmeId = $this->EducationGrades->get($gradeId)->education_programme_id;

            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $EducationProgrammes->get($programmeId)->name;
        }
        return $attr;
    }

    public function addEditOnChangeEducationProgrammeId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        $request = $this->request;
        unset($request->query['programme']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('education_programme_id', $request->data[$this->alias()])) {
                    $request->query['programme'] = $request->data[$this->alias()]['education_programme_id'];
                }
            }
        }
    }

    public function editBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['Users', 'EducationGrades', 'AcademicPeriods', 'EducationGrades.EducationProgrammes']);
    }

    public function editAfterAction(Event $event, Entity $entity)
    {//echo "<pre>";print_r($entity);die();
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

            $this->field('education_programme_id', ['entity' => $entity]);

            $this->field('education_grade_id', ['type' => 'select', 'attr' => ['selected' => $entity->education_grade->programme_grade_name]]);
            
            $this->field('student_status_id', ['visible' => 'false', 'attr' => ['value' => $entity->student_status->name]]);

            $period = $entity->academic_period;
            $dateOptions = [
                'startDate' => $period->start_date->format('d-m-Y'),
                'endDate' => $period->end_date->format('d-m-Y')
            ];

            $this->fields['start_date']['date_options'] = $dateOptions;
            $this->fields['end_date']['date_options'] = $dateOptions;

            $this->setupTabElements($entity);
        }
    }
}
