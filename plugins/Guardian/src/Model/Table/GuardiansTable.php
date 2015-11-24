<?php
namespace Guardian\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class GuardiansTable extends AppTable {
	public $InstitutionStudent;

	public function initialize(array $config) {
		$this->table('security_users');
		$this->entityClass('User.User');
		parent::initialize($config);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);

		$this->belongsToMany('Students', [
			'className' => 'Student.Students',
			'joinTable' => 'student_guardians',
			'foreignKey' => 'guardian_id',
			'targetForeignKey' => 'student_id',
			'through' => 'Student.Guardians',
			'dependent' => true
		]);

		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
		$this->addBehavior('AdvanceSearch');

		// $this->addBehavior('Excel', [
		// 	'excludes' => ['password', 'photo_name'],
		// 	'filename' => 'Guardians'
		// ]);
		// $this->addBehavior('TrackActivity', ['target' => 'Student.StudentActivities', 'key' => 'security_user_id', 'session' => 'Users.id']);
	}

	public function validationDefault(Validator $validator) {
		$BaseUsers = TableRegistry::get('User.Users');
		return $BaseUsers->setUserValidation($validator);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->Session->write('Guardian.Guardians.name', $entity->name);
		$this->setupTabElements(['id' => $entity->id]);
	}

	public function indexBeforeAction(Event $event, Query $query, ArrayObject $settings) {
		// fields are set in UserBehavior
		$this->fields = []; // unset all fields first
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$query->where([$this->aliasField('is_guardian') => 1]);
		
		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['searchTerm' => $search]);
		}
	}

	public function addBeforeAction(Event $event) {
		$openemisNo = $this->getUniqueOpenemisId(['model' => Inflector::singularize('Guardian')]);
		$this->ControllerAction->field('openemis_no', [ 
			'attr' => ['value' => $openemisNo],
			'value' => $openemisNo
		]);

		$this->ControllerAction->field('username', ['order' => 70]);
		$this->ControllerAction->field('password', ['order' => 71, 'visible' => true]);
		$this->ControllerAction->field('is_guardian', ['value' => 1]);
	}

	public function onBeforeDelete(Event $event, ArrayObject $options, $id) {
		$process = function($model, $id, $options) {
			$model->updateAll(['is_guardian' => 0], [$model->primaryKey() => $id]);
			return true;
		};
		return $process;
	}

	private function setupTabElements($options) {
		$this->controller->set('selectedAction', $this->alias);
		$this->controller->set('tabElements', $this->controller->getUserTabElements($options));
	}
}
