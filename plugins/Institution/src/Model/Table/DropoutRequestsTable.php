<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;
use Cake\Event\Event;
use Cake\Validation\Validator;

class DropoutRequestsTable extends AppTable {
	const NEW_REQUEST = 0;
	const APPROVED = 1;
	const REJECTED = 2;

	public function initialize(array $config) {
		$this->table('institution_student_dropout');
		parent::initialize($config);
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions']);
		$this->belongsTo('StudentDropoutReasons', ['className' => 'FieldOption.StudentDropoutReasons']);
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$institutionId = $this->Session->read('Institution.Institutions.id');
		$id = $this->Session->read($this->registryAlias().'.id');
		$Students = TableRegistry::get('Institution.Students');
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

   	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {
		if ($action == 'add' || $action == 'edit') {
			$Students = TableRegistry::get('Institution.Students');
			$toolbarButtons['back']['url']['action'] = $Students->alias();
			$toolbarButtons['back']['url'][0] = 'view';
			$toolbarButtons['back']['url'][1] = $this->Session->read($this->registryAlias().'.id');
		}
	}
}
