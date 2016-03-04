<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;

class QualificationsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->table('staff_qualifications');
		parent::initialize($config);

		$this->addBehavior('ControllerAction.FileUpload', ['size' => '2MB', 'contentEditable' => false, 'allowable_file_types' => 'all']);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('QualificationLevels', ['className' => 'FieldOption.QualificationLevels']);
		$this->belongsTo('QualificationInstitutions', ['className' => 'Staff.QualificationInstitutions']);
		$this->belongsTo('QualificationSpecialisations', ['className' => 'FieldOption.QualificationSpecialisations']);

		$this->addBehavior('OpenEmis.Autocomplete');
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('graduate_year', 'ruleNumeric', 
				['rule' => 'numeric']
			)
			->notEmpty('institution_name', 'Please enter the institution')
			->allowEmpty('file_content')
			;
		;
	}

	public function beforeAction() {
		$this->fields['qualification_level_id']['type'] = 'select';
		$this->fields['qualification_specialisation_id']['type'] = 'select';

		$this->field('graduate_year');
		$this->field('qualification_institution_id');

		// temporary disable
		$this->field('file_name', 			['visible' => false]);
		// file_content is a required field
		$this->field('file_content', 			['type' => 'binary', 'visible' => ['edit' => true]]);

		$this->field('file_type', 			['type' => 'string', 'visible' => ['index'=>true]]);
	}

	public function indexBeforeAction(Event $event) {
		$this->fields['qualification_specialisation_id']['visible'] = false;
		$this->fields['qualification_institution_country']['visible'] = false;
		$this->fields['gpa']['visible'] = false;

		$order = 0;
		$this->setFieldOrder('graduate_year', $order++);
		$this->setFieldOrder('qualification_level_id', $order++);
		$this->setFieldOrder('qualification_title', $order++);
		$this->setFieldOrder('document_no', $order++);
		$this->setFieldOrder('qualification_institution_id', $order++);
	}

	public function addEditBeforeAction(Event $event) {
		$this->fields['graduate_year']['type'] = 'string';

		$order = 0;
		$this->setFieldOrder('qualification_level_id', $order++);
		$this->setFieldOrder('qualification_institution_id', $order++);
		$this->setFieldOrder('qualification_institution_country', $order++);
		$this->setFieldOrder('qualification_title', $order++);
		$this->setFieldOrder('qualification_specialisation_id', $order++);
		$this->setFieldOrder('graduate_year', $order++);
		$this->setFieldOrder('document_no', $order++);
		$this->setFieldOrder('gpa', $order++);
		$this->setFieldOrder('file_name', $order++);
		$this->setFieldOrder('file_content', $order++);
	}

	public function addAfterPatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$alias = $this->alias();
		if (empty($data[$alias]['qualification_institution_id'])) {
			if (!empty($data[$alias]['institution_name'])) {
				$institutionName = $data[$alias]['institution_name'];
				
				$obj = ['name' => $institutionName, 'visible' => 1];

				$newEntity = $this->QualificationInstitutions->newEntity($obj);
				$result = $this->QualificationInstitutions->save($newEntity);
				$entity->qualification_institution_id = $result->id;
			}
		}
	}

	public function editBeforeAction(Event $event) {
		$this->fields['qualification_institution_id']['type'] = 'select';
	}

	public function viewAfterAction(Event $event, Entity $entity) {

		$this->fields['created_user_id']['options'] = [$entity->created_user_id => $entity->created_user->name];
		if (!empty($entity->modified_user_id)) {
			$this->fields['modified_user_id']['options'] = [$entity->modified_user_id => $entity->modified_user->name];
		}
		return $entity;
	}

	public function onUpdateFieldGraduateYear(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['attr'] = [
				'onkeypress' => 'return utility.integerCheck(event)'
			];
		}
		return $attr;
	}
	
	public function onUpdateFieldQualificationInstitutionId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['fieldName'] = $this->aliasField('institution_name');
			$attr['type'] = 'autocomplete';
			$attr['target'] = ['key' => 'qualification_institution_id', 'name' => $this->aliasField('qualification_institution_id')];
			$attr['noResults'] = 'false';
			$attr['attr'] = ['placeholder' => __('Institution')];
			$attr['url'] = ['plugin' => 'Staff', 'controller' => 'Staff', 'action' => 'Qualifications', 'ajaxInstitutionsAutocomplete'];

		}
		return $attr;
	}

	public function ajaxInstitutionsAutocomplete() {
		$this->controller->autoRender = false;	
		$this->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$term = trim($this->request->query['term']);
			$search = '%' . $term . '%';

			$query = $this->QualificationInstitutions
			->find('list')
			->where([$this->QualificationInstitutions->aliasField('name') . ' LIKE ' => $search]);
			
			$list = $query->toArray();
			
			$data = [];
			foreach ($list as $id => $value) {
				$label = $value;
				$data[] = ['label' => $label, 'value' => $id];
			}

			echo json_encode($data);
			die;
		}
	}

	public function onGetFileType(Event $event, Entity $entity) {
		return (!empty($entity->file_name))? $this->getFileTypeForView($entity->file_name): '';;
	}

	private function setupTabElements() {
		$tabElements = $this->controller->getProfessionalDevelopmentTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event) {
		$this->setupTabElements();
	}
	
	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';
    	return $events;
    }

	public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel) {   
		if ($action == "view") {
			if (array_key_exists(1, $this->request->params['pass'])) {
				$filename = $this->get($this->request->params['pass'][1])->file_content;
			}
			
			if (!empty($filename)) {
				$toolbarButtons['download']['type'] = 'button';
				$toolbarButtons['download']['label'] = '<i class="fa kd-download"></i>';
				$toolbarButtons['download']['attr'] = $attr;
				$toolbarButtons['download']['attr']['title'] = __('Download');
				$url = $this->url('download');
				if (!empty($url['action'])) {
					$toolbarButtons['download']['url'] = $url;
				}
			}
		}
	}
}
