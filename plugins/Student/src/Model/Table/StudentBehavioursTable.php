<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\ORM\Behavior;
use Cake\Network\Session;

class StudentBehavioursTable extends AppTable {

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('Students', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentBehaviourCategories', ['className' => 'Student.StudentBehaviourCategories']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
	}

	public function indexBeforeAction(Event $event, ArrayObject $settings) {
		$this->ControllerAction->field('student_id', ['visible' => false]);
		$this->ControllerAction->field('student_behaviour_category_id', ['type' => 'select']);
		$this->ControllerAction->field('description', ['visible' => false]);
		$this->ControllerAction->field('action', ['visible' => false]);

		$this->ControllerAction->setFieldOrder(['institution_id', 'date_of_behaviour', 'time_of_behaviour', 'title', 'student_behaviour_category_id']);
	}
        
	public function beforeFind(Event $event, Query $query, $options) 
	{
		//$userData = $this->Session->read();
		if (isset($this->controller->name) && $this->controller->name == 'Profiles' && $this->request->query['type'] == 'student') {
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
	    if (isset($this->controller->name) && $this->controller->name == 'GuardianNavs') {
	    	$session = $this->request->session();
	        $studentId = $session->read('Student.Students.id');
	    }/*POCOR-6267 ends*/ 
	    if(!empty($studentId)){ //POCOR-7196
		    $conditions[$this->aliasField('student_id')] = $studentId;
			$query->where($conditions, [], true); 
		}else{ // POCOR-7196
			$query ;
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
			if ($this->controller->name == 'Profiles') {
				unset($buttons['view']);
			}
			// end POCOR-1893
		}

		return $buttons;
	}

	private function setupTabElements() {
		$options['type'] = 'student';
		$tabElements = $this->controller->getAcademicTabElements($options);
		$this->controller->set('tabElements', $tabElements);
		$alias = 'Behaviours';
		$this->controller->set('selectedAction', $alias);
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
