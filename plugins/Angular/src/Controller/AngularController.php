<?php

namespace Angular\Controller;

use Angular\Controller\AppController;
use Cake\ORM\TableRegistry;
use Cake\Collection\Collection;

class AngularController extends AppController {
	public $helpers = ['ControllerAction.HtmlField'];
	public function initialize() {
		parent::initialize();
		$this->Angular->resetConfig = false;
	}

	public function app() {
		$this->viewBuilder()->layout(false);
	}

	public function inputs() {
		$this->viewBuilder()->layout(false);
		$requestAttr = json_decode($this->request->query['attributes'], true);
		if (is_array($requestAttr)) {
			$table = TableRegistry::get($requestAttr['className']);
			$fields = array_fill_keys(array_keys($table->fields), '');
			$data = $table->newEntity($fields);
			// pr($data);

			if (isset($requestAttr['fieldName'])) {
				$requestAttr['attr']['name'] = $requestAttr['fieldName'];
			}
			if (isset($requestAttr['label'])) {
				$requestAttr['attr']['label'] = $requestAttr['label'];
			}
			$_attrDefaults = [
				'type' => 'string',
				'model' => $requestAttr['model'],
				'label' => true
			];

			$_fieldAttr = array_merge($_attrDefaults, $requestAttr);

			$_type = $_fieldAttr['type'];
			$_fieldModel = $_fieldAttr['model'];
			$fieldName = $_fieldModel . '.' . $_fieldAttr['field'];
			$options = isset($_fieldAttr['attr']) ? $_fieldAttr['attr'] : [];
					
			$this->set('_type', $_type);
			$this->set('data', $data);
			$this->set('_fieldAttr', $_fieldAttr);
			$this->set('options', $options);
		} else {
			$this->set('_type', null);
		}
	}
}
