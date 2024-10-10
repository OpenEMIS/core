<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Http\ServerRequest;
use Cake\ORM\Behavior;
use Cake\Http\Session;
use Cake\ORM\Table;
use Cake\Routing\Router;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\ResultSet;

class StudentBehavioursTable extends ControllerActionTable
{

	public function initialize(array $config): void {
		parent::initialize($config);

		$this->belongsTo('Students', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentBehaviourCategories', ['className' => 'Student.StudentBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']); //POCOR-7488
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);//POCOR-7488
		$this->belongsTo('StudentBehaviourClassifications', ['className' => 'Student.StudentBehaviourClassifications']);//POCOR-7557
		$this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['StudentBehaviours' =>['id']
            ]
        ]);
        $this->toggle('add', false); //POCOR-8596
        $this->toggle('edit', false); //POCOR-8596
        $this->toggle('remove', false);//POCOR-8596

	}

	public function indexBeforeAction(Event $event, ArrayObject $settings) {
		$this->field('student_id', ['visible' => false]);
		$this->field('assignee_id', ['visible' => false]);
		$this->field('student_behaviour_category_id', ['type' => 'select','visible' => false]);
		$this->field('description', ['visible' => false]);
		$this->field('action', ['visible' => false]);

		$this->setFieldOrder(['institution_id', 'date_of_behaviour', 'time_of_behaviour', 'title', 'student_behaviour_category_id']);
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);
                
		if (array_key_exists('view', $buttons)) {
			$url = [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'StudentBehaviours',
				0 => 'view',
				1 => $this->paramsEncode(['id' => $entity->id]),
				'institution_id' => $entity->institution->id,
			];
			$buttons['view']['url'] = $url;

			// POCOR-1893 unset the view button on profiles controller
			if ($this->request->getParam('controller') == 'Profiles') {
				unset($buttons['view']);
			}
			// end POCOR-1893
		}

		return $buttons;
	}
	

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
	{
	    $session = $this->request->getSession();
	    $userData = $session->read('Auth.User');
	    $studentId = null;

	    if ($userData['is_guardian'] == 1) {
	        if ($this->request->getParam('controller') == 'GuardianNavs') {
	            $studentId = $this->getStudentID();
	        } else {
	            $sId = $session->read('Student.ExaminationResults.student_id');
	            $studentId = $sId ? $this->Action->paramsDecode($sId)['id'] : $this->getUserID();
	        }
	    } else {
	        $studentId = $userData['id'];
	    }

	    // Conditions for controller checks
	    if ($this->request->getParam('controller') == 'GuardianNavs') {
	        $studentId = $this->getQueryString('student_id');
	    } elseif (in_array($this->request->getParam('controller'), ['Students', 'Directories'])) {
	        $studentId = $this->getQueryString('student_id');
	    }

	    // Add conditions to query if studentId is available
	    if (!empty($studentId)) {
	        $conditions[$this->aliasField('student_id')] = $studentId;
	        $query->where($conditions, [], true);
	    }

	    // Additional check for Profiles controller
	    if ($this->request->getParam('controller') == 'Profiles' && $this->request->getQuery('type') == 'student') {
	        if ($userData['is_guardian'] == 1) {
	            $sId = $session->read('Student.ExaminationResults.student_id');
	            if (empty($sId)) {
	                $studentId = $session->read('Student.ExaminationResults.student_id');
	            } else {
	                $studentId = $this->Action->paramsDecode($sId)['id'];
	            }
	        } else {
	            $studentId = $userData['id'];
	        }
	    }

	    // GuardianNavs controller session check
	    if ($this->request->getParam('controller') == 'GuardianNavs') {
	        $studentId = $session->read('Student.Students.id');
	    }

	    if (!empty($studentId)) {
	    	$studentId = $this->getQueryString('student_id');
	        $conditions[$this->aliasField('student_id IS')] = $studentId;
	        $query->where($conditions, [], true);
	    }
	}
	public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $options = ['type' => 'student'];
        $tabElements = $this->getAcademicTabElements($options);
        if($this->controller->getName() == 'GuardianNavs' || $this->controller->getName() == 'Directories') {
            $tabElements = $this->controller->getAcademicTabElements($options);
        }
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Behaviours');
    }


}
