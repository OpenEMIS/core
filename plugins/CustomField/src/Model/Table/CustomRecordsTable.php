<?php
namespace CustomField\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use App\Model\Table\AppTable;

class CustomRecordsTable extends AppTable {
	public function initialize(array $config): void {
		parent::initialize($config);
		$this->addBehavior('CustomField.Record', [
			'moduleKey' => null
		]);
	}

	public function editOnInitialize(EventInterface $event, Entity $entity) {
		//$this->request->getQuery('form') = $entity->custom_form_id;
		$queryParams = $this->request->getQueryParams();
		$queryParams['form'] = $entity->custom_form_id;
		$this->request = $this->request->withQueryParams($queryParams);
	}

	public function addEditAfterAction(EventInterface $event, Entity $entity) {
		$this->setupFields($entity);
		$entity->custom_form_id = $this->request->getQuery('form');
	}

	public function onUpdateFieldCustomFormId(EventInterface $event, array $attr, $action, $request) {
		if ($action == 'add') {
			$formOptions = $this->CustomForms
				->find('list')
				->toArray();
			$selectedForm = $this->setQueryString('form', $formOptions);
			$this->advancedSelectOptions($formOptions, $selectedForm);

			$attr['type'] = 'select';
			$attr['options'] = $formOptions;
			$attr['onChangeReload'] = 'changeForm';
		} else if ($action == 'edit') {
			$selectedForm = $this->request->getQuery('form');

			$attr['type'] = 'readonly';
			$attr['value'] = $selectedForm;
			$attr['attr']['value'] = $this->CustomForms->get($selectedForm)->name;
		}

		return $attr;
	}

	public function addEditOnChangeForm(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		//unset($request->getQuery('form'));
		$queryParams = $this->request->getQueryParams();
		unset($queryParams['form']);
		$this->request = $this->request->withQueryParams($queryParams);
		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->getAlias(), $request->getData())) {
				if (array_key_exists('custom_form_id', $request->getData()[$this->getAlias()])) {
					//$this->request->getQuery('form') = $request->getData()[$this->getAlias()]['custom_form_id'];
					$queryParams['form'] = $requestData[$alias]['custom_form_id'];
				}
			}
		}
		$this->request = $this->request->withQueryParams($queryParams);
	}

	private function setupFields(Entity $entity) {
		$this->ControllerAction->field('custom_form_id');
		$this->ControllerAction->setFieldOrder(['custom_form_id', 'name']);
	}
}
