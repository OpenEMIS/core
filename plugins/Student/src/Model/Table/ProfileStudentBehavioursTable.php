<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\ORM\Behavior;
use Cake\Network\Session;
use App\Model\Table\ControllerActionTable;
//Write this file because of Perosnal > Student > Academic > Behaviors tabs
class ProfileStudentBehavioursTable extends ControllerActionTable
{

	public function initialize(array $config): void {
		$this->setTable('student_behaviours');
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
	}

    public function indexBeforeAction(EventInterface $event, ArrayObject $settings) {
		$this->field('student_id', ['visible' => false]);
		$this->field('student_behaviour_category_id', ['type' => 'select']);
		$this->field('description', ['visible' => false]);
		$this->field('action', ['visible' => false]);

		$this->setFieldOrder(['institution_id', 'date_of_behaviour', 'time_of_behaviour', 'title', 'student_behaviour_category_id']);
	}


	public function beforeFind(EventInterface $event, Query $query, $options)
	{
		//$userData = $this->Session->read();
		if ($this->controller->getName() != null && $this->controller->getName() == 'Profiles' && $this->request->getQuery('type') == 'student') {
			//if ($this->Session->read('Auth.User.is_guardian') == 1) {
			if ($_SESSION['Auth']['User']['is_guardian'] == 1) {
				$userData = $this->Session->read();
				$sId = $this->Session->read('Student.ExaminationResults.student_id');
				//$sId = $_SESSION['Student']['ExaminationResults']['student_id'];
				/**
                 * Need to add current login id as param when no data found in existing variable
                 * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
				 * @ticket POCOR-6548
                 */
                //# START: [POCOR-6548] Check if user data not found then add current login user data
                if ($sId == null || empty($sId) || $sId == '') {
                    $studentId = $userData['Student']['ExaminationResults']['student_id'];
                } else {
					$studentId = $this->ControllerAction->paramsDecode($sId)['id'];
                }
                //# END: [POCOR-6548] Check if user data not found then add current login user data
			} else {
				//$studentId = $this->Session->read('Auth.User.id');
				$studentId = $_SESSION['Auth']['User']['id'];
			}
		}

		/*POCOR-6267 starts*/
	    if ($this->controller->getName()!= null && $this->controller->getName() == 'GuardianNavs') {
	    	$session = $this->request->getSession();
	        $studentId = $session->read('Student.Students.id');
	    }/*POCOR-6267 ends*/
		if($this->controller->getName()!= null && ($this->controller->getName() == 'Students' || $this->controller->getName() == 'Directories')) {
			$studentId = $this->getQueryString('student_id');
		}
	    if(!empty($studentId)){ //POCOR-7196
		    $conditions[$this->aliasField('student_id')] = $studentId;
			$query->where($conditions, [], true);
		}else{ // POCOR-7196
			$query ;
		}
		
	}

	public function onUpdateActionButtons(EventInterface $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);

		if (isset($buttons['view'])) {
			$url = [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'StudentBehaviours',
				'view',
				$this->paramsEncode(['id' => $entity->id, 'institution_id'=> $entity->institution->id]),
				'institution_id' => $entity->institution->id,
			];
			$buttons['view']['url'] = $url;

			// POCOR-1893 unset the view button on profiles controller
			if ($this->controller->getName() == 'Profiles') {
				unset($buttons['view']);
			}
			// end POCOR-1893
		}

		return $buttons;
	}

	private function setupTabElements() {
		$options['type'] = 'student';
		//$tabElements = $this->controller->getAcademicTabElements($options);
		$tabElements = $this->getAcademicTabElements($options);
		if($this->controller->getName() == 'GuardianNavs' || $this->controller->getName() == 'Directories') {
			$tabElements = $this->controller->getAcademicTabElements($options);
		}
		$this->controller->set('tabElements', $tabElements);
		$alias = 'Behaviours';
		$this->controller->set('selectedAction', $alias);
	}

	public function indexAfterAction(EventInterface $event, $data) {
		$this->setupTabElements();
	}

	public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'institution_id') {
            return __('Institution');
        } elseif ($field == 'date_of_behaviour') {
            return __('Date Of Behaviour');
        } elseif ($field == 'time_of_behaviour') {
            return __('Time Of Behaviour');
        } elseif ($field == 'student_behaviour_category_id') {
            return __('Student Behaviour Category');
        } elseif ($field == 'academic_period_id') {
            return __('Academic Period');
        } elseif ($field == 'status_id') {
            return __('Status');
        } elseif ($field == 'assignee_id') {
            return __('Assignee');
        } elseif ($field == 'student_behaviour_classification_id') {
            return __('Student Behaviour Classification');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}


