<?php
namespace Student\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class GuardiansTable extends AppTable {
	public function initialize(array $config) {
		$this->table('student_guardians');
		parent::initialize($config);

		$this->belongsTo('Students',			['className' => 'Student.Students', 'foreignKey' => 'student_id']);
		$this->belongsTo('Users',				['className' => 'Security.Users', 'foreignKey' => 'guardian_id']);
		$this->belongsTo('GuardianRelations',	['className' => 'FieldOption.GuardianRelations', 'foreignKey' => 'guardian_relation_id']);

		// to handle field type (autocomplete)
		$this->addBehavior('OpenEmis.Autocomplete');
		$this->addBehavior('User.User');
		$this->addBehavior('User.AdvancedNameSearch');
	}

	public function validationDefault(Validator $validator) {
		return $validator
			->add('guardian_id', 'ruleStudentGuardianId', [
				'rule' => ['studentGuardianId'],
				'on' => 'create'
			])
		;
	}

	private function setupTabElements($entity=null) {
		if ($this->action == 'index') {
			$tabElements = $this->controller->getUserTabElements();
			$this->controller->set('tabElements', $tabElements);
			$this->controller->set('selectedAction', $this->alias());
			
		} elseif ($this->action == 'view') {
			$url = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name];

			$tabElements = [
				'Guardians' => ['text' => __('Relation')],
				'GuardianUser' => ['text' => __('General')]
			];

			$tabElements['Guardians']['url'] = array_merge($url, ['action' => $this->alias(), 'view', $entity->id]);
			$tabElements['GuardianUser']['url'] = array_merge($url, ['action' => 'GuardianUser', 'view', $entity->guardian_id, 'id' => $entity->id]);

			$this->controller->set('tabElements', $tabElements);
			$this->controller->set('selectedAction', $this->alias());
		}
	}

	public function indexAfterAction(Event $event, $data) {
		if ($this->controller->name == 'Students') {
			$this->setupTabElements();
		}
	}

	public function onGetGuardianId(Event $event, Entity $entity) {
		if ($entity->has('_matchingData')) {
			return $entity->_matchingData['Users']->name;
		}
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('student_id', ['type' => 'hidden', 'value' => $this->Session->read('Student.Students.id')]);
		$this->ControllerAction->field('guardian_id');
		$this->ControllerAction->field('guardian_relation_id', ['type' => 'select']);
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$search = $this->ControllerAction->getSearchKey();
		if (!empty($search)) {
			// function from AdvancedNameSearchBehavior
			$query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
		}
	}

	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$errors = $entity->errors();
		if (!empty($errors)) {
			$entity->unsetProperty('guardian_id');
			unset($data[$this->alias()]['guardian_id']);
		}
	}

	public function addAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('id', ['value' => Text::uuid()]);
	}

	public function viewBeforeAction(Event $event) {
		$this->ControllerAction->field('photo_content', ['type' => 'image', 'order' => 0]);
		$this->ControllerAction->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
		$this->fields['guardian_id']['order'] = 10;
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupTabElements($entity);
	}

	public function editBeforeQuery(Event $event, Query $query) {
		$query->contain(['Students', 'Users']);
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->ControllerAction->field('guardian_id', [
			'type' => 'readonly', 
			'order' => 10, 
			'attr' => ['value' => $entity->user->name_with_id]
		]);
	}

	public function onUpdateFieldGuardianId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['type'] = 'autocomplete';
			$attr['target'] = ['key' => 'guardian_id', 'name' => $this->aliasField('guardian_id')];
			$attr['noResults'] = __('No Guardian found.');
			$attr['attr'] = ['placeholder' => __('OpenEMIS ID or Name')];
			$attr['url'] = ['controller' => 'Students', 'action' => 'Guardians', 'ajaxUserAutocomplete'];

			$iconSave = '<i class="fa fa-check"></i> ' . __('Save');
			$iconAdd = '<i class="fa kd-add"></i> ' . __('Create New');
			$attr['onNoResults'] = "$('.btn-save').html('" . $iconAdd . "').val('new')";
			$attr['onBeforeSearch'] = "$('.btn-save').html('" . $iconSave . "').val('save')";
		} else if ($action == 'index') {
			$attr['sort'] = ['field' => 'Guardians.first_name'];
		}
		return $attr;
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$this->Session->delete('Student.Guardians.new');
	}

	public function addOnNew(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$this->Session->write('Student.Guardians.new', $data[$this->alias()]);
		$event->stopPropagation();
		$action = ['plugin' => $this->controller->plugin, 'controller' => $this->controller->name, 'action' => 'GuardianUser', 'add'];
		return $this->controller->redirect($action);
	}

	public function ajaxUserAutocomplete() {
		$this->controller->autoRender = false;
		$this->ControllerAction->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$term = $this->request->query['term'];
			// only search for guardian
			$query = $this->Users->find()->where([$this->Users->aliasField('is_guardian') => 1]);

			$term = trim($term);
			if (!empty($term)) {
				$query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $term]);
			}
			
			$list = $query->all();

			$data = [];
			foreach($list as $obj) {
				$label = sprintf('%s - %s', $obj->openemis_no, $obj->name);
				$data[] = ['label' => $label, 'value' => $obj->id];
			}

			echo json_encode($data);
			die;
		}
	}
}
