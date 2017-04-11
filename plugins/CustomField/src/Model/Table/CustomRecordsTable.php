<?php
namespace CustomField\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class CustomRecordsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->addBehavior('CustomField.Record', [
			'moduleKey' => null
		]);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['form'] = $entity->custom_form_id;
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
		$entity->custom_form_id = $this->request->query('form');
	}

	public function onUpdateFieldCustomFormId(Event $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$formOptions = $this->CustomForms
				->find('list')
				->toArray();
			$selectedForm = $this->queryString('form', $formOptions);
			$this->advancedSelectOptions($formOptions, $selectedForm);

			$attr['type'] = 'select';
			$attr['options'] = $formOptions;
			$attr['onChangeReload'] = 'changeForm';
		} else if ($action == 'edit') {
			$selectedForm = $this->request->query('form');

			$attr['type'] = 'readonly';
			$attr['value'] = $selectedForm;
			$attr['attr']['value'] = $this->CustomForms->get($selectedForm)->name;
		}

		return $attr;
	}

	public function addEditOnChangeForm(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['form']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('custom_form_id', $request->data[$this->alias()])) {
					$this->request->query['form'] = $request->data[$this->alias()]['custom_form_id'];
				}
			}
		}
	}

	private function setupFields(Entity $entity) {
		$this->ControllerAction->field('custom_form_id');
		$this->ControllerAction->setFieldOrder(['custom_form_id', 'name']);
	}
}
