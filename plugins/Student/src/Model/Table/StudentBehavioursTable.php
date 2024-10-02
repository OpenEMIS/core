<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\AppTable;
use Cake\Http\ServerRequest;
use Cake\ORM\Behavior;
use Cake\Http\Session;
use Cake\ORM\Table;
use Cake\Routing\Router;
use App\Model\Table\ControllerActionTable;
class StudentBehavioursTable extends ControllerActionTable
{
	protected $_defaultConfig = [ //POCOR-8507
        'controller' => null,
    ];

    public $controller;

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

		$this->controller = $config['controller']; //POCOR-8507
	}

	public function indexBeforeAction(Event $event, ArrayObject $settings) {
		$this->field('student_id', ['visible' => false]);
		$this->field('assignee_id', ['visible' => false]);
		$this->field('student_behaviour_category_id', ['type' => 'select','visible' => false]);
		$this->field('description', ['visible' => false]);
		$this->field('action', ['visible' => false]);

		$this->setFieldOrder(['institution_id', 'date_of_behaviour', 'time_of_behaviour', 'title', 'student_behaviour_category_id']);
	}
        
	public function beforeFind(Event $event, Query $query, $options)
	{

		if ($this->controller != null) { //POCOR-8507

			//$userData = $this->Session->read();
			if ($this->controller->getName() != null && $this->controller->getName() == 'Profiles' && $this->request->getQuery('type') == 'student') {
				//if ($this->Session->read('Auth.User.is_guardian') == 1) {
				if ($_SESSION['Auth']['User']['is_guardian'] == 1) {
					$userData = $this->Session->read();
					//$sId = $this->Session->read('Student.ExaminationResults.student_id');
					$sId = $this->getQueryString('student_id');
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
				$studentId = $this->getQueryString('student_id');
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
		$table = $this->_table;
        $request = Router::getRequest();
        $this->controller = $request->getParam('controller');
		if($this->controller != NULL){
			if ($this->controller != null && $this->controller == 'Profiles' && $this->request->getQuery('type') == 'student') {
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
		}

		/*POCOR-6267 starts*/
	    if ($this->controller!= null && $this->controller == 'GuardianNavs') {
	    	$session = $this->request->getSession();
	        $studentId = $session->read('Student.Students.id');
	    }/*POCOR-6267 ends*/
		if($this->controller != null && ($this->controller == 'Students' || $this->controller == 'Directories')) {
			$studentId = $this->getQueryString('student_id');
		}
	    if(!empty($studentId)){ //POCOR-7196
		    $conditions[$this->aliasField('student_id')] = $studentId;
			$query->where($conditions, [], true);
		}else{ // POCOR-7196
			$query ;
		}
		
	    }
	}

	public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
		parent::onUpdateActionButtons($event, $entity, $buttons);
                
		if (array_key_exists('view', $buttons)) {
			$url = [
				'plugin' => 'Institution',
				'controller' => 'Institutions',
				'action' => 'StudentBehaviours',
				'view',
				$this->paramsEncode(['id' => $entity->id]),
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

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
