<?php
namespace Student\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class ProgrammesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_students');
		parent::initialize($config);

		$this->belongsTo('User', ['className' => 'User.Users', 'foreignKey' => 'security_user_id']);
		$this->belongsTo('StudentStatus', ['className' => 'FieldOption.StudentStatuses']);
		$this->belongsTo('EducationProgramme', ['className' => 'Education.EducationProgrammes']);
		$this->belongsTo('InstitutionSite', ['className' => 'Institution.InstitutionSites']);
	}

	// public function index() {
		// todo-mlee sort out dynamic fields
		// $this->controller->set('indexElements', []);
		// $this->controller->set('modal', []);
		// $id = $this->ControllerAction->Session->read('Student.security_user_id');
		// $institutionSiteId = $this->Session->read('InstitutionSite.id');
		// $conditions = array($this->alias().".student_id" => $id);

		// if (!is_null($institutionSiteId)) {
		// 	$conditions[$this->alias().".institution_site_id"] = $institutionSiteId;
		// }


		// $conditions = [];
		// $conditions[$this->aliasField('security_user_id')] = $id;
		// $query = $this->find()->hydrate(false)
		// 			->contain(['StudentStatus', 'InstitutionSite', 'EducationProgramme'])
		// 			->where($conditions)
		// 			->toArray();

		// if(empty($query)){
		// 	$this->Message->alert('general.noData');
		// }

		// $this->controller->set('data', $query);
		// $this->ControllerAction->autoRender = false;
		// $this->controller->render('Programmes/index');
	// }

}