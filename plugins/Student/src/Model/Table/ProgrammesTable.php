<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class ProgrammesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_students');
		parent::initialize($config);

		$this->belongsTo('User', ['className' => 'User.Users']);
		$this->belongsTo('StudentStatus', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationProgramme', ['className' => 'Education.EducationProgrammes']);
		$this->belongsTo('InstitutionSite', ['className' => 'Institution.InstitutionSites']);
	}

	public function index() {
		$this->controller->set('indexElements', []);
		$this->controller->set('modal', []);
		$id = $this->ControllerAction->Session->read('Student.security_user_id');
		$institutionSiteId = $this->Session->read('InstitutionSite.id');
		$conditions = array($this->alias().".student_id" => $id);

		if (!is_null($institutionSiteId)) {
			$conditions[$this->alias().".institution_site_id"] = $institutionSiteId;
		}

		// $query = $this->find()->hydrate(false)
		// 			// ->joins()
		// 			->limit(1)
		// 			->toArray()
		// 			;


		// pr($conditions);
		// pr($query);
		// die;

		$this->ControllerAction->render();

		// $alias = $this->alias;
		// $studentId = $this->Session->read('Student.id');
		// $institutionSiteId = $this->Session->read('InstitutionSite.id');
		// $conditions = array("$alias.student_id" => $studentId);
		
		// if (!is_null($institutionSiteId)) {
		// 	$conditions["$alias.institution_site_id"] = $institutionSiteId;
		// }
		// $this->recursive = 0;
		// $data = $this->find('all', array(
		// 	'fields' => array('InstitutionSite.name', 'EducationProgramme.name', 'Programme.id', 'Programme.start_date', 'Programme.end_date', 'StudentStatus.name'),
		// 	'conditions' => $conditions,
		// 	'order' => array("$alias.start_date DESC")
		// ));

		// if(empty($data)){
		// 	$this->Message->alert('general.noData');
		// }

		// $this->controller->set('data', $data);
	}

}